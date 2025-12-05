<?php
namespace App\Services;

/**
 * Financial Utilities Service
 * Handles calculation logic separated from models (Single Responsibility Principle)
 */
class FinancialUtils
{
    /**
     * Calculate percentage change between two values
     * @param float $previous Previous value
     * @param float $current Current value
     * @return int|null Percentage change (rounded) or null if no meaningful change
     */
    public static function calculatePercentageChange($previous, $current)
    {
        // If both are 0, no meaningful change
        if ($previous == 0 && $current == 0) {
            return null; // Return null to indicate no data for comparison
        }
        
        if ($previous == 0) {
            return ($current > 0) ? 100 : -100; // If previous was 0, any change is 100%
        }
        
        return round((($current - $previous) / $previous) * 100);
    }

    /**
     * Get start and end dates for a given period range
     * @param string $range Period range (this_week, this_month, this_year, last_month, or YYYY-MM format)
     * @return array [startDate, endDate, prevStartDate, prevEndDate]
     */
    public static function getPeriodDates($range)
    {
        // Check if range is in YYYY-MM format (specific month)
        if (preg_match('/^\d{4}-\d{2}$/', $range)) {
            $startDate = $range . '-01';
            $endDate = date('Y-m-t', strtotime($startDate));
            // Previous month for comparison
            $prevStartDate = date('Y-m-01', strtotime($startDate . ' -1 month'));
            $prevEndDate = date('Y-m-t', strtotime($prevStartDate));
            return [$startDate, $endDate, $prevStartDate, $prevEndDate];
        }

        switch ($range) {
            case 'this_week':
                $startDate = date('Y-m-d', strtotime('monday this week'));
                $endDate = date('Y-m-d', strtotime('sunday this week'));
                $prevStartDate = date('Y-m-d', strtotime('monday last week'));
                $prevEndDate = date('Y-m-d', strtotime('sunday last week'));
                break;
            case 'this_year':
                $startDate = date('Y-01-01');
                $endDate = date('Y-12-31');
                $prevStartDate = date('Y-01-01', strtotime('-1 year'));
                $prevEndDate = date('Y-12-31', strtotime('-1 year'));
                break;
            case 'last_month':
                $startDate = date('Y-m-01', strtotime('first day of last month'));
                $endDate = date('Y-m-t', strtotime('last day of last month'));
                $prevStartDate = date('Y-m-01', strtotime('-2 months'));
                $prevEndDate = date('Y-m-t', strtotime('-2 months'));
                break;
            case 'this_month':
            default:
                // Get last 3 months (current month - 2 months)
                $startDate = date('Y-m-01', strtotime('-2 months'));
                $endDate = date('Y-m-t'); // End of current month
                $prevStartDate = date('Y-m-01', strtotime('first day of last month'));
                $prevEndDate = date('Y-m-t', strtotime('last day of last month'));
                break;
        }
        return [$startDate, $endDate, $prevStartDate, $prevEndDate];
    }

    /**
     * Calculate savings rate
     * @param float $income Total income
     * @param float $expense Total expense
     * @return int Savings rate percentage
     */
    public static function calculateSavingsRate($income, $expense)
    {
        if ($income <= 0) {
            return 0;
        }
        return round((($income - $expense) / $income) * 100);
    }

    /**
     * Format amount for display with thousand separators
     * @param float $amount Amount to format
     * @param string $currency Currency symbol
     * @return string Formatted amount string
     */
    public static function formatCurrency($amount, $currency = 'VND')
    {
        return number_format($amount, 0, ',', '.') . ' ' . $currency;
    }

    /**
     * Generate month labels for charts
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Array of month labels
     */
    public static function generateMonthLabels($startDate, $endDate)
    {
        $labels = [];
        $currentDate = new \DateTime($startDate);
        $finalDate = new \DateTime($endDate);
        $interval = new \DateInterval('P1M');

        while ($currentDate <= $finalDate) {
            $labels[] = "Tháng " . ltrim($currentDate->format('m'), '0');
            $currentDate->add($interval);
        }

        return $labels;
    }

    /**
     * Determine if an amount should be stored as negative (expense) or positive (income)
     * @param float $amount Original amount
     * @param string $categoryType Category type ('income' or 'expense')
     * @return float Amount with correct sign
     */
    public static function normalizeAmount($amount, $categoryType)
    {
        return ($categoryType === 'income') ? abs($amount) : -abs($amount);
    }

    /**
     * Calculate budget progress
     * @param float $spent Amount spent
     * @param float $limit Budget limit
     * @return array ['percentage' => float, 'remaining' => float, 'status' => string]
     */
    public static function calculateBudgetProgress($spent, $limit)
    {
        if ($limit <= 0) {
            return [
                'percentage' => 0,
                'remaining' => 0,
                'status' => 'no-limit'
            ];
        }

        $percentage = round(($spent / $limit) * 100, 2);
        $remaining = $limit - $spent;
        
        $status = 'safe';
        if ($percentage >= 100) {
            $status = 'exceeded';
        } elseif ($percentage >= 80) {
            $status = 'warning';
        }

        return [
            'percentage' => $percentage,
            'remaining' => $remaining,
            'status' => $status
        ];
    }

    /**
     * Sanitize input string
     * @param string $input Input string
     * @return string Sanitized string
     */
    public static function sanitizeString($input)
    {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validate email format
     * @param string $email Email to validate
     * @return bool True if valid, false otherwise
     */
    public static function validateEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate amount
     * @param mixed $amount Amount to validate
     * @return bool True if valid, false otherwise
     */
    public static function validateAmount($amount)
    {
        return is_numeric($amount) && $amount > 0;
    }

    /**
     * Validate date format
     * @param string $date Date string
     * @param string $format Expected format (default: Y-m-d)
     * @return bool True if valid, false otherwise
     */
    public static function validateDate($date, $format = 'Y-m-d')
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
}
