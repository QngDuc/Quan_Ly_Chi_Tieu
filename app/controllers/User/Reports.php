<?php
namespace App\Controllers\User;

use App\Core\Controllers;
use App\Middleware\AuthCheck;

class Reports extends Controllers
{
    private $transactionModel;

    public function __construct()
    {
        parent::__construct();
        // Kiểm tra quyền user
        AuthCheck::requireUser();
        $this->transactionModel = $this->model('Transaction');
    }

    public function index($period = 'last_3_months', $type = 'all')
    {
        $userId = $this->getCurrentUserId();
        
        // Get data based on filters
        $reportLine = $this->getLineChartData($userId, $period);
        $reportPie = $this->getPieChartData($userId, $period, $type);

        $data = [
            'title' => 'Báo cáo',
            'reportLine' => $reportLine,
            'reportPie' => $reportPie,
            'current_period' => $period,
            'current_type' => $type
        ];

        $this->view('user/reports', $data);
    }

    /**
     * API endpoint to get report data dynamically
     */
    public function api_get_report_data()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        try {
            $userId = $this->getCurrentUserId();
            $period = $_GET['period'] ?? 'last_3_months';
            $type = $_GET['type'] ?? 'all';

            $reportLine = $this->getLineChartData($userId, $period);
            $reportPie = $this->getPieChartData($userId, $period, $type);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => [
                    'lineChart' => $reportLine,
                    'pieChart' => $reportPie
                ]
            ]);
        } catch (\Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Export report to Excel-compatible (.xls via HTML table)
     */
    public function export_excel()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo 'Method not allowed';
            return;
        }

        $userId = $this->getCurrentUserId();
        $period = $_GET['period'] ?? 'last_3_months';
        $type = $_GET['type'] ?? 'all';

        // Determine date range based on period (align with charts)
        list($startDate, $endDate) = match($period) {
            'this_month' => [date('Y-m-01'), date('Y-m-t')],
            'last_3_months' => [date('Y-m-01', strtotime('-2 months')), date('Y-m-t')],
            'last_6_months' => [date('Y-m-01', strtotime('-5 months')), date('Y-m-t')],
            'this_year' => [date('Y-01-01'), date('Y-12-31')],
            default => [date('Y-m-01', strtotime('-2 months')), date('Y-m-t')]
        };

        // Fetch transactions in range with optional type filter
        $db = (new \App\Core\ConnectDB())->getConnection();
        $sql = "
            SELECT t.date, t.description, c.name AS category, t.type, t.amount
            FROM transactions t
            JOIN categories c ON t.category_id = c.id
            WHERE t.user_id = ? AND t.date BETWEEN ? AND ?
        ";
        $params = [$userId, $startDate, $endDate];
        if ($type === 'income' || $type === 'expense') {
            $sql .= " AND t.type = ?";
            $params[] = $type;
        }
        $sql .= " ORDER BY t.date ASC, t.id ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Compute totals
        $totalIncome = 0.0; $totalExpense = 0.0;
        foreach ($rows as $r) {
            if (($r['type'] ?? '') === 'income') {
                $totalIncome += (float)$r['amount'];
            } else {
                // Expense amounts are stored as negative; take abs for total expense
                $totalExpense += abs((float)$r['amount']);
            }
        }
        $balance = $totalIncome - $totalExpense;

        // Headers for Excel download
        $filename = 'BaoCao_' . ($period) . '_' . ($type) . '_' . date('Ymd_His') . '.xls';
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Output minimal HTML table Excel understands
        echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel
        echo '<html><head><meta charset="UTF-8"></head><body>';

        // Summary table
        echo '<table border="1" cellspacing="0" cellpadding="6">';
        echo '<tr><th colspan="2">Báo cáo chi tiêu</th></tr>';
        echo '<tr><td>Kỳ báo cáo</td><td>' . htmlspecialchars($period) . '</td></tr>';
        echo '<tr><td>Loại</td><td>' . htmlspecialchars($type) . '</td></tr>';
        echo '<tr><td>Từ ngày</td><td>' . htmlspecialchars($startDate) . '</td></tr>';
        echo '<tr><td>Đến ngày</td><td>' . htmlspecialchars($endDate) . '</td></tr>';
        echo '<tr><td>Tổng thu nhập</td><td>' . number_format($totalIncome, 0, ',', '.') . '</td></tr>';
        echo '<tr><td>Tổng chi tiêu</td><td>' . number_format($totalExpense, 0, ',', '.') . '</td></tr>';
        echo '<tr><td>Chênh lệch</td><td>' . number_format($balance, 0, ',', '.') . '</td></tr>';
        echo '</table><br />';

        // Detail table
        echo '<table border="1" cellspacing="0" cellpadding="6">';
        echo '<tr style="background:#e8f5e9;">'
            . '<th>Ngày</th>'
            . '<th>Loại</th>'
            . '<th>Danh mục</th>'
            . '<th>Mô tả</th>'
            . '<th>Số tiền (VND)</th>'
            . '</tr>';

        foreach ($rows as $r) {
            $date = htmlspecialchars($r['date']);
            $typeText = ($r['type'] === 'income') ? 'Thu nhập' : 'Chi tiêu';
            $cat = htmlspecialchars($r['category'] ?? '');
            $desc = htmlspecialchars($r['description'] ?? '');
            $amount = number_format((float)$r['amount'], 0, ',', '.');
            echo '<tr>'
                . '<td>' . $date . '</td>'
                . '<td>' . $typeText . '</td>'
                . '<td>' . $cat . '</td>'
                . '<td>' . $desc . '</td>'
                . '<td style="mso-number-format:\\#\\,\\#\\#0;">' . $amount . '</td>'
                . '</tr>';
        }
        echo '</table>';
        echo '</body></html>';
        exit;
    }

    private function getLineChartData($userId, $period = 'last_3_months')
    {
        $months = [];
        $income = [];
        $expense = [];

        // Determine the number of months based on period
        $monthCount = match($period) {
            'this_month' => 1,
            'last_3_months' => 3,
            'last_6_months' => 6,
            'this_year' => 12,
            default => 3
        };

        // Get data for the specified period
        for ($i = $monthCount - 1; $i >= 0; $i--) {
            $date = date('Y-m', strtotime("-$i months"));
            $monthName = date('m/Y', strtotime("-$i months"));
            
            $startDate = $date . '-01';
            $endDate = date('Y-m-t', strtotime($startDate));

            // Get totals for this month
            $result = $this->transactionModel->getMonthTotals($userId, $startDate, $endDate);

            $months[] = $monthName;
            $income[] = floatval($result['income'] ?? 0);
            $expense[] = floatval($result['expense'] ?? 0);
        }

        return [
            'labels' => $months,
            'income' => $income,
            'expense' => $expense
        ];
    }

    private function getPieChartData($userId, $period = 'last_3_months', $type = 'all')
    {
        // Determine date range based on period
        list($startDate, $endDate) = match($period) {
            'this_month' => [date('Y-m-01'), date('Y-m-t')],
            'last_3_months' => [date('Y-m-01', strtotime('-2 months')), date('Y-m-t')],
            'last_6_months' => [date('Y-m-01', strtotime('-5 months')), date('Y-m-t')],
            'this_year' => [date('Y-01-01'), date('Y-12-31')],
            default => [date('Y-m-01', strtotime('-2 months')), date('Y-m-t')]
        };

        // Get category breakdown with optional type filter
        $results = $this->transactionModel->getCategoryBreakdown($userId, $startDate, $endDate, $type);

        $labels = [];
        $data = [];

        foreach ($results as $row) {
            $labels[] = $row['name'];
            $data[] = floatval($row['total']);
        }

        return [
            'labels' => $labels,
            'data' => $data
        ];
    }
}
