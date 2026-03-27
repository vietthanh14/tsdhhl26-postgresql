<?php
require_once __DIR__ . '/../config/supabase.php';

// admin/index.php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ' . BASE_URL . '/admin/login.php');
    exit;
}
require_once __DIR__ . '/../lib/SupabaseClient.php';
$supabaseAdmin = new SupabaseClient('service');

// Lấy thông kê (Ví dụ số hồ sơ)
$appRes = $supabaseAdmin->select('applications', 'select=id', null);
$totalApps = ($appRes['code'] == 200) ? count($appRes['data']) : 0;

$userRes = $supabaseAdmin->select('user_profiles', 'select=id', null);
$totalUsers = ($userRes['code'] == 200) ? count($userRes['data']) : 0;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/logo.png">
    <title>Bảng Điều Khiển Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/public.css">
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="container-fluid p-0">
    <div class="row m-0">
        <!-- Sidebar -->
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <h3 class="fw-bold mb-4 text-brand">Tổng quan Hệ Thống Tuyển Sinh</h3>
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="card stat-card p-4 h-100">
                        <div class="text-muted small fw-bold text-uppercase mb-1">TỔNG HỒ SƠ ĐÃ NỘP</div>
                        <div class="fs-2 fw-bold text-brand"><?php echo $totalApps; ?></div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card stat-card p-4 h-100">
                        <div class="text-muted small fw-bold text-uppercase mb-1">TÀI KHOẢN THÍ SINH</div>
                        <div class="fs-2 fw-bold text-brand"><?php echo $totalUsers; ?></div>
                    </div>
                </div>
            </div>
            
            <div class="mt-4 p-4 bg-white rounded-3 shadow-sm border-0 mb-4">
                <h5 class="fw-bold mb-3 text-dark">Thông báo từ hệ thống</h5>
                <p class="text-muted mb-0">Chào mừng Quản trị viên. Hãy chọn các danh mục bên trái để quản lý Đợt tuyển sinh, Ngành đào tạo và Hồ sơ ứng viên.</p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>

