<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Nút Toggle Sidebar (Chỉ hiển thị trên Mobile) -->
<button class="btn btn-brand d-md-none position-fixed top-0 start-0 m-3 z-3 shadow-sm rounded-circle sidebar-toggle-btn" 
        type="button" 
        data-bs-toggle="offcanvas" 
        data-bs-target="#adminSidebarMobile" 
        aria-controls="adminSidebarMobile"
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
        <a href="/tsdhhl26/admin/index.php" class="rounded-3 <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2 me-2"></i> Bảng điều khiển
        </a>
        <a href="/tsdhhl26/admin/admission_settings.php" class="rounded-3 <?php echo ($current_page == 'admission_settings.php') ? 'active' : ''; ?>">
            <i class="bi bi-gear me-2"></i> Cấu hình Đợt/Ngành
        </a>
        <a href="/tsdhhl26/admin/applications.php" class="rounded-3 <?php echo ($current_page == 'applications.php') ? 'active' : ''; ?>">
            <i class="bi bi-file-earmark-text me-2"></i> Quản lý Hồ sơ
        </a>
        <a href="/tsdhhl26/admin/documents.php" class="rounded-3 <?php echo ($current_page == 'documents.php') ? 'active' : ''; ?>">
            <i class="bi bi-cloud-arrow-up me-2"></i> Tài liệu tải lên
        </a>
        <a href="/tsdhhl26/admin/users.php" class="rounded-3 <?php echo ($current_page == 'users.php') ? 'active' : ''; ?>">
            <i class="bi bi-people me-2"></i> Quản lý Thí sinh
        </a>
    </div>

</div>

<!-- Mobile Offcanvas Sidebar -->
<div class="offcanvas offcanvas-start sidebar-mobile" tabindex="-1" id="adminSidebarMobile" aria-labelledby="adminSidebarMobileLabel">
    <div class="offcanvas-header border-bottom border-light border-opacity-10 py-3">
        <h5 class="offcanvas-title text-white fw-bold" id="adminSidebarMobileLabel">ADMIN PORTAL</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column p-3 gap-1 sidebar-menu">
        <a href="/tsdhhl26/admin/index.php" class="rounded-3 <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2 me-2"></i> Bảng điều khiển
        </a>
        <a href="/tsdhhl26/admin/admission_settings.php" class="rounded-3 <?php echo ($current_page == 'admission_settings.php') ? 'active' : ''; ?>">
            <i class="bi bi-gear me-2"></i> Cấu hình Đợt/Ngành
        </a>
        <a href="/tsdhhl26/admin/applications.php" class="rounded-3 <?php echo ($current_page == 'applications.php') ? 'active' : ''; ?>">
            <i class="bi bi-file-earmark-text me-2"></i> Quản lý Hồ sơ
        </a>
        <a href="/tsdhhl26/admin/documents.php" class="rounded-3 <?php echo ($current_page == 'documents.php') ? 'active' : ''; ?>">
            <i class="bi bi-cloud-arrow-up me-2"></i> Tài liệu tải lên
        </a>
        <a href="/tsdhhl26/admin/users.php" class="rounded-3 <?php echo ($current_page == 'users.php') ? 'active' : ''; ?>">
            <i class="bi bi-people me-2"></i> Quản lý Thí sinh
        </a>

    </div>
</div>
