<?php
require_once __DIR__ . '/../config/supabase.php';

// includes/footer.php — Footer chung cho các trang public
?>
<footer class="site-footer">
    <div class="container-fluid px-4 py-5">
        <div class="row g-4 justify-content-center text-center">
            <!-- Logo + Mạng xã hội -->
            <div class="col-md-4">
                <img src="<?php echo BASE_URL; ?>/assets/logo.png" alt="Logo ĐH Hạ Long" width="80" height="80" class="mb-3 mx-auto d-block"
                     onerror="this.style.display='none'">
                <div class="fw-bold text-white mb-1" style="font-size:.85rem;letter-spacing:.5px;">ĐẠI HỌC HẠ LONG</div>
                <div class="d-flex gap-3 justify-content-center mt-3">
                    <a href="https://www.facebook.com/dhhl.edu.vn" target="_blank" class="footer-social" title="Facebook"><i class="bi bi-facebook" aria-hidden="true"></i></a>
                    <a href="#" class="footer-social" title="YouTube"><i class="bi bi-youtube" aria-hidden="true"></i></a>
                    <a href="#" class="footer-social" title="Instagram"><i class="bi bi-instagram" aria-hidden="true"></i></a>
                    <a href="#" class="footer-social" title="TikTok"><i class="bi bi-tiktok" aria-hidden="true"></i></a>
                </div>
            </div>

            <!-- Thông tin liên hệ -->
            <div class="col-md-4">
                <h6 class="footer-heading justify-content-center d-flex align-items-center"><i class="bi bi-building me-2"></i>THÔNG TIN LIÊN HỆ</h6>
                <ul class="list-unstyled text-white-50 small mt-3 d-flex flex-column gap-2">
                    <li><i class="bi bi-geo-alt-fill me-2 text-white-50"></i>Cơ sở 1: Số 258 Bạch Đằng, phường Vàng Danh, tỉnh Quảng Ninh</li>
                    <li><i class="bi bi-geo-alt-fill me-2 text-white-50"></i>Cơ sở 2: Số 58 Nguyễn Văn Cừ, phường Hạ Long, tỉnh Quảng Ninh</li>
                    <li><i class="bi bi-telephone-fill me-2 text-white-50"></i>Hotline: <a href="tel:0886889898" class="text-white text-decoration-none fw-semibold">0886.88.98.98</a></li>
                    <li><i class="bi bi-envelope-fill me-2 text-white-50"></i>Email: <a href="mailto:phongdaotao.dhhl@moet.edu.vn" class="text-white text-decoration-none">phongdaotao.dhhl@moet.edu.vn</a></li>
                </ul>
            </div>

            <!-- QR Zalo -->
            <div class="col-md-4">
                <h6 class="footer-heading justify-content-center d-flex align-items-center"><i class="bi bi-people-fill me-2"></i>NHÓM ZALO HỖ TRỢ</h6>
                <div class="mt-3 d-inline-block position-relative">
                    <img src="<?php echo BASE_URL; ?>/assets/qr-zalo.jpg" alt="QR Zalo" width="130" height="130" class="rounded border border-white border-opacity-25">
                    <div class="mt-2">
                        <a href="https://docs.google.com/spreadsheets/d/1tyKS6r47TQEJOm0Io5jI35AukdhxBGJtmJY2ei-NcmE/edit?gid=0#gid=0" target="_blank"
                           class="btn btn-sm btn-light text-dark fw-semibold rounded-pill px-3"
                           style="font-size:.75rem;">
                            Quét mã QR hoặc Nhấn vào đây để tham gia
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Copyright bar -->
    <div class="footer-bottom">
        <div class="container-fluid px-4">
            <p class="mb-0 text-center text-white-50 small">
                Copyright &copy; <?php echo date('Y'); ?> UHL All Rights Reserved.
            </p>
        </div>
    </div>
</footer>

<!-- Modal Thông Báo Dùng Chung (gọi bằng showNotifyModal) -->
<div class="modal fade" id="globalNotifyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0" id="globalNotifyHeader">
                <h5 class="modal-title fw-bold" id="globalNotifyTitle"></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <i class="mb-3 d-block" id="globalNotifyIcon" style="font-size: 3rem;"></i>
                <p class="mb-0 fs-6" id="globalNotifyMsg"></p>
            </div>
            <div class="modal-footer border-0 justify-content-center pb-4">
                <button type="button" class="btn px-4" id="globalNotifyBtn" data-bs-dismiss="modal">Đã hiểu</button>
            </div>
        </div>
    </div>
</div>

<script>
function showNotifyModal(message, type) {
    type = type || 'danger';
    const config = {
        danger:  { bg: 'bg-danger text-white',  icon: 'bi bi-x-circle text-danger',           title: 'Đã xảy ra lỗi',     btn: 'btn-danger'  },
        warning: { bg: 'bg-warning text-dark',   icon: 'bi bi-exclamation-triangle text-warning', title: 'Cảnh báo',           btn: 'btn-warning' },
        success: { bg: 'bg-success text-white',  icon: 'bi bi-check-circle text-success',       title: 'Thành công',         btn: 'btn-success' },
        info:    { bg: 'bg-primary text-white',  icon: 'bi bi-info-circle text-primary',        title: 'Thông báo',          btn: 'btn-primary' }
    };
    const c = config[type] || config.danger;
    document.getElementById('globalNotifyHeader').className = 'modal-header border-0 ' + c.bg;
    document.getElementById('globalNotifyTitle').innerHTML = '<i class="' + c.icon.split(' ').slice(0,2).join(' ') + ' me-2"></i> ' + c.title;
    document.getElementById('globalNotifyIcon').className = c.icon + ' mb-3 d-block';
    document.getElementById('globalNotifyMsg').textContent = message;
    document.getElementById('globalNotifyBtn').className = 'btn px-4 ' + c.btn;
    new bootstrap.Modal(document.getElementById('globalNotifyModal')).show();
}
</script>
