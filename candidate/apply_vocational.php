<?php
// candidate/apply_vocational.php
// ID hệ Trung Cấp trong bảng education_levels (id=4)
$forced_level_id = 4;

// Bước 2 — Trung cấp hiển thị trường 1–11 (Định danh + Địa chỉ)
// Cấu hình tài liệu bắt buộc dùng để quét ở Bước 2
$required_doc_config = [
    2 => 'Ảnh chụp CMND/CCCD'
];
$step2_fields = [
    ['label' => 'Họ và tên',           'key' => 'full_name', 'required' => true],
    ['label' => 'Số CMND/CCCD',        'key' => 'identity_card', 'required' => true],
    ['label' => 'Ngày sinh',            'key' => 'date_of_birth', 'required' => true],
    ['label' => 'Giới tính',            'key' => 'gender', 'required' => true],
    ['label' => 'Dân tộc',              'key' => 'ethnicity', 'required' => false],
    ['label' => 'Email liên lạc',       'key' => 'contact_email', 'required' => true],
    ['label' => 'Số điện thoại',        'key' => 'phone_number', 'required' => true],
    ['label' => 'Tỉnh/Thành phố',      'key' => 'province', 'required' => true],
    ['label' => 'Phường/Xã',            'key' => 'ward', 'required' => true],
    ['label' => 'Địa chỉ chi tiết',     'key' => 'address_detail', 'required' => true],
    ['label' => 'Tên trường THPT/THCS', 'key' => 'school_name', 'required' => true],
    ['label' => 'Tỉnh/TP trường học',   'key' => 'school_province', 'required' => true],
    ['label' => 'Phường/Xã trường học',  'key' => 'school_ward', 'required' => false],
    ['label' => 'Khu vực ưu tiên',      'key' => 'priority_area', 'required' => false],
    ['label' => 'Đối tượng ưu tiên',    'key' => 'priority_object', 'required' => false],
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
