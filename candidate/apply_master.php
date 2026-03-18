<?php
// candidate/apply_master.php
// ID hệ Thạc sĩ trong bảng education_levels (id=3)
$forced_level_id = 3;

// Bước 2 — Thạc sĩ hiển thị: Họ tên, CCCD, Ngày sinh, Email, SĐT, Bằng ĐH
$step2_fields = [
    ['label' => 'Họ và tên',      'key' => 'full_name'],
    ['label' => 'Số CMND/CCCD',   'key' => 'identity_card'],
    ['label' => 'Ngày sinh',       'key' => 'date_of_birth'],
    ['label' => 'Email liên lạc', 'key' => 'contact_email'],
    ['label' => 'Số điện thoại',  'key' => 'phone_number'],
];
$step1_info = '
<div class="d-flex align-items-start gap-2">
    <span class="fs-4">&#127891;</span>
    <div>
        <p class="mb-1 fw-bold text-dark">Xét tuyển Hệ Thạc sĩ — Trường Đại học Hạ Long</p>
        <ul class="mb-0 small text-muted ps-3">
            <li>Tốt nghiệp Đại học đúng hoặc phù hợp chuyên ngành.</li>
            <li>Thời gian đào tạo: 1.5 – 2 năm.</li>
            <li>Lệ phí xét tuyển được hiển thị sau khi chọn ngành ở Bước 3.</li>
            <li>Nộp ảnh biên lai chuyển khoản thành công để hoàn tất hồ sơ.</li>
        </ul>
    </div>
</div>';
require __DIR__ . '/apply.php';
