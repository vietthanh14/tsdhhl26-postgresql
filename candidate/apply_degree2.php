<?php
// candidate/apply_degree2.php
// ID hệ Văn bằng 2, vừa làm vừa học trong bảng education_levels (id=5)
$forced_level_id = 5;

// Bước 2 — VB2 hiển thị: Họ tên, CCCD, Ngày sinh, SĐT, Email
$step2_fields = [
    ['label' => 'Họ và tên',      'key' => 'full_name'],
    ['label' => 'Số CMND/CCCD',   'key' => 'identity_card'],
    ['label' => 'Ngày sinh',       'key' => 'date_of_birth'],
    ['label' => 'Số điện thoại',  'key' => 'phone_number'],
    ['label' => 'Email liên lạc', 'key' => 'contact_email'],
];
$step1_info = '
<div class="d-flex align-items-start gap-2">
    <span class="fs-4">&#128214;</span>
    <div>
        <p class="mb-1 fw-bold text-dark">Xét tuyển Hệ Văn bằng 2 — Trường Đại học Hạ Long</p>
        <ul class="mb-0 small text-muted ps-3">
            <li>Đã tốt nghiệp Đại học/Cao đẳng, muốn học thêm ngành mới.</li>
            <li>Hình thức vừa làm vừa học, linh hoạt thời gian.</li>
            <li>Được miễn giảm các học phần đã học (tùy ngành).</li>
            <li>Nộp ảnh biên lai chuyển khoản thành công để hoàn tất hồ sơ.</li>
        </ul>
    </div>
</div>';
require __DIR__ . '/apply.php';
