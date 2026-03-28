<?php
// candidate/apply_college.php
// ID hệ Cao đẳng Chính quy trong bảng education_levels (id=2)
$forced_level_id = 2;

// Bước 2 — Cao đẳng hiển thị trường 1–20 (Định danh + Địa chỉ + THPT + Ưu tiên)
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
    ['label' => 'Trường THPT',          'key' => 'school_name'],
    ['label' => 'Năm TN THPT',          'key' => 'graduation_year'],
    ['label' => 'Tỉnh/TP trường THPT',  'key' => 'school_province'],
    ['label' => 'Phường/Xã trường THPT','key' => 'school_ward'],
    ['label' => 'Học lực lớp 12',       'key' => 'academic_performance'],
    ['label' => 'Hạnh kiểm lớp 12',    'key' => 'conduct'],
    ['label' => 'Khu vực ưu tiên',      'key' => 'priority_area'],
    ['label' => 'Đối tượng ưu tiên',    'key' => 'priority_object'],
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
