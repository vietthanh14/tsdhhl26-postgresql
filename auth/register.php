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
    <style>
        :root {
            --brand-color: #1A3A6E;
            --brand-hover: #12284c;
            --bg-color: #f7f9fc;
            --text-color: #333333;
            --border-radius: 4px;
        }
        body { 
            background-color: var(--bg-color); 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            min-height: 100vh; 
            padding: 40px 0; 
            font-family: 'Inter', sans-serif;
            color: var(--text-color);
        }
        .auth-card { 
            width: 100%; 
            max-width: 480px; 
            padding: 40px; 
            border-radius: var(--border-radius); 
            background: white; 
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .btn-brand {
            background-color: var(--brand-color);
            color: #ffffff;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 500;
            transition: all 0.2s ease;
        }
        .btn-brand:hover, .btn-brand:focus {
            background-color: var(--brand-hover);
            color: #ffffff;
        }
        .form-control {
            border-radius: var(--border-radius);
            border: 1px solid #cbd5e1;
            padding: 0.6rem 0.75rem;
        }
        .form-control:focus {
            border-color: var(--brand-color);
            box-shadow: 0 0 0 2px rgba(26, 58, 110, 0.15);
        }
        .form-label {
            font-size: 0.9rem;
            color: #475569;
            margin-bottom: 0.3rem;
        }
        .form-text { font-size: 0.8rem; color: #94a3b8; }
        .auth-title {
            color: var(--brand-color);
            letter-spacing: -0.5px;
            margin-bottom: 0.5rem;
        }
        h6 { color: var(--brand-color); font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .text-brand { color: var(--brand-color) !important; }
    </style>
</head>
<body>

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

</body>
</html>
