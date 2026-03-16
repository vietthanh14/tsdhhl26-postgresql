<?php
// auth/register.php
session_start();
require_once __DIR__ . '/../lib/SupabaseClient.php';

if (isset($_SESSION['user_id'])) {
    header('Location: /tsdhhl26/candidate/index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = strtolower(trim($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $full_name = 'Chưa cập nhật'; // Sẽ cập nhật sau ở Profile

    if ($password !== $password_confirm) {
        $error = 'Mật khẩu xác nhận không khớp.';
    } elseif (strlen($password) < 6) {
        $error = 'Mật khẩu phải chứa ít nhất 6 ký tự.';
    } elseif ($username && $password) {
        try {
            // Nối Username thành email ảo để đăng ký qua hệ thống của Supabase
            $fake_email = $username . '@halou.system';
            
            $supabase = new SupabaseClient('anon');
            $response = $supabase->signUp($fake_email, $password);

            if ($response['code'] == 200 && isset($response['data']['user'])) {
                $user_id = $response['data']['user']['id'];
                
                // Chèn vào bảng user_profiles
                $supabaseAdmin = new SupabaseClient('service');
                
                $profileData = [
                    'id' => $user_id,
                    'full_name' => $full_name,
                    'username' => $username
                ];
                
                $profileResponse = $supabaseAdmin->insert('user_profiles', $profileData);
                
                if ($profileResponse['code'] == 201 || $profileResponse['code'] == 200) {
                    $success = 'Đăng ký tài khoản thành công! Bạn có thể sử dụng **Tên đăng nhập** và Mật khẩu vừa tạo để đăng nhập.';
                } else {
                    $error = "Tạo tài khoản thành công, nhưng lỗi lưu hồ sơ: " . json_encode($profileResponse['data']);
                }
            } else {
                // Xử lý báo lỗi chính xác từ Supabase (nếu có) thay vì mặc định là trùng username
                $supabaseError = $response['data']['error_description'] ?? $response['data']['msg'] ?? 'Lỗi không xác định.';
                if (strpos(strtolower($supabaseError), 'already registered') !== false || strpos(strtolower($supabaseError), 'exists') !== false) {
                    $error = 'Tên đăng nhập này đã được sử dụng trên hệ thống.';
                } else {
                    $error = 'Lỗi đăng ký: ' . $supabaseError;
                }
            }
        } catch (Exception $e) {
            $error = "Lỗi hệ thống: " . $e->getMessage();
        }
    } else {
        $error = 'Vui lòng điền đầy đủ các thông tin bắt buộc.';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký Tài khoản - Tuyển sinh Đại học Hạ Long</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/tsdhhl26/assets/css/public.css">
    <style>
        h6 { color: var(--brand); font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-text { font-size: 0.8rem; color: #94a3b8; }
    </style>
</head>
<body>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="page-wrapper">
<div class="auth-card">
    <div class="text-center mb-4 pb-2 border-bottom">
        <h3 class="auth-title fw-bold">MỞ HỒ SƠ</h3>
        <p class="text-muted small">Đăng ký tài khoản Tuyển sinh mới</p>
    </div>

    <?php if($error): ?>
        <div class="alert alert-danger py-2 rounded-1 border-0 bg-danger text-white small"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if($success): ?>
        <div class="alert alert-success py-3 rounded-1 border-0 bg-success text-white small"><?php echo $success; ?></div>
        <div class="text-center mt-4">
            <a href="/tsdhhl26/" class="btn btn-brand px-4 py-2">ĐẾN TRANG ĐĂNG NHẬP</a>
        </div>
    <?php else: ?>

    <form method="POST" action="">
        <h6 class="mb-3 mt-2">THÔNG TIN TÀI KHOẢN</h6>
        
        <div class="mb-3">
            <label class="form-label fw-semibold">Tên đăng nhập <span class="text-danger">*</span></label>
            <input type="text" name="username" class="form-control" required placeholder="nhapchuongtrinh" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            <div class="form-text">Viết liền không dấu. Ví dụ: nguyenvana2024</div>
        </div>
        
        <div class="mb-3">
            <label class="form-label fw-semibold">Mật khẩu <span class="text-danger">*</span></label>
            <input type="password" name="password" class="form-control" required placeholder="Ít nhất 6 ký tự">
        </div>
        
        <div class="mb-4">
            <label class="form-label fw-semibold">Nhập lại Mật khẩu <span class="text-danger">*</span></label>
            <input type="password" name="password_confirm" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-brand w-100 py-2 mt-2">ĐĂNG KÝ TÀI KHOẢN</button>
    </form>
    
    <div class="text-center mt-4 pt-3 border-top pb-1">
        <span class="small text-muted">Đã có tài khoản?</span> 
        <a href="/tsdhhl26/" class="text-decoration-none fw-semibold text-brand small">Đăng nhập ngay</a>
    </div>
    
    <?php endif; ?>
</div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
