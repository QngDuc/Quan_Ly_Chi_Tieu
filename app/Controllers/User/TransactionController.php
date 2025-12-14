<?php
namespace App\Controllers\User;

use App\Core\Controllers;
use App\Core\ConnectDB;
use App\Core\Response;
use App\Middleware\AuthCheck;

class TransactionController extends Controllers
{
    protected $db;

    public function __construct()
    {
        parent::__construct();
        AuthCheck::requireUser();
        $this->db = (new ConnectDB())->getConnection();
    }

    /**
     * Create transaction with JARS allocation logic.
     * @param int $userId
     * @param array $data - expects ['type'=>'income'|'expense','amount'=>12345,'category_id'=>int,'date'=>'YYYY-MM-DD','description'=>string]
     * @return array
     */
    public function createTransaction(int $userId, array $data): array
    {
        try {
            $this->db->beginTransaction();

            $type = $data['type'] ?? 'expense';
            $amount = isset($data['amount']) ? floatval($data['amount']) : 0.0;
            $categoryId = isset($data['category_id']) ? intval($data['category_id']) : null;
            $date = $data['date'] ?? date('Y-m-d');
            $description = $data['description'] ?? '';

            if ($amount <= 0) {
                throw new \Exception('Invalid amount');
            }

            // --- Handle Income: allocate to jars according to user settings ---
            if ($type === 'income') {
                // 1) load user budget settings
                $stmt = $this->db->prepare("SELECT nec_percent, ffa_percent, ltss_percent, edu_percent, play_percent, give_percent FROM user_budget_settings WHERE user_id = ? LIMIT 1");
                $stmt->execute([$userId]);
                $settings = $stmt->fetch(\PDO::FETCH_ASSOC);

                if (!$settings) {
                    // defaults (percentages)
                    $settings = [
                        'nec_percent' => 55,
                        'ffa_percent' => 10,
                        'ltss_percent' => 10,
                        'edu_percent' => 10,
                        'play_percent' => 10,
                        'give_percent' => 5,
                    ];
                }

                // 2) compute allocations (use 2 decimal places)
                $allocations = [];
                $allocations['nec'] = round($amount * (floatval($settings['nec_percent']) / 100.0), 2);
                $allocations['ffa'] = round($amount * (floatval($settings['ffa_percent']) / 100.0), 2);
                $allocations['ltss'] = round($amount * (floatval($settings['ltss_percent']) / 100.0), 2);
                $allocations['edu'] = round($amount * (floatval($settings['edu_percent']) / 100.0), 2);
                $allocations['play'] = round($amount * (floatval($settings['play_percent']) / 100.0), 2);
                $allocations['give'] = round($amount * (floatval($settings['give_percent']) / 100.0), 2);

                // Fix rounding drift: adjust NEC by remainder
                $sumAlloc = array_sum($allocations);
                $diff = round($amount - $sumAlloc, 2);
                if ($diff != 0) {
                    $allocations['nec'] += $diff;
                }

                // 3) update user_wallets for each jar
                $upStmt = $this->db->prepare("INSERT INTO user_wallets (user_id, jar_code, balance) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE balance = balance + VALUES(balance)");
                foreach ($allocations as $jar => $amtAlloc) {
                    if ($amtAlloc <= 0) continue;
                    // Use positive amount for wallet increase
                    $upStmt->execute([$userId, $jar, $amtAlloc]);
                }

                // 4) insert transaction record (store positive amount and type 'income')
                $ins = $this->db->prepare("INSERT INTO transactions (user_id, category_id, amount, date, description, type, created_at) VALUES (?, ?, ?, ?, ?, 'income', NOW())");
                $ins->execute([$userId, $categoryId ?? 0, $amount, $date, $description]);
                $txId = $this->db->lastInsertId();

                $this->db->commit();
                return ['success' => true, 'transaction_id' => $txId, 'allocations' => $allocations];
            }

            // --- Handle Expense: subtract from corresponding jar based on category.group_type ---
            if ($type === 'expense') {
                if (!$categoryId) {
                    throw new \Exception('Category required for expense');
                }

                // 1) get category group_type
                $cstmt = $this->db->prepare("SELECT group_type FROM categories WHERE id = ? LIMIT 1");
                $cstmt->execute([$categoryId]);
                $cat = $cstmt->fetch(\PDO::FETCH_ASSOC);
                $group = $cat['group_type'] ?? 'none';

                // 2) if group_type not 'none', subtract from that jar
                if ($group && $group !== 'none') {
                    // Ensure wallet row exists
                    $this->db->prepare("INSERT IGNORE INTO user_wallets (user_id, jar_code, balance) VALUES (?, ?, 0)")->execute([$userId, $group]);

                    // Lock the wallet row and check balance to prevent overdraft
                    $lockStmt = $this->db->prepare("SELECT balance FROM user_wallets WHERE user_id = ? AND jar_code = ? LIMIT 1 FOR UPDATE");
                    $lockStmt->execute([$userId, $group]);
                    $walletRow = $lockStmt->fetch(\PDO::FETCH_ASSOC);
                    $currentJarBalance = isset($walletRow['balance']) ? floatval($walletRow['balance']) : 0.0;

                    if ($currentJarBalance < $amount) {
                        throw new \Exception('Số dư trong lọ "' . strtoupper($group) . '" không đủ. Còn: ' . number_format($currentJarBalance) . 'đ');
                    }

                    // Subtract amount (store negative in transactions)
                    $upd = $this->db->prepare("UPDATE user_wallets SET balance = balance - ? WHERE user_id = ? AND jar_code = ?");
                    $upd->execute([$amount, $userId, $group]);
                }

                // 3) insert transaction as negative amount
                $txAmount = -abs($amount);
                $ins = $this->db->prepare("INSERT INTO transactions (user_id, category_id, amount, date, description, type, created_at) VALUES (?, ?, ?, ?, ?, 'expense', NOW())");
                $ins->execute([$userId, $categoryId, $txAmount, $date, $description]);
                $txId = $this->db->lastInsertId();

                // 4) Budget alert: sum expense in this category for current month and compare with budgets
                $monthStart = date('Y-m-01', strtotime($date));
                $monthEnd = date('Y-m-t', strtotime($date));

                $sumStmt = $this->db->prepare("SELECT COALESCE(SUM(ABS(amount)),0) as total FROM transactions WHERE user_id = ? AND category_id = ? AND type = 'expense' AND date BETWEEN ? AND ?");
                $sumStmt->execute([$userId, $categoryId, $monthStart, $monthEnd]);
                $row = $sumStmt->fetch(\PDO::FETCH_ASSOC);
                $totalSpent = isset($row['total']) ? floatval($row['total']) : 0.0;

                // Get budget for this category (prefer monthly row)
                $bstmt = $this->db->prepare("SELECT amount, period FROM budgets WHERE user_id = ? AND category_id = ? AND is_active = 1 LIMIT 1");
                $bstmt->execute([$userId, $categoryId]);
                $budget = $bstmt->fetch(\PDO::FETCH_ASSOC);

                $alert = ['status' => 'none', 'ratio' => null];
                if ($budget && floatval($budget['amount']) > 0) {
                    $budgetAmount = floatval($budget['amount']);
                    $ratio = ($totalSpent / $budgetAmount) * 100.0;
                    $alert['ratio'] = round($ratio, 2);
                    if ($ratio < 80) $alert['status'] = 'success';
                    elseif ($ratio >= 80 && $ratio <= 100) $alert['status'] = 'warning';
                    else $alert['status'] = 'danger';
                }

                $this->db->commit();
                return ['success' => true, 'transaction_id' => $txId, 'total_spent' => $totalSpent, 'budget' => $budget ?? null, 'alert' => $alert];
            }

            throw new \Exception('Unsupported transaction type');

        } catch (\Exception $e) {
            try { $this->db->rollBack(); } catch (\Exception $ex) {}
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
