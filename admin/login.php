<?php
require_once __DIR__ . '/../config/supabase.php';

// admin/login.php
session_start();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Đọc thông tin admin từ .env (không để lộ mật khẩu trong source code)
    $adminUser = getenv('ADMIN_USERNAME');
    $adminPass = getenv('ADMIN_PASSWORD');

    if (!$adminUser || !$adminPass) {
        $error = "Chưa cấu hình tài khoản admin trong file .env";
    } elseif ($username === $adminUser && $password === $adminPass) {
        // Xóa session thí sinh nếu có (tránh lẫn lộn vai trò)
        unset($_SESSION['user_id'], $_SESSION['access_token'], $_SESSION['email']);
        $_SESSION['admin_logged_in'] = true;
        header('Location: ' . BASE_URL . '/admin/index.php');
        exit;
    } else {
        $error = "Tài khoản hoặc mật khẩu quản trị không hợp lệ.";
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/logo.png">
    <title>Admin Login - HALOU</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/public.css">
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="page-wrapper">
<div class="card p-4">
        <h4 class="text-center fw-bold text-dark mb-4">Màn Hình Quản Trị Hệ Thống</h4>
        <?php if($error): ?><div class="alert alert-danger py-2 small"><?php echo $error; ?></div><?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label text-muted small fw-bold">Tài khoản Admin</label>
                <input type="text" name="username" class="form-control" required value="admin">
            </div>
            <div class="mb-4">
                <label class="form-label text-muted small fw-bold">Mật khẩu</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button class="btn btn-brand w-100 fw-bold">ĐĂNG NHẬP QUẢN TRỊ</button>
            <div class="text-center mt-3"><a href="<?php echo BASE_URL; ?>/index.php" class="small text-decoration-none">Quay lại trang thí sinh</a></div>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
