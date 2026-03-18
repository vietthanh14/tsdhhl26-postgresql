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
    $username       = strtolower(trim($_POST['username'] ?? ''));
    $password       = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $full_name      = trim($_POST['full_name'] ?? '');
    $date_of_birth  = trim($_POST['date_of_birth'] ?? '');
    $contact_email  = trim($_POST['contact_email'] ?? '');
    $phone_number   = trim($_POST['phone_number'] ?? '');
    $identity_card  = trim($_POST['identity_card'] ?? '');

    if (!$username) {
        $error = 'Vui lòng nhập tên đăng nhập.';
    } elseif (!preg_match('/^[a-z0-9_]{3,30}$/', $username)) {
        $error = 'Tên đăng nhập chỉ gồm chữ thường, số và dấu gạch dưới (3-30 ký tự).';
    } elseif (!$full_name) {
        $error = 'Vui lòng nhập họ và tên.';
    } elseif (!$date_of_birth) {
        $error = 'Vui lòng nhập ngày sinh.';
    } elseif (!$contact_email || !filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Địa chỉ email không hợp lệ.';
    } elseif (!$phone_number || !preg_match('/^[0-9]{9,11}$/', $phone_number)) {
        $error = 'Số điện thoại không hợp lệ (9-11 chữ số).';
    } elseif (!$identity_card || !preg_match('/^[0-9]{9,12}$/', $identity_card)) {
        $error = 'Số căn cước công dân không hợp lệ (9-12 chữ số).';
    } elseif ($password !== $password_confirm) {
        $error = 'Mật khẩu xác nhận không khớp.';
    } elseif (strlen($password) < 6) {
        $error = 'Mật khẩu phải chứa ít nhất 6 ký tự.';
    } else {
        try {
            // Kiểm tra trùng username trước khi tạo tài khoản Supabase
            $supabaseAdmin = new SupabaseClient('service');
            $chkUsername = $supabaseAdmin->select('user_profiles', "username=eq.{$username}&select=id");
            if ($chkUsername['code'] == 200 && !empty($chkUsername['data'])) {
                $error = 'Tên đăng nhập này đã được sử dụng. Vui lòng chọn tên khác.';
            } else {
                // Nối Username thành email ảo để đăng ký qua hệ thống của Supabase
                $fake_email = $username . '@halou.system';

                $supabase = new SupabaseClient('anon');
                $response = $supabase->signUp($fake_email, $password);

                if ($response['code'] == 200 && isset($response['data']['user'])) {
                    $user_id = $response['data']['user']['id'];

                    $profileData = [
                        'id'            => $user_id,
                        'username'      => $username,
                        'full_name'     => $full_name,
                        'date_of_birth' => $date_of_birth,
                        'contact_email' => $contact_email,
                        'phone_number'  => $phone_number,
                        'identity_card' => $identity_card,
                    ];

                    $profileResponse = $supabaseAdmin->insert('user_profiles', $profileData);

                    if ($profileResponse['code'] == 201 || $profileResponse['code'] == 200) {
                        $success = 'Đăng ký tài khoản thành công! Bạn có thể sử dụng <strong>Tên đăng nhập</strong> và Mật khẩu vừa tạo để đăng nhập.';
                    } else {
                        $error = "Tạo tài khoản thành công, nhưng lỗi lưu hồ sơ. Vui lòng liên hệ quản trị viên.";
                    }
                } else {
                    $supabaseError = $response['data']['error_description'] ?? $response['data']['msg'] ?? 'Lỗi không xác định.';
                    if (strpos(strtolower($supabaseError), 'already registered') !== false || strpos(strtolower($supabaseError), 'exists') !== false) {
                        $error = 'Tên đăng nhập này đã được sử dụng trên hệ thống.';
                    } else {
                        $error = 'Lỗi đăng ký: ' . $supabaseError;
                    }
                }
            }
        } catch (Exception $e) {
            $error = "Lỗi hệ thống: " . $e->getMessage();
        }
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
        <div class="alert alert-danger py-2 rounded-1 border-0 bg-danger text-white small" role="alert" aria-live="polite"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if($success): ?>
        <div class="alert alert-success py-3 rounded-1 border-0 bg-success text-white small" role="alert" aria-live="polite"><?php echo $success; ?></div>
        <div class="text-center mt-4">
            <a href="/tsdhhl26/" class="btn btn-brand px-4 py-2">ĐẾN TRANG ĐĂNG NHẬP</a>
        </div>
    <?php else: ?>

    <form method="POST" action="">
        <h6 class="mb-3 mt-2">THÔNG TIN CÁ NHÂN</h6>

        <div class="mb-3">
            <label for="regFullName" class="form-label fw-semibold">Họ và tên <span class="text-danger">*</span></label>
            <input type="text" name="full_name" id="regFullName" class="form-control" required placeholder="Nguyễn Văn A" autocomplete="name" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
        </div>

        <div class="row g-3 mb-3">
            <div class="col-sm-6">
                <label for="regDob" class="form-label fw-semibold">Ngày sinh <span class="text-danger">*</span></label>
                <input type="date" name="date_of_birth" id="regDob" class="form-control" required autocomplete="bday" value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>">
            </div>
            <div class="col-sm-6">
                <label for="regPhone" class="form-label fw-semibold">Số điện thoại <span class="text-danger">*</span></label>
                <input type="tel" name="phone_number" id="regPhone" class="form-control" required placeholder="0912345678" autocomplete="tel" value="<?php echo htmlspecialchars($_POST['phone_number'] ?? ''); ?>">
            </div>
        </div>

        <div class="mb-3">
            <label for="regEmail" class="form-label fw-semibold">Email liên lạc <span class="text-danger">*</span></label>
            <input type="email" name="contact_email" id="regEmail" class="form-control" required placeholder="example@gmail.com" autocomplete="email" spellcheck="false" value="<?php echo htmlspecialchars($_POST['contact_email'] ?? ''); ?>">
        </div>

        <div class="mb-4">
            <label for="regCccd" class="form-label fw-semibold">Số căn cước công dân <span class="text-danger">*</span></label>
            <input type="text" name="identity_card" id="regCccd" class="form-control" required placeholder="012345678901" maxlength="12" autocomplete="off" inputmode="numeric" value="<?php echo htmlspecialchars($_POST['identity_card'] ?? ''); ?>">
            <div class="form-text">Nhập số CCCD 12 chữ số</div>
        </div>

        <h6 class="mb-3 mt-2">THÔNG TIN TÀI KHOẢN</h6>

        <div class="mb-3">
            <label for="regUsername" class="form-label fw-semibold">Tên đăng nhập <span class="text-danger">*</span></label>
            <input type="text" name="username" id="regUsername" class="form-control" required placeholder="nhapchuongtrinh" autocomplete="username" spellcheck="false" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            <div class="form-text">Viết liền không dấu. Ví dụ: nguyenvana2024</div>
        </div>

        <div class="mb-3">
            <label for="regPassword" class="form-label fw-semibold">Mật khẩu <span class="text-danger">*</span></label>
            <input type="password" name="password" id="regPassword" class="form-control" required placeholder="Ít nhất 6 ký tự" autocomplete="new-password">
        </div>

        <div class="mb-4">
            <label for="regPasswordConfirm" class="form-label fw-semibold">Nhập lại Mật khẩu <span class="text-danger">*</span></label>
            <input type="password" name="password_confirm" id="regPasswordConfirm" class="form-control" required placeholder="Nhập lại mật khẩu…" autocomplete="new-password">
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
