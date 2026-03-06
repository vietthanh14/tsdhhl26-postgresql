<?php
// candidate/includes/sidebar.php
$currentPage = basename($_SERVER['PHP_SELF']);
$currentLevelId = $_GET['level_id'] ?? null;

// Lấy danh sách Hệ Đào tạo để render menu (cần $supabase đã được khởi tạo ở file cha)
$levelsResSidebar = $supabase->select('education_levels', 'order=id.asc');
$educationLevelsSidebar = ($levelsResSidebar['code'] == 200) ? $levelsResSidebar['data'] : [];
?>
<div class="col-md-2 sidebar d-none d-md-block">
    <h5 class="text-white text-center mb-4">HALOU PORTAL</h5>
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
    
    <div class="text-secondary small fw-bold px-4 mt-4 mb-1 text-uppercase" style="letter-spacing: 0.5px; opacity: 0.7;">Tài Khoản</div>
    <a href="/tsdhhl26/candidate/documents.php" class="<?php echo ($currentPage == 'documents.php') ? 'active' : ''; ?>">Tài liệu của tôi</a>
    <a href="/tsdhhl26/candidate/profile.php" class="<?php echo ($currentPage == 'profile.php') ? 'active' : ''; ?>">Thông tin cá nhân</a>
    <hr class="text-secondary">
    <a href="/tsdhhl26/auth/logout.php" class="text-danger">Đăng xuất</a>
</div>
