<?php
// candidate/apply_vocational.php
// ID hệ Trung Cấp trong bảng education_levels (id=4)
$forced_level_id = 4;

// Bước 2 — Trung cấp hiển thị trường 1–11 (Định danh + Địa chỉ)
$step2_fields = [
    ['label' => 'Họ và tên',           'key' => 'full_name'],
    ['label' => 'Số CMND/CCCD',        'key' => 'identity_card'],
    ['label' => 'Ngày sinh',            'key' => 'date_of_birth'],
    ['label' => 'Giới tính',            'key' => 'gender'],
    ['label' => 'Dân tộc',              'key' => 'ethnicity'],
    ['label' => 'Email liên lạc',       'key' => 'contact_email'],
    ['label' => 'Số điện thoại',        'key' => 'phone_number'],
    ['label' => 'Tỉnh/Thành phố',      'key' => 'province'],
    ['label' => 'Phường/Xã',            'key' => 'ward'],
    ['label' => 'Địa chỉ chi tiết',     'key' => 'address_detail'],
    ['label' => 'Tên trường THPT/THCS', 'key' => 'school_name'],
    ['label' => 'Tỉnh/TP trường học',   'key' => 'school_province'],
    ['label' => 'Phường/Xã trường học',  'key' => 'school_ward'],
    ['label' => 'Khu vực ưu tiên',      'key' => 'priority_area'],
    ['label' => 'Đối tượng ưu tiên',    'key' => 'priority_object'],
];
$step1_info = '
<div class="d-flex align-items-start gap-2">
    <span class="fs-4">&#128218;</span>
    <div>
        <p class="mb-1 fw-bold text-dark">Xét tuyển Hệ Trung Cấp — Trường Đại học Hạ Long</p>
        <ul class="mb-0 small text-muted ps-3">
            <li>Tốt nghiệp THCS hoặc tương đương.</li>
            <li>Thời gian đào tạo: 2 – 3 năm (tùy ngành).</li>
            <li>Chương trình đào tạo nghề nghiệp, thực hành cao.</li>
            <li>Nộp ảnh biên lai chuyển khoản thành công để hoàn tất hồ sơ.</li>
        </ul>
    </div>
</div>';
require __DIR__ . '/apply.php';
