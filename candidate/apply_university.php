<?php
// candidate/apply_university.php
$forced_level_name = 'Đại học Chính quy';

// Bước 2 — Đại học hiển thị: Họ tên, CMND, Ngày sinh, Email
$step2_fields = [
    ['label' => 'Họ và tên',      'key' => 'full_name'],
    ['label' => 'Số CMND/CCCD',   'key' => 'identity_card'],
    ['label' => 'Ngày sinh',       'key' => 'date_of_birth'],
    ['label' => 'Email liên lạc', 'key' => 'contact_email'],
];
$step1_info = '
<div class="d-flex align-items-start gap-2">
    <span class="fs-4">&#127979;</span>
    <div>
        <p class="mb-1 fw-bold text-dark">Xét tuyển Hệ Đại học — Trường Đại học Hạ Long</p>
        <ul class="mb-0 small text-muted ps-3">
            <li>Tốt nghiệp THPT hoặc tương đương.</li>
            <li>Chọn ngành phù hợp với năng lực và ngưỡng xét tuyển.</li>
            <li>Lệ phí xét tuyển được hiển thị sau khi chọn ngành ở Bước 3.</li>
            <li>Nộp ảnh biên lai chuyển khoản thành công để hoàn tất hồ sơ.</li>
        </ul>
    </div>
</div>';
require __DIR__ . '/apply.php';
