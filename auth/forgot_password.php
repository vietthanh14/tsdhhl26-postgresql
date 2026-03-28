<?php
// auth/forgot_password.php
session_start();
require_once __DIR__ . '/../lib/SupabaseClient.php';
require_once __DIR__ . '/includes/auth_layout.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = strtolower(trim($_POST['username'] ?? ''));
    $identity_card = trim($_POST['identity_card'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($identity_card) || empty($new_password)) {
        $error = 'Vui lòng điền đầy đủ các thông tin.';
    } elseif (strlen($new_password) < 6) {
        $error = 'Mật khẩu phải chứa ít nhất 6 ký tự.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Mật khẩu nhập lại không khớp.';
    } else {
        try {
            $supabaseAdmin = new SupabaseClient('service');
            $profileResponse = $supabaseAdmin->select('user_profiles', "username=ilike.{$username}&identity_card=eq.{$identity_card}");

            if ($profileResponse['code'] == 200 && !empty($profileResponse['data'])) {
                $userId = $profileResponse['data'][0]['id'];
                $updateResponse = $supabaseAdmin->updateAuthUser($userId, ['password' => $new_password]);

                if (isset($updateResponse['data']['id'])) {
                    $success = 'Thiết lập Mật khẩu mới thành công! Hệ thống đã ghi nhận thiết lập của bạn.';
                } else {
                    $error = 'Hệ thống Auth từ chối cấp lại mật khẩu. Vui lòng liên hệ BQT.';
                }
            } else {
                $error = 'Tên đăng nhập hoặc Số CMND/CCCD không chính xác.';
            }
        } catch (Exception $e) {
            $error = 'Lỗi hệ thống: ' . $e->getMessage();
        }
    }
}

$extraStyles = 'h6 { color: var(--brand); font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; } .form-text { font-size: 0.8rem; color: #94a3b8; }';
authPageStart('Cấp lại Mật khẩu', $extraStyles);
?>

    <div class="auth-card">
        <div class="text-center mb-4 pb-2 border-bottom">
            <h3 class="auth-title fw-bold">CẤP LẠI MẬT KHẨU</h3>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 rounded-1 border-0 bg-danger text-white small"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success py-3 rounded-1 border-0 bg-success text-white small"><?php echo htmlspecialchars($success); ?></div>
            <div class="text-center mt-4">
                <a href="<?php echo BASE_URL; ?>/" class="btn btn-brand w-100 py-2 fw-semibold">QUAY LẠI ĐĂNG NHẬP</a>
            </div>
        <?php else: ?>

            <form method="POST" action="">
                <div class="alert bg-light border-0 small text-muted mb-4">
                    Để cấp lại mật khẩu, vui lòng nhập chính xác Tên đăng nhập và Số CMND/CCCD khớp với Hồ sơ đã đăng ký.
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Tên đăng nhập <span class="text-danger">*</span></label>
                    <input type="text" name="username" class="form-control" required placeholder="nguyenvana2024"
                        value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Số CMND / Thẻ Căn Cước <span class="text-danger">*</span></label>
                    <input type="text" name="identity_card" class="form-control" required placeholder="001234567890"
                        value="<?php echo htmlspecialchars($_POST['identity_card'] ?? ''); ?>">
                </div>

                <h6 class="mb-3 mt-4 pt-4 border-top">MẬT KHẨU MỚI</h6>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Mật khẩu thiết lập mới <span class="text-danger">*</span></label>
                    <input type="password" name="new_password" class="form-control" required placeholder="Ít nhất 6 ký tự">
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Nhập lại Mật khẩu <span class="text-danger">*</span></label>
                    <input type="password" name="confirm_password" class="form-control" required placeholder="Nhập lại mật khẩu mới">
                </div>

                <button type="submit" class="btn btn-brand w-100 py-2 mt-2">XÁC NHẬN CẤP LẠI</button>
            </form>

            <div class="text-center mt-4 pt-3 border-top pb-1">
                <a href="<?php echo BASE_URL; ?>/" class="text-decoration-none fw-semibold text-muted small">← Về trang Đăng nhập</a>
            </div>

        <?php endif; ?>
    </div>

<?php authPageEnd(); ?>