<?php
// Deprecated: View moved to views/auth/login.php
// Keep file empty to avoid double header issues when included by mistake.
http_response_code(301);
header('Location: ' . (defined('BASE_URL') ? BASE_URL : '') . '/auth/login');
exit;