<?php
// admin/login.php
require_once __DIR__ . '/../config/database.php';
session_start();
require_once __DIR__ . '/../auth/includes/auth_layout.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../lib/RateLimiter.php';
    if (!RateLimiter::checkSessionLimit('admin_login', 5, 1800)) {
        $error = "Bạn đã thử đăng nhập sai vượt quá 5 lần. Vui lòng dừng lại 30 phút để bảo vệ hệ thống.";
    } else {
        $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $adminUser = getenv('ADMIN_USERNAME');
    $adminPass = getenv('ADMIN_PASSWORD');

    if (!$adminUser || !$adminPass) {
        $error = "Chưa cấu hình tài khoản admin trong file .env";
    } elseif ($username === $adminUser && $password === $adminPass) {
        session_regenerate_id(true);
        unset($_SESSION['user_id'], $_SESSION['access_token'], $_SESSION['email']);
        $_SESSION['admin_logged_in'] = true;
        header('Location: ' . BASE_URL . '/admin/index.php');
        exit;
    } else {
        $error = "Tài khoản hoặc mật khẩu quản trị không hợp lệ.";
    }
    }
}

authPageStart('Admin Login');
?>

<div class="card p-4">
    <h4 class="text-center fw-bold text-dark mb-4">Màn Hình Quản Trị Hệ Thống</h4>
    <?php include __DIR__ . '/../includes/flash_messages.php'; ?>
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

<?php authPageEnd(); ?>
