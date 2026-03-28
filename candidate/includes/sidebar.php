<?php
require_once __DIR__ . '/../../config/supabase.php';

$currentPage = basename($_SERVER['PHP_SELF']);
$currentLevelId = $_GET['level_id'] ?? null;

// Lấy danh sách Hệ Đào tạo từ cache (TTL 1 giờ)
require_once __DIR__ . '/../../lib/Cache.php';
$educationLevelsSidebar = Cache::remember('education_levels', 3600, function() {
    global $supabase;
    if (!isset($supabase)) {
        require_once __DIR__ . '/../../lib/SupabaseClient.php';
        $supabase = new SupabaseClient('anon');
    }
    $res = $supabase->select('education_levels', 'order=id.asc');
    return ($res['code'] == 200) ? $res['data'] : [];
});

// Map education_level ID → wrapper file (dùng ID thay vì tên → không bị ảnh hưởng khi đổi tên hệ)
$levelFileMap = [
    1 => 'apply_university.php',  // Đại học chính quy
    2 => 'apply_college.php',     // Cao đẳng chính quy
    3 => 'apply_master.php',      // Thạc sĩ
    4 => 'apply_vocational.php',  // Trung cấp
    5 => 'apply_degree2.php',     // Văn bằng 2
];
?>

<!-- Desktop Sidebar -->
<div class="col-md-2 sidebar d-none d-md-block px-0 shadow">
    <div class="sidebar-header text-center py-4 border-bottom border-light border-opacity-10 mb-3">
        <h5 class="text-white fw-bold mb-0">CỔNG ĐĂNG KÝ</h5>
        <small class="text-light text-opacity-75">Dành cho Thí sinh</small>
    </div>
    
    <div class="sidebar-menu d-flex flex-column gap-1 px-2">
        <a href="<?php echo BASE_URL; ?>/candidate/index.php" class="rounded-3 <?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2 me-2"></i> Bảng điều khiển
        </a>
        <a href="<?php echo BASE_URL; ?>/candidate/profile.php" class="rounded-3 <?php echo ($currentPage == 'profile.php') ? 'active' : ''; ?>">
            <i class="bi bi-person me-2"></i> Thông tin cá nhân
        </a>
        
        <!-- Cấu hình - Collapsible -->
        <a href="#applySubmenu" class="rounded-3 d-flex justify-content-between align-items-center <?php echo (strpos($currentPage, 'apply') !== false) ? 'active' : ''; ?>" 
           data-bs-toggle="collapse" aria-expanded="<?php echo (strpos($currentPage, 'apply') !== false) ? 'true' : 'false'; ?>">
            <span><i class="bi bi-pencil-square me-2"></i> Đăng Ký Xét Tuyển</span>
            <i class="bi bi-chevron-down small"></i>
        </a>
        <div class="collapse <?php echo (strpos($currentPage, 'apply') !== false) || true ? 'show' : ''; ?>" id="applySubmenu">
            <div class="d-flex flex-column gap-1 ps-3">
                <?php foreach ($educationLevelsSidebar as $lvl):
                    $lvlFile = $levelFileMap[$lvl['id']] ?? ('apply.php?level_id=' . $lvl['id']);
                    $lvlBase = explode('?', $lvlFile)[0];
                    $isActive = ($currentPage === $lvlBase) || ($currentPage === 'apply.php' && $currentLevelId == $lvl['id']);
                ?>
                    <a href="<?php echo BASE_URL; ?>/candidate/<?php echo $lvlFile; ?>"
                       class="rounded-3 small <?php echo $isActive ? 'active' : ''; ?>">
                        <i class="bi bi-arrow-right-circle<?php echo $isActive ? '-fill' : ''; ?> me-2 text-warning"></i>
                        Hệ <?php echo htmlspecialchars($lvl['name']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Mobile Offcanvas Sidebar -->
<div class="offcanvas offcanvas-start sidebar-mobile" tabindex="-1" id="candidateSidebarMobile" aria-labelledby="candidateSidebarMobileLabel"
     data-bs-theme="dark" style="background-color: #1A3A6E; color: #cbd5e1;">
    <div class="offcanvas-header border-bottom border-light border-opacity-10 py-3">
        <h5 class="offcanvas-title text-white fw-bold" id="candidateSidebarMobileLabel">CỔNG ĐĂNG KÝ</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column p-3 gap-1 sidebar-menu">
        <a href="<?php echo BASE_URL; ?>/candidate/index.php" class="rounded-3 <?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2 me-2"></i> Bảng điều khiển
        </a>
        <a href="<?php echo BASE_URL; ?>/candidate/profile.php" class="rounded-3 <?php echo ($currentPage == 'profile.php') ? 'active' : ''; ?>">
            <i class="bi bi-person me-2"></i> Thông tin cá nhân
        </a>

        <!-- Cấu hình - Collapsible (Mobile) -->
        <a href="#applySubmenuMobile" class="rounded-3 d-flex justify-content-between align-items-center <?php echo (strpos($currentPage, 'apply') !== false) ? 'active' : ''; ?>"
           data-bs-toggle="collapse" aria-expanded="<?php echo (strpos($currentPage, 'apply') !== false) ? 'true' : 'false'; ?>">
            <span><i class="bi bi-pencil-square me-2"></i> Đăng Ký Xét Tuyển</span>
            <i class="bi bi-chevron-down small"></i>
        </a>
        <div class="collapse <?php echo (strpos($currentPage, 'apply') !== false) || true ? 'show' : ''; ?>" id="applySubmenuMobile">
            <div class="d-flex flex-column gap-1 ps-3">
                <?php foreach ($educationLevelsSidebar as $lvl):
                    $lvlFile = $levelFileMap[$lvl['id']] ?? ('apply.php?level_id=' . $lvl['id']);
                    $lvlBase = explode('?', $lvlFile)[0];
                    $isActive = ($currentPage === $lvlBase) || ($currentPage === 'apply.php' && $currentLevelId == $lvl['id']);
                ?>
                    <a href="<?php echo BASE_URL; ?>/candidate/<?php echo $lvlFile; ?>"
                       class="rounded-3 small <?php echo $isActive ? 'active' : ''; ?>">
                        <i class="bi bi-arrow-right-circle<?php echo $isActive ? '-fill' : ''; ?> me-2 text-warning"></i>
                        Hệ <?php echo htmlspecialchars($lvl['name']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
    // Link the general header hamburger icon to this offcanvas, since the header might already have one lacking mapping
    document.addEventListener('DOMContentLoaded', () => {
        const toggleBtn = document.getElementById('sidebarToggleBtn');
        if (toggleBtn) {
            // Remove old onclick/event listeners safely
            const newToggleBtn = toggleBtn.cloneNode(true);
            toggleBtn.parentNode.replaceChild(newToggleBtn, toggleBtn);
            
            // Map it to Bootstrap offcanvas
            newToggleBtn.setAttribute('data-bs-toggle', 'offcanvas');
            newToggleBtn.setAttribute('data-bs-target', '#candidateSidebarMobile');
            newToggleBtn.setAttribute('aria-controls', 'candidateSidebarMobile');
        }
    });
</script>
