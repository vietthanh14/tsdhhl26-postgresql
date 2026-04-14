<?php
// config/database.php

// Thiết lập múi giờ mặc định cho Việt Nam (GMT+7)
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Load Composer autoload
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}
// Hàm tải biến môi trường từ file .env
function loadEnv($path)
{
    if (!file_exists($path)) {
        return false;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0)
            continue;
        list($name, $value) = explode('=', $line, 2);

        $name = trim($name);
        $value = trim($value);

        // Loại bỏ dấu nháy kép thừa nếu có
        $value = trim($value, '"');
        $value = trim($value, "'");

        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
    return true;
}

// Nạp các biến .env từ thư mục gốc
loadEnv(__DIR__ . '/../.env');

// Cấu hình Database cPanel
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '5432');
define('DB_NAME', getenv('DB_NAME') ?: '');
define('DB_USER', getenv('DB_USER') ?: '');
define('DB_PASS', getenv('DB_PASS') ?: '');

// Đường dẫn gốc ứng dụng — TỰ ĐỘNG PHÁT HIỆN, không cần cấu hình
if (getenv('APP_BASE_PATH')) {
    define('BASE_PATH', rtrim(getenv('APP_BASE_PATH'), '/') . '/');
    define('BASE_URL', rtrim(getenv('APP_BASE_PATH'), '/'));
} else {
    $projectRoot = str_replace('\\', '/', dirname(__DIR__));
    $scriptFilename = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? '');
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

    $baseUrl = '';

    // Tìm phần đường dẫn tương đối từ gốc dự án đến file thực thi
    // VD: projectRoot = "/home/user/public_html", scriptFilename = "/home/user/public_html/auth/login.php"
    // => relativePath = "/auth/login.php"
    if ($projectRoot && $scriptFilename && strpos($scriptFilename, $projectRoot) === 0) {
        $relativePath = substr($scriptFilename, strlen($projectRoot));

        // Loại bỏ phần relativePath khỏi cuối SCRIPT_NAME
        // VD: scriptName = "/tsdhhl26/auth/login.php", relativePath = "/auth/login.php"
        // => baseUrl = "/tsdhhl26"
        if ($relativePath && substr($scriptName, -strlen($relativePath)) === $relativePath) {
            $baseUrl = substr($scriptName, 0, -strlen($relativePath));
        }
    }

    // Fallback nếu thuật toán trên không lấy được
    if ($baseUrl === '') {
        $docRoot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/'));
        if ($docRoot && strpos($projectRoot, $docRoot) === 0) {
            $baseUrl = substr($projectRoot, strlen($docRoot));
        }
    }

    // Đảm bảo định dạng chuẩn
    $baseUrl = rtrim($baseUrl, '/');
    define('BASE_PATH', $baseUrl . '/');
    define('BASE_URL', $baseUrl);
}

// URL Web App của Google Apps Script (Sẽ cấu hình sau khi upload source GAS lên)
define('GAS_WEBAPP_URL', getenv('GAS_WEBAPP_URL') ?: '');