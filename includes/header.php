<?php
// includes/header.php — Header chung cho các trang public
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$header_user_name = 'Thí sinh';
if (isset($_SESSION['user_id'])) {
    if (isset($profile) && isset($profile['full_name'])) {
        $header_user_name = $profile['full_name'];
    } else {
        require_once __DIR__ . '/../lib/DatabaseClient.php';
        $sb_header = new DatabaseClient('anon');
        $header_token = $_SESSION['access_token'] ?? null;
        $p_header = $sb_header->select('user_profiles', "id=eq.".$_SESSION['user_id'], $header_token);
        if ($p_header['code'] == 200 && !empty($p_header['data'])) {
            $header_user_name = $p_header['data'][0]['full_name'] ?? 'Thí sinh';
        }
    }
}
?>
<header class="site-header">
    <div class="container-fluid px-3 px-md-4">
        <div class="d-flex align-items-center justify-content-between py-2">
            <!-- Left side items: Hamburger + Branding -->
            <div class="d-flex align-items-center gap-2">
                <!-- Hamburger Menu (Mobile Only) -->
                <button class="btn text-white d-md-none p-0 border-0" id="sidebarToggleBtn" aria-label="Mở menu" style="background: transparent;">
                    <i class="bi bi-list fs-1" style="line-height: 1;"></i>
                </button>

                <!-- Logo + Tên trường -->
                <a href="<?php echo BASE_URL; ?>/" class="header-brand d-flex align-items-center gap-2 text-decoration-none">
                    <img src="<?php echo BASE_URL; ?>/assets/logo.png" alt="Logo ĐH Hạ Long" width="40" height="40" class="d-none d-sm-block" onerror="this.style.display='none'">
                    <div class="d-flex flex-column justify-content-center">
                        <div class="fw-bold text-white mb-0" style="font-size:clamp(0.9rem,2.5vw,1.1rem); letter-spacing:0.5px; line-height:1.2;">TRƯỜNG ĐẠI HỌC HẠ LONG</div>
                        <div class="text-white-50 d-none d-sm-block" style="font-size:0.75rem; letter-spacing:0.5px; line-height:1.2;">Halong University</div>
                    </div>
                </a>
            </div>

            <!-- Right side: Nav links + User dropdown -->
            <div class="d-flex align-items-center gap-3 gap-md-4">
                <!-- Nav links -->
                <a href="<?php echo BASE_URL; ?>/<?php echo (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) ? 'admin/index.php' : ''; ?>" class="text-white text-decoration-none fw-medium d-flex align-items-center gap-1 header-nav-link" aria-label="Trang chủ">
                    <i class="bi bi-house-door-fill fs-5" style="line-height:1;"></i> <span class="d-none d-md-inline">Trang chủ</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/search.php" class="text-white text-decoration-none fw-medium d-flex align-items-center gap-1 header-nav-link" aria-label="Tra cứu">
                    <i class="bi bi-search fs-5" style="line-height:1;"></i> <span class="d-none d-md-inline">Tra cứu</span>
                </a>

                <!-- User dropdown / Auth buttons -->
                <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true): ?>
                    <div class="dropdown">
                        <button type="button" class="text-white fw-medium dropdown-toggle d-flex align-items-center gap-2 btn border-0 bg-transparent p-0 m-0" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-shield-lock-fill text-warning fs-5" style="line-height:1;"></i>
                            <span class="d-none d-sm-inline">Xin chào, <span class="fw-bold text-warning">Quản trị viên</span></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" style="border-radius: 8px;">
                            <li>
                                <a class="dropdown-item py-2 text-danger d-flex align-items-center gap-2" href="<?php echo BASE_URL; ?>/admin/logout.php">
                                    <i class="bi bi-box-arrow-right"></i> Đăng xuất
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php elseif (isset($_SESSION['user_id'])): ?>
                    <div class="dropdown">
                        <button type="button" class="text-white fw-medium dropdown-toggle d-flex align-items-center gap-1 btn border-0 bg-transparent p-0 m-0" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle fs-4" style="line-height:1;"></i>
                            <span class="d-none d-md-inline ms-1">Xin chào, <span class="fw-bold text-warning"><?php echo htmlspecialchars($header_user_name); ?></span></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-3" style="border-radius: 8px;">
                            <li class="dropdown-item-text text-muted small d-md-none fw-semibold px-3 py-2">
                                <?php echo htmlspecialchars($header_user_name); ?>
                            </li>
                            <li class="d-md-none"><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="<?php echo BASE_URL; ?>/candidate/profile.php">
                                    <i class="bi bi-person text-secondary"></i> Thông tin cá nhân
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item py-2 text-danger d-flex align-items-center gap-2" href="<?php echo BASE_URL; ?>/auth/logout.php">
                                    <i class="bi bi-box-arrow-right"></i> Đăng xuất
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php else: ?>
                    <div class="d-flex align-items-center gap-2">
                        <a href="<?php echo BASE_URL; ?>/auth/login.php" class="text-white text-decoration-none fw-bold header-nav-link small">Đăng nhập</a>
                        <a href="<?php echo BASE_URL; ?>/auth/register.php" class="btn fw-bold px-3 shadow-sm" style="background-color: #f59e0b; color: #fff; border: none; font-size:.85rem; padding: 0.4rem 0.8rem;">Đăng ký</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>
