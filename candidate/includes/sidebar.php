<?php
// candidate/includes/sidebar.php
$currentPage = basename($_SERVER['PHP_SELF']);
$currentLevelId = $_GET['level_id'] ?? null;

// Lấy danh sách Hệ Đào tạo để render menu (cần $supabase đã được khởi tạo ở file cha)
$levelsResSidebar = $supabase->select('education_levels', 'order=id.asc');
$educationLevelsSidebar = ($levelsResSidebar['code'] == 200) ? $levelsResSidebar['data'] : [];
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

    <a href="/tsdhhl26/candidate/index.php" class="<?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>">Bảng điều khiển</a>
    
    <div class="text-secondary small fw-bold px-4 mt-3 mb-1 text-uppercase" style="letter-spacing: 0.5px; opacity: 0.7;">Đăng Ký Xét Tuyển</div>
    <?php
    // Mapping tên hệ (chữ thường) → file form riêng
    // Thêm hệ mới vào đây khi cần tạo form riêng biệt
    $levelFileMap = [
        'đại học chính quy' => 'apply_university.php',
        'cao đẳng chính quy' => 'apply_college.php',
    ];
    foreach ($educationLevelsSidebar as $lvl):
        $lvlKey  = mb_strtolower(trim($lvl['name']));
        $lvlFile = $levelFileMap[$lvlKey] ?? ('apply.php?level_id=' . $lvl['id']);
        $lvlBase = explode('?', $lvlFile)[0];
        $isActive = ($currentPage === $lvlBase) || ($currentPage === 'apply.php' && $currentLevelId == $lvl['id']);
    ?>
        <a href="/tsdhhl26/candidate/<?php echo $lvlFile; ?>"
           class="<?php echo $isActive ? 'active' : ''; ?>"
           style="padding-left: 32px; font-size: 0.95rem;">
            ▶ Hệ <?php echo htmlspecialchars($lvl['name']); ?>
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
