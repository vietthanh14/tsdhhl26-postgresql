<?php
require_once __DIR__ . '/../../config/supabase.php';

// candidate/includes/sidebar.php
$currentPage = basename($_SERVER['PHP_SELF']);
$currentLevelId = $_GET['level_id'] ?? null;

// Lấy danh sách Hệ Đào tạo từ cache (TTL 1 giờ)
require_once __DIR__ . '/../../lib/Cache.php';
$educationLevelsSidebar = Cache::remember('education_levels', 3600, function() use ($supabase) {
    $res = $supabase->select('education_levels', 'order=id.asc');
    return ($res['code'] == 200) ? $res['data'] : [];
});
?>
<!-- Mobile Overlay -->
<div class="sidebar-mobile-overlay" id="sidebarOverlay"></div>

<div class="col-md-2 sidebar sidebar-offcanvas d-none d-md-block" id="mainSidebar">
    <div class="d-flex justify-content-between align-items-center mb-4 px-3">
        <h5 class="text-white mb-0 text-center w-100">HALOU PORTAL</h5>
        <button class="btn btn-sm text-white d-md-none p-0" id="closeSidebarBtn">
            <i class="bi bi-x-lg fs-4"></i>
        </button>
    </div>

    <a href="<?php echo BASE_URL; ?>/candidate/index.php" class="<?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>">Bảng điều khiển</a>
    
    <div class="mt-2" style="padding: 12px 24px; color: #f59e0b; font-weight: 600; border-left: 3px solid #f59e0b; display: flex; align-items: center; gap: 8px; font-size: inherit;">
        <i class="bi bi-pencil-square" aria-hidden="true"></i> Đăng Ký Xét Tuyển
    </div>
    <?php
    $levelFileMap = [
        'đại học chính quy' => 'apply_university.php',
        'cao đẳng chính quy' => 'apply_college.php',
        'thạc sĩ' => 'apply_master.php',
        'trung cấp' => 'apply_vocational.php',
        'văn bằng 2, vừa làm vừa học' => 'apply_degree2.php',
    ];
    foreach ($educationLevelsSidebar as $lvl):
        $lvlKey  = mb_strtolower(trim($lvl['name']));
        $lvlFile = $levelFileMap[$lvlKey] ?? ('apply.php?level_id=' . $lvl['id']);
        $lvlBase = explode('?', $lvlFile)[0];
        $isActive = ($currentPage === $lvlBase) || ($currentPage === 'apply.php' && $currentLevelId == $lvl['id']);
    ?>
        <a href="<?php echo BASE_URL; ?>/candidate/<?php echo $lvlFile; ?>"
           class="<?php echo $isActive ? 'active' : ''; ?>"
           style="padding-left: 32px; font-size: 0.92rem; font-weight: 600;">
            <i class="bi bi-arrow-right-circle<?php echo $isActive ? '-fill' : ''; ?> me-1 text-warning"></i>
            Hệ <?php echo htmlspecialchars($lvl['name']); ?>
        </a>
    <?php endforeach; ?>
    
    <div class="mb-4"></div>
</div>

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
