<?php
// candidate/apply_college.php
// ID hệ Cao đẳng Chính quy trong bảng education_levels (id=2)
$forced_level_id = 2;

// Bước 2 — Cao đẳng hiển thị thêm: Số điện thoại, Địa chỉ
$step2_fields = [
    ['label' => 'Họ và tên',        'key' => 'full_name'],
    ['label' => 'Số CMND/CCCD',     'key' => 'identity_card'],
    ['label' => 'Ngày sinh',         'key' => 'date_of_birth'],
    ['label' => 'Số điện thoại',     'key' => 'phone_number'],
    ['label' => 'Email liên lạc',   'key' => 'contact_email'],
    ['label' => 'Địa chỉ liên lạc', 'key' => 'address'],
];
$step1_info = '
<div class="d-flex align-items-start gap-2">
    <span class="fs-4">&#127979;</span>
    <div>
        <p class="mb-1 fw-bold text-dark">Xét tuyển Hệ Cao đẳng — Trường Đại học Hạ Long</p>
        <ul class="mb-0 small text-muted ps-3">
            <li>Tốt nghiệp THCS hoặc THPT.</li>
            <li>Thời gian đào tạo: 3 năm (Hệ chính quy).</li>
            <li>Chương trình thực hành, ứng dụng cao gắn liền doanh nghiệp.</li>
            <li>Nộp ảnh biên lai chuyển khoản thành công để hoàn tất hồ sơ.</li>
        </ul>
    </div>
</div>';
require __DIR__ . '/apply.php';
