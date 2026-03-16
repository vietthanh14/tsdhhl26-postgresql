<?php
// includes/footer.php — Footer chung cho các trang public
?>
<footer class="site-footer">
    <div class="container-fluid px-4 py-5">
        <div class="row g-4 align-items-start">
            <!-- Logo + Mạng xã hội -->
            <div class="col-md-3 text-center text-md-start">
                <img src="/tsdhhl26/assets/logo.png" alt="Logo ĐH Hạ Long" height="80" class="mb-3"
                     onerror="this.style.display='none'">
                <div class="fw-bold text-white mb-1" style="font-size:.85rem;letter-spacing:.5px;">ĐẠI HỌC HẠ LONG</div>
                <div class="d-flex gap-3 justify-content-center justify-content-md-start mt-3">
                    <a href="https://www.facebook.com/dhhl.edu.vn" target="_blank" class="footer-social" title="Facebook"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="footer-social" title="YouTube"><i class="bi bi-youtube"></i></a>
                    <a href="#" class="footer-social" title="Instagram"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="footer-social" title="TikTok"><i class="bi bi-tiktok"></i></a>
                </div>
            </div>

            <!-- Thông tin liên hệ -->
            <div class="col-md-5">
                <h6 class="footer-heading"><i class="bi bi-building me-2"></i>THÔNG TIN LIÊN HỆ</h6>
                <ul class="list-unstyled text-white-50 small mt-3 d-flex flex-column gap-2">
                    <li><i class="bi bi-geo-alt-fill me-2 text-white-50"></i>Cơ sở 1: Số 258 Bạch Đằng, phường Vàng Danh, tỉnh Quảng Ninh</li>
                    <li><i class="bi bi-geo-alt-fill me-2 text-white-50"></i>Cơ sở 2: Số 58 Nguyễn Văn Cừ, phường Hạ Long, tỉnh Quảng Ninh</li>
                    <li><i class="bi bi-telephone-fill me-2 text-white-50"></i>Hotline: <a href="tel:0886889898" class="text-white text-decoration-none fw-semibold">0886.88.98.98</a></li>
                    <li><i class="bi bi-envelope-fill me-2 text-white-50"></i>Email: <a href="mailto:phongdaotao.dhhl@moet.edu.vn" class="text-white text-decoration-none">phongdaotao.dhhl@moet.edu.vn</a></li>
                </ul>
            </div>

            <!-- QR Zalo -->
            <div class="col-md-4 text-center">
                <h6 class="footer-heading"><i class="bi bi-people-fill me-2"></i>NHÓM ZALO HỖ TRỢ</h6>
                <div class="mt-3 d-inline-block position-relative">
                    <img src="/tsdhhl26/assets/zalo_qr.png" alt="QR Zalo" width="130" class="rounded border border-white border-opacity-25"
                         onerror="this.src='https://api.qrserver.com/v1/create-qr-code/?size=130x130&data=https://zalo.me/g/halouniversity'">
                    <div class="mt-2">
                        <a href="https://zalo.me" target="_blank"
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
