<?php
// auth/logout.php
session_start();

// Xóa tất cả các biến session
$_SESSION = array();

// Nếu phiên làm việc sử dụng cookie, xóa cookie đó đi
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Cuối cùng, hủy session
session_destroy();

// Điều hướng người dùng về trang chủ
header('Location: /tsdhhl26/');
exit;
