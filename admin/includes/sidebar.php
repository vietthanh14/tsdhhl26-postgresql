<?php
require_once __DIR__ . '/../../config/supabase.php';

$current_page = basename($_SERVER['PHP_SELF']);
$config_pages = ['manage_periods.php', 'manage_levels.php', 'manage_majors.php', 'manage_methods.php', 'manage_doc_types.php', 'admission_settings.php'];
$is_config_page = in_array($current_page, $config_pages);
?>
<!-- Nút Toggle Sidebar (Mobile) -->
<button class="btn btn-brand d-md-none position-fixed top-0 start-0 m-3 z-3 shadow-sm rounded-circle sidebar-toggle-btn" 
        type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebarMobile" aria-controls="adminSidebarMobile"
        style="width: 45px; height: 45px; line-height: 40px; padding: 0;">
    <i class="bi bi-list fs-4"></i>
</button>

<!-- Desktop Sidebar -->
<div class="col-md-2 sidebar d-none d-md-block px-0 shadow">
    <div class="sidebar-header text-center py-4 border-bottom border-light border-opacity-10 mb-3">
        <h5 class="text-white fw-bold mb-0">ADMIN PORTAL</h5>
        <small class="text-light text-opacity-75">Quản trị hệ thống</small>
    </div>
    
    <div class="sidebar-menu d-flex flex-column gap-1 px-2">
        <a href="<?php echo BASE_URL; ?>/admin/index.php" class="rounded-3 <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2 me-2"></i> Bảng điều khiển
        </a>

        <!-- Cấu hình - Collapsible -->
        <a href="#configSubmenu" class="rounded-3 d-flex justify-content-between align-items-center <?php echo $is_config_page ? 'active' : ''; ?>" 
           data-bs-toggle="collapse" aria-expanded="<?php echo $is_config_page ? 'true' : 'false'; ?>">
            <span><i class="bi bi-gear me-2"></i> Cấu hình</span>
            <i class="bi bi-chevron-down small"></i>
        </a>
        <div class="collapse <?php echo $is_config_page ? 'show' : ''; ?>" id="configSubmenu">
            <div class="d-flex flex-column gap-1 ps-3">
                <a href="<?php echo BASE_URL; ?>/admin/manage_periods.php" class="rounded-3 small <?php echo ($current_page == 'manage_periods.php') ? 'active' : ''; ?>">
                    <i class="bi bi-calendar-event me-2"></i> Đợt Tuyển Sinh
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/manage_levels.php" class="rounded-3 small <?php echo ($current_page == 'manage_levels.php') ? 'active' : ''; ?>">
                    <i class="bi bi-mortarboard me-2"></i> Hệ Đào Tạo
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/manage_majors.php" class="rounded-3 small <?php echo ($current_page == 'manage_majors.php') ? 'active' : ''; ?>">
                    <i class="bi bi-book me-2"></i> Ngành Học
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/manage_methods.php" class="rounded-3 small <?php echo ($current_page == 'manage_methods.php') ? 'active' : ''; ?>">
                    <i class="bi bi-list-check me-2"></i> Phương thức & Lệ phí
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/manage_doc_types.php" class="rounded-3 small <?php echo ($current_page == 'manage_doc_types.php') ? 'active' : ''; ?>">
                    <i class="bi bi-folder me-2"></i> Danh mục Tài liệu
                </a>
            </div>
        </div>

        <a href="<?php echo BASE_URL; ?>/admin/applications.php" class="rounded-3 <?php echo ($current_page == 'applications.php') ? 'active' : ''; ?>">
            <i class="bi bi-file-earmark-text me-2"></i> Quản lý Hồ sơ
        </a>
        <a href="<?php echo BASE_URL; ?>/admin/documents.php" class="rounded-3 <?php echo ($current_page == 'documents.php') ? 'active' : ''; ?>">
            <i class="bi bi-cloud-arrow-up me-2"></i> Tài liệu tải lên
        </a>
        <a href="<?php echo BASE_URL; ?>/admin/users.php" class="rounded-3 <?php echo ($current_page == 'users.php') ? 'active' : ''; ?>">
            <i class="bi bi-people me-2"></i> Quản lý Thí sinh
        </a>
    </div>

</div>

<!-- Mobile Offcanvas Sidebar -->
<div class="offcanvas offcanvas-start sidebar-mobile" tabindex="-1" id="adminSidebarMobile" aria-labelledby="adminSidebarMobileLabel"
     data-bs-theme="dark" style="background-color: #1A3A6E; color: #cbd5e1;">
    <div class="offcanvas-header border-bottom border-light border-opacity-10 py-3">
        <h5 class="offcanvas-title text-white fw-bold" id="adminSidebarMobileLabel">ADMIN PORTAL</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column p-3 gap-1 sidebar-menu">
        <a href="<?php echo BASE_URL; ?>/admin/index.php" class="rounded-3 <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2 me-2"></i> Bảng điều khiển
        </a>

        <!-- Cấu hình - Collapsible (Mobile) -->
        <a href="#configSubmenuMobile" class="rounded-3 d-flex justify-content-between align-items-center <?php echo $is_config_page ? 'active' : ''; ?>"
           data-bs-toggle="collapse" aria-expanded="<?php echo $is_config_page ? 'true' : 'false'; ?>">
            <span><i class="bi bi-gear me-2"></i> Cấu hình</span>
            <i class="bi bi-chevron-down small"></i>
        </a>
        <div class="collapse <?php echo $is_config_page ? 'show' : ''; ?>" id="configSubmenuMobile">
            <div class="d-flex flex-column gap-1 ps-3">
                <a href="<?php echo BASE_URL; ?>/admin/manage_periods.php" class="rounded-3 small <?php echo ($current_page == 'manage_periods.php') ? 'active' : ''; ?>">
                    <i class="bi bi-calendar-event me-2"></i> Đợt Tuyển Sinh
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/manage_levels.php" class="rounded-3 small <?php echo ($current_page == 'manage_levels.php') ? 'active' : ''; ?>">
                    <i class="bi bi-mortarboard me-2"></i> Hệ Đào Tạo
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/manage_majors.php" class="rounded-3 small <?php echo ($current_page == 'manage_majors.php') ? 'active' : ''; ?>">
                    <i class="bi bi-book me-2"></i> Ngành Học
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/manage_methods.php" class="rounded-3 small <?php echo ($current_page == 'manage_methods.php') ? 'active' : ''; ?>">
                    <i class="bi bi-list-check me-2"></i> Phương thức & Lệ phí
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/manage_doc_types.php" class="rounded-3 small <?php echo ($current_page == 'manage_doc_types.php') ? 'active' : ''; ?>">
                    <i class="bi bi-folder me-2"></i> Danh mục Tài liệu
                </a>
            </div>
        </div>

        <a href="<?php echo BASE_URL; ?>/admin/applications.php" class="rounded-3 <?php echo ($current_page == 'applications.php') ? 'active' : ''; ?>">
            <i class="bi bi-file-earmark-text me-2"></i> Quản lý Hồ sơ
        </a>
        <a href="<?php echo BASE_URL; ?>/admin/documents.php" class="rounded-3 <?php echo ($current_page == 'documents.php') ? 'active' : ''; ?>">
            <i class="bi bi-cloud-arrow-up me-2"></i> Tài liệu tải lên
        </a>
        <a href="<?php echo BASE_URL; ?>/admin/users.php" class="rounded-3 <?php echo ($current_page == 'users.php') ? 'active' : ''; ?>">
            <i class="bi bi-people me-2"></i> Quản lý Thí sinh
        </a>
    </div>
</div>

<!-- Kết nối nút Hamburger trong header chung với Offcanvas Admin -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.getElementById('sidebarToggleBtn');
        if (toggleBtn) {
            const newToggleBtn = toggleBtn.cloneNode(true);
            toggleBtn.parentNode.replaceChild(newToggleBtn, toggleBtn);
            newToggleBtn.setAttribute('data-bs-toggle', 'offcanvas');
            newToggleBtn.setAttribute('data-bs-target', '#adminSidebarMobile');
            newToggleBtn.setAttribute('aria-controls', 'adminSidebarMobile');
        }
    });
</script>
