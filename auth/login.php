<?php
// auth/login.php
session_start();
require_once __DIR__ . '/../lib/SupabaseClient.php';

if (isset($_SESSION['user_id'])) {
    header('Location: /tsdhhl26/candidate/index.php');
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
                // Đăng nhập thành công, lưu Token và ID vào session
                $_SESSION['access_token'] = $response['data']['access_token'];
                $_SESSION['user_id'] = $response['data']['user']['id'];
                $_SESSION['email'] = $response['data']['user']['email'];
                
                header('Location: /tsdhhl26/candidate/index.php');
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .auth-card { width: 100%; max-width: 400px; padding: 30px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); background: white; }
    </style>
</head>
<body>

<div class="auth-card">
    <div class="text-center mb-4">
        <h2 class="text-primary fw-bold">ĐĂNG NHẬP</h2>
        <p class="text-muted">Cổng thông tin tuyển sinh</p>
    </div>

    <?php if($error): ?>
        <div class="alert alert-danger py-2"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-3">
            <label class="form-label">Tên đăng nhập</label>
            <input type="text" name="username" class="form-control" required placeholder="Nhập tên đăng nhập...">
        </div>
        <div class="mb-4">
            <label class="form-label">Mật khẩu</label>
            <input type="password" name="password" class="form-control" required placeholder="********">
        </div>
        <button type="submit" class="btn btn-primary w-100 fw-bold py-2">VÀO HỆ THỐNG</button>
    </form>
    
    <div class="text-center mt-4">
        <span>Chưa có tài khoản?</span> <a href="/tsdhhl26/auth/register.php" class="text-decoration-none fw-bold">Đăng ký mới</a>
    </div>
    <div class="text-center mt-2">
        <a href="/tsdhhl26/" class="text-muted text-decoration-none small">&larr; Quay lại trang chủ</a>
    </div>
</div>

</body>
</html>
