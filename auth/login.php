<?php
// auth/login.php
session_start();
require_once __DIR__ . '/../lib/SupabaseClient.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/candidate/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = strtolower(trim($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        try {
            $fake_email = $username . '@halou.system';
            $supabase = new SupabaseClient('anon');
            $response = $supabase->signIn($fake_email, $password);

            if ($response['code'] == 200 && isset($response['data']['access_token'])) {
                // Xóa session admin nếu có (tránh lẫn lộn vai trò)
                unset($_SESSION['admin_logged_in']);
                // Đăng nhập thành công, lưu Token và ID vào session
                $_SESSION['access_token'] = $response['data']['access_token'];
                $_SESSION['user_id'] = $response['data']['user']['id'];
                $_SESSION['email'] = $response['data']['user']['email'];

                header('Location: ' . BASE_URL . '/candidate/index.php');
                exit;
            } else {
                // Lỗi từ Supabase (Vd: Sai MK)
                $error = $response['data']['error_description'] ?? 'Tài khoản hoặc mật khẩu không chính xác.';
            }
        } catch (Exception $e) {
            $error = "Lỗi hệ thống: " . $e->getMessage();
        }
    } else {
        $error = 'Vui lòng nhập đầy đủ Tên đăng nhập và Mật khẩu.';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Tuyển sinh Đại học Hạ Long</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/public.css">
</head>

<body>

    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="page-wrapper">
        <div class="auth-card">
            <div class="text-center mb-4">
                <h3 class="auth-title fw-bold">ĐĂNG NHẬP</h3>

            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger py-2 rounded-1 border-0 bg-danger text-white small" role="alert"
                    aria-live="polite"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label for="loginUsername" class="form-label fw-semibold">Tên đăng nhập</label>
                    <input type="text" name="username" id="loginUsername" class="form-control" required
                        placeholder="Nhập tên đăng nhập…" autocomplete="username" spellcheck="false">
                </div>
                <div class="mb-4">
                    <label for="loginPassword" class="form-label fw-semibold">Mật khẩu</label>
                    <input type="password" name="password" id="loginPassword" class="form-control" required
                        placeholder="Nhập mật khẩu…" autocomplete="current-password">
                </div>

                <div class="d-flex justify-content-end mb-3">
                    <a href="<?php echo BASE_URL; ?>/auth/forgot_password.php"
                        class="text-decoration-none small text-brand fw-medium">Quên mật khẩu?</a>
                </div>

                <button type="submit" class="btn btn-brand w-100 py-2">ĐĂNG NHẬP</button>
            </form>

            <div class="text-center mt-4 pt-3 border-top pb-1">
                <span class="small text-muted">Chưa có tài khoản?</span>
                <a href="<?php echo BASE_URL; ?>/auth/register.php"
                    class="text-decoration-none fw-semibold text-brand small">Đăng ký mới</a>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>