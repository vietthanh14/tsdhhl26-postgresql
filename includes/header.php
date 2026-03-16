<?php
// includes/header.php — Header chung cho các trang public
?>
<header class="site-header">
    <div class="container-fluid px-4">
        <div class="d-flex align-items-center justify-content-between py-2">
            <!-- Logo + Tên trường -->
            <a href="/tsdhhl26/" class="header-brand d-flex align-items-center gap-3 text-decoration-none">
                <img src="/tsdhhl26/assets/logo.png" alt="Logo ĐH Hạ Long" height="52"
                     onerror="this.style.display='none'">
                <div>
                    <div class="fw-bold text-white" style="font-size:1rem;letter-spacing:.3px;">TRƯỜNG ĐẠI HỌC HẠ LONG</div>
                    <div class="text-white-50" style="font-size:.78rem;letter-spacing:.5px;">Halong University</div>
                </div>
            </a>

            <!-- Thông tin liên hệ nhanh -->
            <div class="d-none d-md-flex flex-column align-items-end gap-1">
                <a href="tel:0886889898" class="text-white text-decoration-none small">
                    <i class="bi bi-telephone-fill me-1"></i>Hotline: <strong>0886.88.98.98</strong>
                </a>
                <a href="mailto:phongdaotao.dhhl@moet.edu.vn" class="text-white-50 text-decoration-none small">
                    <i class="bi bi-envelope-fill me-1"></i>Email: phongdaotao.dhhl@moet.edu.vn
                </a>
            </div>
        </div>
    </div>
</header>
