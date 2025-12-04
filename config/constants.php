<?php
/**
 * Application Constants
 * Các hằng số dùng chung trong ứng dụng
 */

// Application version
define('APP_VERSION', '2.0.0');
define('APP_NAME', 'SmartSpending');

// Pagination
define('ITEMS_PER_PAGE', 10);
define('TRANSACTIONS_PER_PAGE', 7);

// Date formats
define('DATE_FORMAT', 'd/m/Y');
define('DATETIME_FORMAT', 'd/m/Y H:i:s');
define('DB_DATE_FORMAT', 'Y-m-d');
define('DB_DATETIME_FORMAT', 'Y-m-d H:i:s');

// Currency
define('CURRENCY_SYMBOL', '₫');
define('CURRENCY_CODE', 'VND');

// Transaction types
define('TRANSACTION_TYPE_INCOME', 'income');
define('TRANSACTION_TYPE_EXPENSE', 'expense');

// Budget status
define('BUDGET_STATUS_SAFE', 'safe');
define('BUDGET_STATUS_WARNING', 'warning');
define('BUDGET_STATUS_EXCEEDED', 'exceeded');

// Budget thresholds
define('BUDGET_WARNING_THRESHOLD', 80); // 80%
define('BUDGET_EXCEEDED_THRESHOLD', 100); // 100%

// Goal status
define('GOAL_STATUS_ACTIVE', 'active');
define('GOAL_STATUS_COMPLETED', 'completed');
define('GOAL_STATUS_CANCELLED', 'cancelled');

// Recurring transaction frequencies
define('RECURRING_FREQUENCY_DAILY', 'daily');
define('RECURRING_FREQUENCY_WEEKLY', 'weekly');
define('RECURRING_FREQUENCY_MONTHLY', 'monthly');
define('RECURRING_FREQUENCY_YEARLY', 'yearly');

// Session keys
define('SESSION_USER_ID', 'user_id');
define('SESSION_USER_EMAIL', 'user_email');
define('SESSION_USER_NAME', 'user_name');
define('SESSION_CSRF_TOKEN', 'csrf_token');

// Google OAuth (loaded from .env file)
define('GOOGLE_CLIENT_ID', $_ENV['GOOGLE_CLIENT_ID'] ?? '');
define('GOOGLE_CLIENT_SECRET', $_ENV['GOOGLE_CLIENT_SECRET'] ?? '');

// File upload limits
define('MAX_UPLOAD_SIZE', 5242880); // 5MB

// Validation rules
define('MIN_PASSWORD_LENGTH', 6);
define('MAX_PASSWORD_LENGTH', 255);
define('MIN_USERNAME_LENGTH', 3);
define('MAX_USERNAME_LENGTH', 50);

// API response codes
define('HTTP_OK', 200);
define('HTTP_CREATED', 201);
define('HTTP_BAD_REQUEST', 400);
define('HTTP_UNAUTHORIZED', 401);
define('HTTP_FORBIDDEN', 403);
define('HTTP_NOT_FOUND', 404);
define('HTTP_METHOD_NOT_ALLOWED', 405);
define('HTTP_UNPROCESSABLE_ENTITY', 422);
define('HTTP_INTERNAL_SERVER_ERROR', 500);

// Cache settings
define('CACHE_ENABLED', false);
define('CACHE_DURATION', 3600); // 1 hour

// Chart settings
define('CHART_COLORS_INCOME', '#4caf50');
define('CHART_COLORS_EXPENSE', '#f44336');
define('CHART_COLORS_BALANCE', '#2196f3');

// Default categories
define('DEFAULT_CATEGORIES_INCOME', [
    'Lương',
    'Thưởng',
    'Đầu tư',
    'Khác'
]);

define('DEFAULT_CATEGORIES_EXPENSE', [
    'Ăn uống',
    'Giao thông',
    'Mua sắm',
    'Giải trí',
    'Nhà ở',
    'Tiện ích',
    'Sức khỏe',
    'Giáo dục',
    'Gia đình',
    'Khác'
]);
