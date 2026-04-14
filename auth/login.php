<?php
// auth/login.php
session_start();
require_once __DIR__ . '/../lib/DatabaseClient.php';
require_once __DIR__ . '/includes/auth_layout.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/candidate/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../lib/RateLimiter.php';
    if (!RateLimiter::checkSessionLimit('login_auth', 10, 1800)) {
        $error = 'Bạn đã thử đăng nhập quá 10 lần trong 30 phút. Vui lòng thử lại sau để bảo mật tài khoản.';
    } else {
    $username = strtolower(trim($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        try {
            $fake_email = $username . '@halou.system';
            $supabase = new DatabaseClient('anon');
            $response = $supabase->signIn($fake_email, $password);

            if ($response['code'] == 200 && isset($response['data']['access_token'])) {
                session_regenerate_id(true);
                unset($_SESSION['admin_logged_in']);
                $_SESSION['access_token'] = $response['data']['access_token'];
                $_SESSION['user_id'] = $response['data']['user']['id'];
                $_SESSION['email'] = $response['data']['user']['email'];

                header('Location: ' . BASE_URL . '/candidate/index.php');
                exit;
            } else {
                $error = $response['data']['error_description'] ?? 'Tài khoản hoặc mật khẩu không chính xác.';
            }
        } catch (Exception $e) {
            $error = "Lỗi hệ thống: " . $e->getMessage();
        }
    } else {
        $error = 'Vui lòng nhập đầy đủ Tên đăng nhập và Mật khẩu.';
    }
    }
}

authPageStart('Đăng nhập');
?>

    <div class="auth-card">
        <div class="text-center mb-4">
            <h3 class="auth-title fw-bold">ĐĂNG NHẬP</h3>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 rounded-1 border-0 bg-danger text-white small" role="alert"
                aria-live="polite"><?php echo htmlspecialchars($error); ?></div>
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

<?php authPageEnd(); ?>