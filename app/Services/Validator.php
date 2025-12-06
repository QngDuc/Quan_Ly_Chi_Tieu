<?php
namespace App\Services;

/**
 * Validator Service
 * Handles data validation and sanitization for all user inputs
 */
class Validator
{
    private $errors = [];
    private $data = [];

    /**
     * Validate transaction data
     * @param array $data Data to validate
     * @return bool True if valid, false otherwise
     */
    public function validateTransaction($data)
    {
        $this->errors = [];
        $this->data = [];

        // Validate amount
        if (!isset($data['amount'])) {
            $this->errors['amount'] = 'Số tiền là bắt buộc';
        } else {
            // Convert to float and validate
            $amount = is_numeric($data['amount']) ? floatval($data['amount']) : 0;
            if ($amount <= 0) {
                $this->errors['amount'] = 'Số tiền phải là số dương hợp lệ';
            } else {
                $this->data['amount'] = $amount;
            }
        }

        // Validate category_id
        if (!isset($data['category_id']) || !is_numeric($data['category_id']) || $data['category_id'] <= 0) {
            $this->errors['category_id'] = 'Vui lòng chọn danh mục hợp lệ';
        } else {
            $this->data['category_id'] = intval($data['category_id']);
        }

        // Validate date - support both 'date' and 'transaction_date' field names
        $dateValue = null;
        if (isset($data['transaction_date']) && !empty($data['transaction_date'])) {
            $dateValue = $data['transaction_date'];
        } elseif (isset($data['date']) && !empty($data['date'])) {
            $dateValue = $data['date'];
        }

        if ($dateValue) {
            $dateObj = \DateTime::createFromFormat('Y-m-d', $dateValue);
            if (!$dateObj || $dateObj->format('Y-m-d') !== $dateValue) {
                $this->errors['date'] = 'Ngày không hợp lệ (định dạng: YYYY-MM-DD)';
            } else {
                $this->data['date'] = $dateValue;
            }
        } else {
            $this->data['date'] = date('Y-m-d');
        }

        // Validate and sanitize description
        if (isset($data['description'])) {
            $description = trim($data['description']);
            if (strlen($description) > 255) {
                $this->errors['description'] = 'Mô tả không được vượt quá 255 ký tự';
            } else {
                $this->data['description'] = htmlspecialchars($description, ENT_QUOTES, 'UTF-8');
            }
        } else {
            $this->data['description'] = '';
        }

        // Validate type (optional, can be determined by category)
        if (isset($data['type']) && !in_array($data['type'], ['income', 'expense'])) {
            $this->errors['type'] = 'Loại giao dịch không hợp lệ';
        } elseif (isset($data['type'])) {
            $this->data['type'] = $data['type'];
        }

        return empty($this->errors);
    }

    /**
     * Validate user profile data
     * @param array $data Data to validate
     * @return bool True if valid, false otherwise
     */
    public function validateProfile($data)
    {
        $this->errors = [];
        $this->data = [];

        // Validate name
        if (!isset($data['name']) || empty(trim($data['name']))) {
            $this->errors['name'] = 'Tên không được để trống';
        } elseif (strlen($data['name']) > 100) {
            $this->errors['name'] = 'Tên không được vượt quá 100 ký tự';
        } else {
            $this->data['name'] = htmlspecialchars(trim($data['name']), ENT_QUOTES, 'UTF-8');
        }

        // Validate email
        if (!isset($data['email']) || empty(trim($data['email']))) {
            $this->errors['email'] = 'Email không được để trống';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->errors['email'] = 'Email không hợp lệ';
        } else {
            $this->data['email'] = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
        }

        return empty($this->errors);
    }

    /**
     * Validate password change data
     * @param array $data Data to validate
     * @return bool True if valid, false otherwise
     */
    public function validatePasswordChange($data)
    {
        $this->errors = [];
        $this->data = [];

        // Validate current password
        if (!isset($data['current_password']) || empty($data['current_password'])) {
            $this->errors['current_password'] = 'Vui lòng nhập mật khẩu hiện tại';
        } else {
            $this->data['current_password'] = $data['current_password'];
        }

        // Validate new password
        if (!isset($data['new_password']) || empty($data['new_password'])) {
            $this->errors['new_password'] = 'Vui lòng nhập mật khẩu mới';
        } elseif (strlen($data['new_password']) < 6) {
            $this->errors['new_password'] = 'Mật khẩu mới phải có ít nhất 6 ký tự';
        } else {
            $this->data['new_password'] = $data['new_password'];
        }

        // Validate confirm password
        if (isset($data['confirm_password'])) {
            if ($data['confirm_password'] !== $data['new_password']) {
                $this->errors['confirm_password'] = 'Mật khẩu xác nhận không khớp';
            }
        }

        return empty($this->errors);
    }

    /**
     * Validate category data
     * @param array $data Data to validate
     * @return bool True if valid, false otherwise
     */
    public function validateCategory($data)
    {
        $this->errors = [];
        $this->data = [];

        // Validate name
        if (!isset($data['name']) || empty(trim($data['name']))) {
            $this->errors['name'] = 'Tên danh mục không được để trống';
        } elseif (strlen($data['name']) > 100) {
            $this->errors['name'] = 'Tên danh mục không được vượt quá 100 ký tự';
        } else {
            $this->data['name'] = FinancialUtils::sanitizeString($data['name']);
        }

        // Validate type
        if (!isset($data['type']) || !in_array($data['type'], ['income', 'expense'])) {
            $this->errors['type'] = 'Loại danh mục không hợp lệ';
        } else {
            $this->data['type'] = $data['type'];
        }

        return empty($this->errors);
    }

    /**
     * Validate budget data
     * @param array $data Data to validate
     * @return bool True if valid, false otherwise
     */
    public function validateBudget($data)
    {
        $this->errors = [];
        $this->data = [];

        // Validate category_id
        if (!isset($data['category_id']) || !is_numeric($data['category_id']) || $data['category_id'] <= 0) {
            $this->errors['category_id'] = 'Vui lòng chọn danh mục hợp lệ';
        } else {
            $this->data['category_id'] = intval($data['category_id']);
        }

        // Validate limit_amount
        if (!isset($data['limit_amount']) || !is_numeric($data['limit_amount']) || $data['limit_amount'] <= 0) {
            $this->errors['limit_amount'] = 'Hạn mức phải là số dương hợp lệ';
        } else {
            $this->data['limit_amount'] = floatval($data['limit_amount']);
        }

        // Validate period (month or year)
        if (!isset($data['period']) || empty($data['period'])) {
            $this->errors['period'] = 'Vui lòng chọn kỳ hạn';
        } elseif (!preg_match('/^\d{4}-\d{2}$/', $data['period'])) {
            $this->errors['period'] = 'Kỳ hạn không hợp lệ (định dạng: YYYY-MM)';
        } else {
            $this->data['period'] = $data['period'];
        }

        return empty($this->errors);
    }

    /**
     * Validate recurring transaction data
     * @param array $data Data to validate
     * @return bool True if valid, false otherwise
     */
    public function validateRecurringTransaction($data)
    {
        $this->errors = [];
        $this->data = [];

        // First validate as regular transaction
        if (!$this->validateTransaction($data)) {
            return false;
        }

        // Validate frequency
        $validFrequencies = ['daily', 'weekly', 'monthly', 'yearly'];
        if (!isset($data['frequency']) || !in_array($data['frequency'], $validFrequencies)) {
            $this->errors['frequency'] = 'Tần suất không hợp lệ';
        } else {
            $this->data['frequency'] = $data['frequency'];
        }

        // Validate start_date
        if (!isset($data['start_date']) || !FinancialUtils::validateDate($data['start_date'])) {
            $this->errors['start_date'] = 'Ngày bắt đầu không hợp lệ';
        } else {
            $this->data['start_date'] = $data['start_date'];
        }

        // Validate end_date (optional)
        if (isset($data['end_date']) && !empty($data['end_date'])) {
            if (!FinancialUtils::validateDate($data['end_date'])) {
                $this->errors['end_date'] = 'Ngày kết thúc không hợp lệ';
            } elseif (strtotime($data['end_date']) < strtotime($data['start_date'])) {
                $this->errors['end_date'] = 'Ngày kết thúc phải sau ngày bắt đầu';
            } else {
                $this->data['end_date'] = $data['end_date'];
            }
        }

        return empty($this->errors);
    }

    /**
     * Get validation errors
     * @return array Array of errors
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Get first error message
     * @return string|null First error message or null
     */
    public function getFirstError()
    {
        return !empty($this->errors) ? reset($this->errors) : null;
    }

    /**
     * Get validated data
     * @return array Validated and sanitized data
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Get a specific field from validated data
     * @param string $key Field key
     * @param mixed $default Default value if not found
     * @return mixed Field value or default
     */
    public function get($key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Generic validate method with rules
     * @param array $data Data to validate
     * @param array $rules Validation rules
     * @return bool True if valid, false otherwise
     */
    public function validate($data, $rules)
    {
        $this->errors = [];
        $this->data = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            
            foreach ($fieldRules as $rule) {
                if ($rule === 'required' && empty($value)) {
                    $this->errors[$field] = ucfirst($field) . ' is required';
                    continue 2;
                }
                
                if ($rule === 'numeric' && !is_numeric($value)) {
                    $this->errors[$field] = ucfirst($field) . ' must be numeric';
                    continue 2;
                }
                
                if ($rule === 'positive' && (!is_numeric($value) || $value <= 0)) {
                    $this->errors[$field] = ucfirst($field) . ' must be positive';
                    continue 2;
                }
                
                if (strpos($rule, 'min:') === 0) {
                    $min = (int)substr($rule, 4);
                    if (strlen($value) < $min) {
                        $this->errors[$field] = ucfirst($field) . " must be at least {$min} characters";
                        continue 2;
                    }
                }
                
                if (strpos($rule, 'max:') === 0) {
                    $max = (int)substr($rule, 4);
                    if (strlen($value) > $max) {
                        $this->errors[$field] = ucfirst($field) . " must not exceed {$max} characters";
                        continue 2;
                    }
                }
                
                if ($rule === 'date' && !empty($value)) {
                    $d = \DateTime::createFromFormat('Y-m-d', $value);
                    if (!$d || $d->format('Y-m-d') !== $value) {
                        $this->errors[$field] = ucfirst($field) . ' must be a valid date (YYYY-MM-DD)';
                        continue 2;
                    }
                }
                
                if ($rule === 'in:active,completed,cancelled' && !in_array($value, ['active', 'completed', 'cancelled'])) {
                    $this->errors[$field] = ucfirst($field) . ' must be one of: active, completed, cancelled';
                    continue 2;
                }
            }
            
            // If no errors for this field, add to validated data
            if (!isset($this->errors[$field])) {
                $this->data[$field] = $value;
            }
        }

        return empty($this->errors);
    }

    /**
     * Sanitize a string value
     * @param string $value Value to sanitize
     * @return string Sanitized value
     */
    public function sanitize($value)
    {
        if (is_null($value)) {
            return '';
        }
        return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
    }
}
