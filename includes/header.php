<?php
// includes/header.php — Header chung cho các trang public
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$header_user_name = 'Thí sinh';
if (isset($_SESSION['user_id'])) {
    if (isset($profile) && isset($profile['full_name'])) {
        $header_user_name = $profile['full_name'];
    } else {
        require_once __DIR__ . '/../lib/SupabaseClient.php';
        $sb_header = new SupabaseClient('anon');
        $header_token = $_SESSION['access_token'] ?? null;
        $p_header = $sb_header->select('user_profiles', "id=eq.".$_SESSION['user_id'], $header_token);
        if ($p_header['code'] == 200 && !empty($p_header['data'])) {
            $header_user_name = $p_header['data'][0]['full_name'] ?? 'Thí sinh';
        }
    }
}
?>
<header class="site-header">
    <div class="container-fluid px-4">
        <div class="d-flex align-items-center justify-content-between py-2">
            <!-- Hamburger Menu (Mobile Only) -->
            <button class="btn text-white d-md-none me-2" id="sidebarToggleBtn" style="border: none; background: transparent;">
                <i class="bi bi-list fs-3"></i>
            </button>

            <!-- Logo + Tên trường -->
            <a href="/tsdhhl26/" class="header-brand d-flex align-items-center gap-3 text-decoration-none">
                <img src="/tsdhhl26/assets/logo.png" alt="Logo ĐH Hạ Long" height="52"
                     onerror="this.style.display='none'">
                <div>
                    <div class="fw-bold text-white" style="font-size:1rem;letter-spacing:.3px;">TRƯỜNG ĐẠI HỌC HẠ LONG</div>
                    <div class="text-white-50" style="font-size:.78rem;letter-spacing:.5px;">Halong University</div>
                </div>
            </a>

            <div class="d-none d-md-flex align-items-center gap-4">
                <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true): ?>
                    <a href="/tsdhhl26/admin/index.php" class="text-white text-decoration-none fw-medium d-flex align-items-center gap-1" style="opacity: 0.9; transition: opacity 0.2s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.9'">
                        <i class="bi bi-speedometer2"></i> Bảng điều khiển
                    </a>
                <?php else: ?>
                    <a href="/tsdhhl26/" class="text-white text-decoration-none fw-medium d-flex align-items-center gap-1" style="opacity: 0.9; transition: opacity 0.2s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.9'">
                        <i class="bi bi-house-door-fill"></i> Trang chủ
                    </a>
                    <a href="/tsdhhl26/search.php" class="text-white text-decoration-none fw-medium d-flex align-items-center gap-1" style="opacity: 0.9; transition: opacity 0.2s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.9'">
                        <i class="bi bi-search"></i> Tra cứu
                    </a>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true): ?>
                    <div class="dropdown">
                        <a href="#" class="text-white text-decoration-none fw-medium dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown" aria-expanded="false" style="opacity: 0.9;">
                            <i class="bi bi-shield-lock-fill text-warning"></i>
                            <span>Xin chào, <span class="fw-bold text-warning">Quản trị viên</span></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" style="border-radius: 8px;">
                            <li>
                                <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="/tsdhhl26/admin/index.php">
                                    <i class="bi bi-speedometer2 text-secondary"></i> Bảng điều khiển
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item py-2 text-danger d-flex align-items-center gap-2" href="/tsdhhl26/admin/logout.php">
                                    <i class="bi bi-box-arrow-right"></i> Đăng xuất
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php elseif (isset($_SESSION['user_id'])): ?>
                    <div class="dropdown">
                        <a href="#" class="text-white text-decoration-none fw-medium dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown" aria-expanded="false" style="opacity: 0.9;">
                            <span>Xin chào, <span class="fw-bold text-warning"><?php echo htmlspecialchars($header_user_name); ?></span></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" style="border-radius: 8px;">
                            <li>
                                <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="/tsdhhl26/candidate/profile.php">
                                    <i class="bi bi-person text-secondary"></i> Thông tin cá nhân
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item py-2 text-danger d-flex align-items-center gap-2" href="/tsdhhl26/auth/logout.php">
                                    <i class="bi bi-box-arrow-right"></i> Đăng xuất
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="/tsdhhl26/auth/login.php" class="text-white text-decoration-none fw-bold" style="transition: opacity 0.2s;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">
                        Đăng nhập
                    </a>
                    <a href="/tsdhhl26/auth/register.php" class="btn fw-bold px-4 shadow-sm" style="background-color: #f59e0b; color: #fff; border: none; min-height: 40px; display: inline-flex; align-items: center; justify-content: center;">
                        Đăng ký ngay
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>
