<?php
// admin/index.php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: /tsdhhl26/admin/login.php');
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
    <title>Bảng Điều Khiển Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/tsdhhl26/assets/css/public.css">
    <style>
        :root { --brand-color: #1A3A6E; --sidebar-bg: #1A3A6E; }
        body { background-color: #f7f9fc; font-family: 'Inter', sans-serif; }
        .sidebar { background-color: var(--sidebar-bg); min-height: 100vh; padding-top: 25px; }
        .sidebar a { color: #cbd5e1; text-decoration: none; padding: 12px 24px; display: block; border-left: 3px solid transparent; font-weight: 500; }
        .sidebar a:hover, .sidebar a.active { background-color: rgba(255,255,255,0.05); color: #fff; border-left-color: #3b82f6; }
        .stat-card { border: none; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-left: 4px solid var(--brand-color); }
    </style>
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="container-fluid">

    <div class="row">
        <!-- Mobile Overlay -->
        <div class="sidebar-mobile-overlay" id="sidebarOverlay"></div>

        <!-- Sidebar -->
        <div class="col-md-2 sidebar sidebar-offcanvas d-none d-md-block px-0" id="mainSidebar">
            <div class="d-flex justify-content-between align-items-center mb-4 px-3">
                <h5 class="text-white mb-0 text-center w-100">ADMIN PORTAL</h5>
                <button class="btn btn-sm text-white d-md-none p-0" id="closeSidebarBtn">
                    <i class="bi bi-x-lg fs-4"></i>
                </button>
            </div>
            <a href="/tsdhhl26/admin/index.php" class="active">Bảng điều khiển</a>
            <a href="/tsdhhl26/admin/admission_settings.php">Cấu hình Đợt/Ngành</a>
            <a href="/tsdhhl26/admin/applications.php">Quản lý Hồ sơ</a>
            <a href="/tsdhhl26/admin/documents.php">Tài liệu tải lên</a>
            <a href="/tsdhhl26/admin/users.php">Quản lý Thí sinh</a>
            <hr class="text-secondary mx-3">
            <a href="/tsdhhl26/admin/logout.php" class="text-danger">Đăng xuất</a>
        </div>

        <!-- Main Content -->
        <div class="col-md-10 p-5">
            <h3 class="fw-bold mb-4">Tổng quan Hệ Thống Tuyển Sinh</h3>
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="card stat-card p-4 h-100">
                        <div class="text-muted small fw-bold text-uppercase mb-1">TỔNG HỒ SƠ ĐÃ NỘP</div>
                        <div class="fs-2 fw-bold text-dark"><?php echo $totalApps; ?></div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card stat-card p-4 h-100">
                        <div class="text-muted small fw-bold text-uppercase mb-1">TÀI KHOẢN THÍ SINH</div>
                        <div class="fs-2 fw-bold text-dark"><?php echo $totalUsers; ?></div>
                    </div>
                </div>
            </div>
            
            <div class="mt-4 p-4 bg-white rounded card mb-4">
                <h5 class="fw-bold mb-3">Thông báo từ hệ thống</h5>
                <p class="text-muted mb-0">Chào mừng Quản trị viên. Hãy chọn các danh mục bên trái để quản lý Đợt tuyển sinh, Ngành đào tạo và Hồ sơ ứng viên.</p>
            </div>
        </div>
    </div>
</div><!-- /container-fluid -->
<?php include __DIR__ . '/../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Off-canvas sidebar logic
    document.addEventListener('DOMContentLoaded', () => {
        const toggleBtn = document.getElementById('sidebarToggleBtn');
        const closeBtn = document.getElementById('closeSidebarBtn');
        const sidebar = document.getElementById('mainSidebar');
        const overlay = document.getElementById('sidebarOverlay');

        if(toggleBtn && sidebar && overlay) {
            const toggleMenu = () => {
                sidebar.classList.toggle('open');
                overlay.classList.toggle('show');
                document.body.style.overflow = sidebar.classList.contains('open') ? 'hidden' : '';
            };

            toggleBtn.addEventListener('click', toggleMenu);
            if(closeBtn) closeBtn.addEventListener('click', toggleMenu);
            overlay.addEventListener('click', toggleMenu);
        }
    });
</script>
</body>
</html>

