<?php
namespace App\Http\Controllers\User;

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
        if ($period === 'this_month') {
            $startDate = date('Y-m-01');
            $endDate = date('Y-m-t');
        } elseif ($period === 'last_3_months') {
            $startDate = date('Y-m-01', strtotime('-2 months'));
            $endDate = date('Y-m-t');
        } elseif ($period === 'last_6_months') {
            $startDate = date('Y-m-01', strtotime('-5 months'));
            $endDate = date('Y-m-t');
        } elseif ($period === 'this_year') {
            $startDate = date('Y-01-01');
            $endDate = date('Y-12-31');
        } else {
            $startDate = date('Y-m-01', strtotime('-2 months'));
            $endDate = date('Y-m-t');
        }

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

    /**
     * Export report to real XLSX using PhpSpreadsheet when available.
     * Falls back to the HTML-based Excel export if library isn't installed.
     */
    public function export_xlsx()
    {
        // If PhpSpreadsheet not available, fallback
        if (!class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
            // Redirect to legacy export
            $query = $_SERVER['QUERY_STRING'] ?? '';
            header('Location: ' . BASE_URL . '/reports/export_excel' . ($query ? '?' . $query : ''));
            exit;
        }

        $userId = $this->getCurrentUserId();
        $period = $_GET['period'] ?? 'last_3_months';
        $type = $_GET['type'] ?? 'all';

        // Determine date range (reuse logic from export_excel)
        if ($period === 'this_month') {
            $startDate = date('Y-m-01');
            $endDate = date('Y-m-t');
        } elseif ($period === 'last_3_months') {
            $startDate = date('Y-m-01', strtotime('-2 months'));
            $endDate = date('Y-m-t');
        } elseif ($period === 'last_6_months') {
            $startDate = date('Y-m-01', strtotime('-5 months'));
            $endDate = date('Y-m-t');
        } elseif ($period === 'this_year') {
            $startDate = date('Y-01-01');
            $endDate = date('Y-12-31');
        } else {
            $startDate = date('Y-m-01', strtotime('-2 months'));
            $endDate = date('Y-m-t');
        }

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

        // Create spreadsheet
        $spreadsheetClass = 'PhpOffice\\PhpSpreadsheet\\Spreadsheet';
        $spreadsheet = new $spreadsheetClass();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Báo cáo');

        // Header
        $sheet->setCellValue('A1', 'Báo cáo chi tiêu');
        $sheet->mergeCells('A1:E1');

        $sheet->fromArray(['Kỳ báo cáo', 'Loại', 'Từ ngày', 'Đến ngày', 'Tổng thu nhập / chi tiêu'], null, 'A2');
        // Compute totals
        $totalIncome = 0.0; $totalExpense = 0.0;
        foreach ($rows as $r) {
            if (($r['type'] ?? '') === 'income') {
                $totalIncome += (float)$r['amount'];
            } else {
                $totalExpense += abs((float)$r['amount']);
            }
        }

        $sheet->setCellValue('A3', $period);
        $sheet->setCellValue('B3', $type);
        $sheet->setCellValue('C3', $startDate);
        $sheet->setCellValue('D3', $endDate);
        $sheet->setCellValue('E3', number_format($totalIncome - $totalExpense, 2, '.', ','));

        // Detail table header
        $startRow = 6;
        $sheet->fromArray(['Ngày', 'Loại', 'Danh mục', 'Mô tả', 'Số tiền (VND)'], null, 'A' . $startRow);

        $r = $startRow + 1;
        foreach ($rows as $row) {
            $sheet->setCellValue('A' . $r, $row['date']);
            $sheet->setCellValue('B' . $r, ($row['type'] === 'income') ? 'Thu nhập' : 'Chi tiêu');
            $sheet->setCellValue('C' . $r, $row['category']);
            $sheet->setCellValue('D' . $r, $row['description']);
            $sheet->setCellValue('E' . $r, (float)$row['amount']);
            $r++;
        }

        // Auto-size columns
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Send XLSX to client
        $filename = 'BaoCao_' . ($period) . '_' . ($type) . '_' . date('Ymd_His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $ioFactory = 'PhpOffice\\PhpSpreadsheet\\IOFactory';
        $writer = call_user_func([$ioFactory, 'createWriter'], $spreadsheet, 'Xlsx');
        if ($writer && method_exists($writer, 'save')) {
            $writer->save('php://output');
        }
        exit;
    }

    private function getLineChartData($userId, $period = 'last_3_months')
    {
        $months = [];
        $income = [];
        $expense = [];

        // Determine the number of months based on period
        if ($period === 'this_month') {
            $monthCount = 1;
        } elseif ($period === 'last_3_months') {
            $monthCount = 3;
        } elseif ($period === 'last_6_months') {
            $monthCount = 6;
        } elseif ($period === 'this_year') {
            $monthCount = 12;
        } else {
            $monthCount = 3;
        }

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
        switch ($period) {
            case 'this_month':
                $startDate = date('Y-m-01');
                $endDate = date('Y-m-t');
                break;
            case 'last_3_months':
                $startDate = date('Y-m-01', strtotime('-2 months'));
                $endDate = date('Y-m-t');
                break;
            case 'last_6_months':
                $startDate = date('Y-m-01', strtotime('-5 months'));
                $endDate = date('Y-m-t');
                break;
            case 'this_year':
                $startDate = date('Y-01-01');
                $endDate = date('Y-12-31');
                break;
            default:
                $startDate = date('Y-m-01', strtotime('-2 months'));
                $endDate = date('Y-m-t');
                break;
        }

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
