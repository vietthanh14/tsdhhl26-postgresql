<?php
// candidate/profile.php
session_start();
require_once __DIR__ . '/../lib/SupabaseClient.php';
require_once __DIR__ . '/../lib/Cache.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$token = $_SESSION['access_token'] ?? null;
$supabase = new SupabaseClient('anon');
$supabaseAdmin = new SupabaseClient('service');
$message = '';
$error = '';

// Xử lý CẬP NHẬT thông tin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $identity_card = trim($_POST['identity_card'] ?? '');
    $contact_email = trim($_POST['contact_email'] ?? '');
    $date_of_birth = trim($_POST['date_of_birth'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $ethnicity = trim($_POST['ethnicity'] ?? '');
    $province_code = trim($_POST['province_code'] ?? '');
    $province_name = trim($_POST['province_name'] ?? '');
    $ward_code = trim($_POST['ward_code'] ?? '');
    $ward_name = trim($_POST['ward_name'] ?? '');
    $address_detail = trim($_POST['address_detail'] ?? '');
    $school_name = trim($_POST['school_name'] ?? '');
    $school_province_name = trim($_POST['school_province_name'] ?? '');
    $school_ward_name = trim($_POST['school_ward_name'] ?? '');
    $school_address_detail = trim($_POST['school_address_detail'] ?? '');
    $priority_area = trim($_POST['priority_area'] ?? '');
    $academic_performance = trim($_POST['academic_performance'] ?? '');
    $conduct = trim($_POST['conduct'] ?? '');
    $graduation_year = trim($_POST['graduation_year'] ?? '');
    $priority_object = trim($_POST['priority_object'] ?? '');
    $prev_degree_level = trim($_POST['prev_degree_level'] ?? '');
    $prev_major = trim($_POST['prev_major'] ?? '');
    $prev_admission_date = trim($_POST['prev_admission_date'] ?? '');
    $prev_graduation_date = trim($_POST['prev_graduation_date'] ?? '');
    $prev_graduation_rank = trim($_POST['prev_graduation_rank'] ?? '');
    $prev_diploma_school = trim($_POST['prev_diploma_school'] ?? '');
    $prev_diploma_date = trim($_POST['prev_diploma_date'] ?? '');
    $current_position = trim($_POST['current_position'] ?? '');
    $current_workplace = trim($_POST['current_workplace'] ?? '');

    if (empty($full_name)) {
        $_SESSION['profile_error'] = "Họ tên không được để trống.";
    } else {
        $updateData = [
            'full_name' => $full_name,
            'identity_card' => $identity_card,
            'contact_email' => $contact_email,
            'date_of_birth' => $date_of_birth ?: null,
            'phone_number' => $phone_number,
            'gender' => $gender ?: null,
            'ethnicity' => $ethnicity ?: null,
            'province' => $province_name ?: null,
            'ward' => $ward_name ?: null,
            'address_detail' => $address_detail ?: null,
            'school_name' => $school_name ?: null,
            'school_province' => $school_province_name ?: null,
            'school_ward' => $school_ward_name ?: null,
            'school_address_detail' => $school_address_detail ?: null,
            'priority_area' => $priority_area ?: null,
            'academic_performance' => $academic_performance ?: null,
            'conduct' => $conduct ?: null,
            'graduation_year' => $graduation_year ? (int)$graduation_year : null,
            'priority_object' => $priority_object ?: null,
            'prev_degree_level' => $prev_degree_level ?: null,
            'prev_major' => $prev_major ?: null,
            'prev_admission_date' => $prev_admission_date ?: null,
            'prev_graduation_date' => $prev_graduation_date ?: null,
            'prev_graduation_rank' => $prev_graduation_rank ?: null,
            'prev_diploma_school' => $prev_diploma_school ?: null,
            'prev_diploma_date' => $prev_diploma_date ?: null,
            'current_position' => $current_position ?: null,
            'current_workplace' => $current_workplace ?: null,
            'updated_at' => date('Y-m-d H:i:sP'),
        ];

        $updateRes = $supabase->update('user_profiles', 'id', $user_id, $updateData, $token);

        if (in_array($updateRes['code'], [200, 204])) {
            $_SESSION['profile_success'] = "Cập nhật hồ sơ thành công!";
        } else {
            $_SESSION['profile_error'] = "Lỗi cập nhật hồ sơ. Vui lòng thử lại hoặc liên hệ quản trị viên.";
        }
    }

    header('Location: ' . BASE_URL . '/candidate/profile.php');
    exit;
}

// Lấy thông báo từ Session để xuất ra (nếu có)
if (isset($_SESSION['profile_success'])) {
    $message = $_SESSION['profile_success'];
    unset($_SESSION['profile_success']);
}
if (isset($_SESSION['profile_error'])) {
    $error = $_SESSION['profile_error'];
    unset($_SESSION['profile_error']);
}

// Lấy thông tin hiện tại
$profileResponse = $supabase->select('user_profiles', "id=eq.{$user_id}", $token);

// Bảo mật: Nếu token hết hạn (401) hoặc user đã bị xoá khỏi DB (empty data), xoá session cũ
if ($profileResponse['code'] == 401 || empty($profileResponse['data'])) {
    session_destroy();
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$profile = $profileResponse['data'][0];
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông tin cá nhân - Tuyển sinh</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/public.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/dashboard.css">
    <style>
        /* Searchable Combobox (profile.php only) */
        .combo-wrapper { position: relative; }
        .combo-wrapper .combo-input {
            border-radius: 6px; border: 1px solid #cbd5e1; min-height: 44px; width: 100%;
            padding: .375rem .75rem; font-size: 1rem;
            transition: border-color .15s ease, box-shadow .15s ease;
            background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 16 16'%3E%3Cpath fill='%2364748b' d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E") no-repeat right .75rem center;
            padding-right: 2rem; cursor: pointer;
        }
        .combo-wrapper .combo-input:focus { outline: none; border-color: var(--brand-color); box-shadow: 0 0 0 2px rgba(26, 58, 110, .15); }
        .combo-wrapper .combo-input:disabled { background-color: #f8fafc; cursor: not-allowed; color: #94a3b8; }
        .combo-dropdown {
            display: none; position: absolute; top: calc(100% + 4px); left: 0; right: 0;
            background: #fff; border: 1px solid #cbd5e1; border-radius: 8px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, .1); max-height: 230px; overflow-y: auto; z-index: 1050;
        }
        .combo-dropdown.open { display: block; }
        .combo-dropdown .combo-search { position: sticky; top: 0; padding: 8px; background: #fff; border-bottom: 1px solid #e2e8f0; }
        .combo-dropdown .combo-search input { width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 10px; font-size: .875rem; outline: none; }
        .combo-dropdown .combo-search input:focus { border-color: var(--brand-color); }
        .combo-option { padding: 9px 14px; cursor: pointer; font-size: .9rem; transition: background .1s; }
        .combo-option:hover, .combo-option.active { background: #eff6ff; color: var(--brand-color); }
        .combo-option.no-result { color: #94a3b8; cursor: default; font-style: italic; }
        .combo-clear {
            position: absolute; right: 28px; top: 50%; transform: translateY(-50%);
            cursor: pointer; color: #94a3b8; font-size: .85rem; display: none; line-height: 1; z-index: 1;
        }
        .combo-clear:hover { color: #ef4444; }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <div class="container-fluid">

        <div class="row">
            <!-- Sidebar Component -->
            <?php include __DIR__ . '/includes/sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-10 content-area">
                <div
                    class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-3 border-bottom gap-2">
                    <h3 class="fw-bold mb-0 text-dark">Thông tin cá nhân</h3>
                </div>

                <div class="row">
                    <div class="col-md-8">
                        <div class="card">

                            <div class="card-body">
                                <?php if ($message): ?>
                                    <div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>
                                <?php if ($error): ?>
                                    <div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

                                <form method="POST" action="">

                                    <!-- ====== Section 1: Thông tin định danh ====== -->
                                    <h6 class="fw-bold mb-3 px-3 py-2 rounded" style="background-color: #1A3A6E; color: #fff;"><i class="bi bi-person-vcard me-2"></i>Thông tin định danh</h6>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label text-muted fw-bold">Họ và Tên đầy đủ <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="full_name" class="form-control" required
                                                value="<?php echo htmlspecialchars($profile['full_name'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-6 mt-3 mt-md-0">
                                            <label class="form-label text-muted fw-bold">Tên đăng nhập
                                                (Username)</label>
                                            <input type="text" class="form-control bg-light" disabled
                                                value="<?php echo htmlspecialchars($profile['username'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label text-muted fw-bold">Số CMND/CCCD <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="identity_card" class="form-control" required
                                                value="<?php echo htmlspecialchars($profile['identity_card'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-6 mt-3 mt-md-0">
                                            <label class="form-label text-muted fw-bold">Ngày sinh</label>
                                            <input type="date" name="date_of_birth" class="form-control"
                                                value="<?php echo htmlspecialchars($profile['date_of_birth'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label text-muted fw-bold">Giới tính</label>
                                            <select name="gender" class="form-select">
                                                <option value="">-- Chọn giới tính --</option>
                                                <option value="Nam" <?php echo ($profile['gender'] ?? '') === 'Nam' ? 'selected' : ''; ?>>Nam</option>
                                                <option value="Nữ" <?php echo ($profile['gender'] ?? '') === 'Nữ' ? 'selected' : ''; ?>>Nữ</option>
                                                <option value="Khác" <?php echo ($profile['gender'] ?? '') === 'Khác' ? 'selected' : ''; ?>>Khác</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mt-3 mt-md-0">
                                            <label class="form-label text-muted fw-bold">Dân tộc</label>
                                            <input type="text" name="ethnicity" class="form-control"
                                                placeholder="Ví dụ: Kinh"
                                                value="<?php echo htmlspecialchars($profile['ethnicity'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label text-muted fw-bold">Email nhận thông báo</label>
                                            <input type="email" name="contact_email" class="form-control"
                                                placeholder="nguyenvana@gmail.com"
                                                value="<?php echo htmlspecialchars($profile['contact_email'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-6 mt-3 mt-md-0">
                                            <label class="form-label text-muted fw-bold">Số điện thoại liên hệ</label>
                                            <input type="tel" name="phone_number" class="form-control"
                                                value="<?php echo htmlspecialchars($profile['phone_number'] ?? ''); ?>">
                                        </div>
                                    </div>

                                    <!-- ====== Section 2: Địa chỉ thường trú ====== -->
                                    <hr class="my-4">
                                    <h6 class="fw-bold mb-3 px-3 py-2 rounded" style="background-color: #1A3A6E; color: #fff;"><i class="bi bi-house-door me-2"></i>Địa chỉ thường trú</h6>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label text-muted fw-bold">Tỉnh / Thành phố</label>
                                            <div class="combo-wrapper" id="provinceWrapper">
                                                <span class="combo-clear" id="provinceClear" title="Xóa">&times;</span>
                                                <input type="text" class="combo-input" id="provinceInput"
                                                    placeholder="-- Chọn Tỉnh/Thành phố --" readonly>
                                                <div class="combo-dropdown" id="provinceDropdown">
                                                    <div class="combo-search">
                                                        <input type="text" id="provinceSearch"
                                                            placeholder="🔍 Tìm tỉnh/thành..." autocomplete="off">
                                                    </div>
                                                    <div id="provinceList"></div>
                                                </div>
                                            </div>
                                            <input type="hidden" name="province_name" id="provinceName">
                                            <input type="hidden" name="province_code" id="provinceCode">
                                        </div>
                                        <div class="col-md-6 mt-3 mt-md-0">
                                            <label class="form-label text-muted fw-bold">Phường / Xã</label>
                                            <div class="combo-wrapper" id="wardWrapper">
                                                <span class="combo-clear" id="wardClear" title="Xóa">&times;</span>
                                                <input type="text" class="combo-input" id="wardInput"
                                                    placeholder="-- Chọn Phường/Xã --" readonly disabled>
                                                <div class="combo-dropdown" id="wardDropdown">
                                                    <div class="combo-search">
                                                        <input type="text" id="wardSearch"
                                                            placeholder="🔍 Tìm phường/xã..." autocomplete="off">
                                                    </div>
                                                    <div id="wardList"></div>
                                                </div>
                                            </div>
                                            <input type="hidden" name="ward_name" id="wardName">
                                            <input type="hidden" name="ward_code" id="wardCode">
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label text-muted fw-bold">Địa chỉ chi tiết (số nhà, thôn,
                                                đường...)</label>
                                            <input type="text" name="address_detail" id="addressDetail" class="form-control"
                                                placeholder="Ví dụ: Số 12, Đường Lê Lợi"
                                                value="<?php echo htmlspecialchars($profile['address_detail'] ?? ''); ?>">
                                        </div>
                                    </div>

                                    <!-- ====== Section 3: Trường THPT ====== -->
                                    <hr class="my-4">
                                    <h6 class="fw-bold mb-3 px-3 py-2 rounded" style="background-color: #1A3A6E; color: #fff;"><i class="bi bi-mortarboard me-2"></i>Trường THPT</h6>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label text-muted fw-bold">Tên trường THPT</label>
                                            <input type="text" name="school_name" class="form-control"
                                                placeholder="Ví dụ: THPT Nguyễn Huệ"
                                                value="<?php echo htmlspecialchars($profile['school_name'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-6 mt-3 mt-md-0">
                                            <label class="form-label text-muted fw-bold">Năm tốt nghiệp THPT</label>
                                            <input type="number" name="graduation_year" class="form-control"
                                                placeholder="Ví dụ: 2026"
                                                value="<?php echo htmlspecialchars($profile['graduation_year'] ?? ''); ?>">
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label text-muted fw-bold">Tỉnh / Thành phố (Trường THPT)</label>
                                            <div class="combo-wrapper" id="schoolProvinceWrapper">
                                                <span class="combo-clear" id="schoolProvinceClear" title="Xóa">&times;</span>
                                                <input type="text" class="combo-input" id="schoolProvinceInput"
                                                    placeholder="-- Chọn Tỉnh/Thành phố --" readonly>
                                                <div class="combo-dropdown" id="schoolProvinceDropdown">
                                                    <div class="combo-search">
                                                        <input type="text" id="schoolProvinceSearch"
                                                            placeholder="🔍 Tìm tỉnh/thành..." autocomplete="off">
                                                    </div>
                                                    <div id="schoolProvinceList"></div>
                                                </div>
                                            </div>
                                            <input type="hidden" name="school_province_name" id="schoolProvinceName">
                                            <input type="hidden" name="school_province_code" id="schoolProvinceCode">
                                        </div>
                                        <div class="col-md-6 mt-3 mt-md-0">
                                            <label class="form-label text-muted fw-bold">Phường / Xã (Trường THPT)</label>
                                            <div class="combo-wrapper" id="schoolWardWrapper">
                                                <span class="combo-clear" id="schoolWardClear" title="Xóa">&times;</span>
                                                <input type="text" class="combo-input" id="schoolWardInput"
                                                    placeholder="-- Chọn Phường/Xã --" readonly disabled>
                                                <div class="combo-dropdown" id="schoolWardDropdown">
                                                    <div class="combo-search">
                                                        <input type="text" id="schoolWardSearch"
                                                            placeholder="🔍 Tìm phường/xã..." autocomplete="off">
                                                    </div>
                                                    <div id="schoolWardList"></div>
                                                </div>
                                            </div>
                                            <input type="hidden" name="school_ward_name" id="schoolWardName">
                                            <input type="hidden" name="school_ward_code" id="schoolWardCode">
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label text-muted fw-bold">Học lực lớp 12</label>
                                            <select name="academic_performance" class="form-select">
                                                <option value="">-- Chọn học lực --</option>
                                                <option value="Giỏi" <?php echo ($profile['academic_performance'] ?? '') === 'Giỏi' ? 'selected' : ''; ?>>Giỏi</option>
                                                <option value="Khá" <?php echo ($profile['academic_performance'] ?? '') === 'Khá' ? 'selected' : ''; ?>>Khá</option>
                                                <option value="Trung bình" <?php echo ($profile['academic_performance'] ?? '') === 'Trung bình' ? 'selected' : ''; ?>>Trung bình</option>
                                                <option value="Yếu" <?php echo ($profile['academic_performance'] ?? '') === 'Yếu' ? 'selected' : ''; ?>>Yếu</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mt-3 mt-md-0">
                                            <label class="form-label text-muted fw-bold">Hạnh kiểm lớp 12</label>
                                            <select name="conduct" class="form-select">
                                                <option value="">-- Chọn hạnh kiểm --</option>
                                                <option value="Tốt" <?php echo ($profile['conduct'] ?? '') === 'Tốt' ? 'selected' : ''; ?>>Tốt</option>
                                                <option value="Khá" <?php echo ($profile['conduct'] ?? '') === 'Khá' ? 'selected' : ''; ?>>Khá</option>
                                                <option value="Trung bình" <?php echo ($profile['conduct'] ?? '') === 'Trung bình' ? 'selected' : ''; ?>>Trung bình</option>
                                                <option value="Yếu" <?php echo ($profile['conduct'] ?? '') === 'Yếu' ? 'selected' : ''; ?>>Yếu</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- ====== Section 4: Ưu tiên ====== -->
                                    <hr class="my-4">
                                    <h6 class="fw-bold mb-3 px-3 py-2 rounded" style="background-color: #1A3A6E; color: #fff;"><i class="bi bi-star me-2"></i>Ưu tiên</h6>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label text-muted fw-bold">Khu vực ưu tiên</label>
                                            <select name="priority_area" class="form-select">
                                                <option value="">-- Chọn khu vực --</option>
                                                <option value="KV1" <?php echo ($profile['priority_area'] ?? '') === 'KV1' ? 'selected' : ''; ?>>KV1</option>
                                                <option value="KV2" <?php echo ($profile['priority_area'] ?? '') === 'KV2' ? 'selected' : ''; ?>>KV2</option>
                                                <option value="KV2-NT" <?php echo ($profile['priority_area'] ?? '') === 'KV2-NT' ? 'selected' : ''; ?>>KV2-NT</option>
                                                <option value="KV3" <?php echo ($profile['priority_area'] ?? '') === 'KV3' ? 'selected' : ''; ?>>KV3</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mt-3 mt-md-0">
                                            <label class="form-label text-muted fw-bold">Đối tượng ưu tiên</label>
                                            <select name="priority_object" class="form-select">
                                                <option value="">-- Không có --</option>
                                                <option value="01" <?php echo ($profile['priority_object'] ?? '') === '01' ? 'selected' : ''; ?>>01 - Dân tộc thiểu số (KV1)</option>
                                                <option value="02" <?php echo ($profile['priority_object'] ?? '') === '02' ? 'selected' : ''; ?>>02 - CN sản xuất ưu tú</option>
                                                <option value="03" <?php echo ($profile['priority_object'] ?? '') === '03' ? 'selected' : ''; ?>>03 - Thương binh, Quân/CA tại ngũ</option>
                                                <option value="04" <?php echo ($profile['priority_object'] ?? '') === '04' ? 'selected' : ''; ?>>04 - Con liệt sĩ, Con TB/BB (≥81%)</option>
                                                <option value="05" <?php echo ($profile['priority_object'] ?? '') === '05' ? 'selected' : ''; ?>>05 - TNXP, Quân/CA xuất ngũ</option>
                                                <option value="06" <?php echo ($profile['priority_object'] ?? '') === '06' ? 'selected' : ''; ?>>06 - DTTS ngoài KV1, Con TB/BB (<81%)</option>
                                                <option value="07" <?php echo ($profile['priority_object'] ?? '') === '07' ? 'selected' : ''; ?>>07 - Người KT nặng, LĐ/Nhà giáo/YT XS</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- ====== Section 5: Đã tốt nghiệp ====== -->
                                    <hr class="my-4">
                                    <h6 class="fw-bold mb-3 px-3 py-2 rounded" style="background-color: #1A3A6E; color: #fff;"><i class="bi bi-award me-2"></i>Đã tốt nghiệp (nếu có)</h6>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label text-muted fw-bold">Trình độ đã tốt nghiệp</label>
                                            <select name="prev_degree_level" class="form-select">
                                                <option value="">-- Chưa có --</option>
                                                <option value="Trung cấp" <?php echo ($profile['prev_degree_level'] ?? '') === 'Trung cấp' ? 'selected' : ''; ?>>Trung cấp</option>
                                                <option value="Cao đẳng" <?php echo ($profile['prev_degree_level'] ?? '') === 'Cao đẳng' ? 'selected' : ''; ?>>Cao đẳng</option>
                                                <option value="Đại học" <?php echo ($profile['prev_degree_level'] ?? '') === 'Đại học' ? 'selected' : ''; ?>>Đại học</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mt-3 mt-md-0">
                                            <label class="form-label text-muted fw-bold">Ngành đã tốt nghiệp</label>
                                            <input type="text" name="prev_major" class="form-control"
                                                placeholder="Ví dụ: Kế toán"
                                                value="<?php echo htmlspecialchars($profile['prev_major'] ?? ''); ?>">
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label text-muted fw-bold">Ngày trúng tuyển</label>
                                            <input type="date" name="prev_admission_date" class="form-control"
                                                value="<?php echo htmlspecialchars($profile['prev_admission_date'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-6 mt-3 mt-md-0">
                                            <label class="form-label text-muted fw-bold">Ngày tốt nghiệp</label>
                                            <input type="date" name="prev_graduation_date" class="form-control"
                                                value="<?php echo htmlspecialchars($profile['prev_graduation_date'] ?? ''); ?>">
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label text-muted fw-bold">Xếp loại tốt nghiệp</label>
                                            <select name="prev_graduation_rank" class="form-select">
                                                <option value="">-- Chọn xếp loại --</option>
                                                <option value="Xuất sắc" <?php echo ($profile['prev_graduation_rank'] ?? '') === 'Xuất sắc' ? 'selected' : ''; ?>>Xuất sắc</option>
                                                <option value="Giỏi" <?php echo ($profile['prev_graduation_rank'] ?? '') === 'Giỏi' ? 'selected' : ''; ?>>Giỏi</option>
                                                <option value="Khá" <?php echo ($profile['prev_graduation_rank'] ?? '') === 'Khá' ? 'selected' : ''; ?>>Khá</option>
                                                <option value="Trung bình khá" <?php echo ($profile['prev_graduation_rank'] ?? '') === 'Trung bình khá' ? 'selected' : ''; ?>>Trung bình khá</option>
                                                <option value="Trung bình" <?php echo ($profile['prev_graduation_rank'] ?? '') === 'Trung bình' ? 'selected' : ''; ?>>Trung bình</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mt-3 mt-md-0">
                                            <label class="form-label text-muted fw-bold">Bằng tốt nghiệp do trường cấp</label>
                                            <input type="text" name="prev_diploma_school" class="form-control"
                                                placeholder="Ví dụ: Trường CĐ Sư phạm Huế"
                                                value="<?php echo htmlspecialchars($profile['prev_diploma_school'] ?? ''); ?>">
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label text-muted fw-bold">Cấp ngày</label>
                                            <input type="date" name="prev_diploma_date" class="form-control"
                                                value="<?php echo htmlspecialchars($profile['prev_diploma_date'] ?? ''); ?>">
                                        </div>
                                    </div>

                                    <!-- ====== Section 6: Công tác hiện tại ====== -->
                                    <hr class="my-4">
                                    <h6 class="fw-bold mb-3 px-3 py-2 rounded" style="background-color: #1A3A6E; color: #fff;"><i class="bi bi-briefcase me-2"></i>Công tác hiện tại (nếu có)</h6>

                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <label class="form-label text-muted fw-bold">Chức vụ công tác hiện nay</label>
                                            <input type="text" name="current_position" class="form-control"
                                                placeholder="Ví dụ: Nhân viên kế toán"
                                                value="<?php echo htmlspecialchars($profile['current_position'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-6 mt-3 mt-md-0">
                                            <label class="form-label text-muted fw-bold">Tên và địa chỉ cơ quan đang công tác</label>
                                            <input type="text" name="current_workplace" class="form-control"
                                                placeholder="Ví dụ: Công ty TNHH ABC - 123 Lê Lợi, Huế"
                                                value="<?php echo htmlspecialchars($profile['current_workplace'] ?? ''); ?>">
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-brand px-4 py-2 fw-semibold">LƯU THAY
                                        ĐỔI</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <!-- Upload Section -->
                        <div class="card p-3 mb-4">
                            <div class="card-header border-0 bg-white p-0 mb-3">
                                <h6 class="fw-bold text-dark mb-0">Tải lên tài liệu</h6>
                                <p class="text-muted small mb-0">Chọn loại hồ sơ và tệp tin điện tử (PDF, JPG, PNG)</p>
                            </div>
                            <div class="card-body p-0">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">1. Loại tài liệu</label>
                                    <select id="docTypeSelect" class="form-select form-select-sm">
                                        <option value="">-- Chọn danh mục --</option>
                                        <?php
                                        $docTypes = Cache::remember('document_types', 3600, function() use ($supabaseAdmin) {
                                            $res = $supabaseAdmin->select('document_types', 'select=*&order=id.asc');
                                            return ($res['code'] == 200) ? $res['data'] : [];
                                        });
                                        foreach ($docTypes as $type) {
                                            echo "<option value='{$type['id']}'>{$type['type_name']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">2. Chọn tập tin</label>
                                    <input type="file" id="fileInput" class="form-control form-control-sm"
                                        accept=".pdf,.jpg,.jpeg,.png">
                                </div>
                                <button id="uploadBtn" class="btn btn-brand btn-sm w-100 fw-bold py-2">BẮT ĐẦU TẢI
                                    LÊN</button>

                                <!-- Progress Bar -->
                                <div id="uploadProgress" class="progress mt-3 d-none" style="height: 5px;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-success"
                                        style="width: 100%"></div>
                                </div>
                                <div id="uploadStatus" class="small mt-2 text-center"></div>
                            </div>
                        </div>

                        <!-- List Section -->
                        <div class="card p-0">
                            <div class="card-header py-3 px-3">
                                <h6 class="mb-0 fw-bold">Tài liệu đã nộp</h6>
                            </div>
                            <div class="list-group list-group-flush" id="documentList">
                                <?php
                                $userDocsRes = $supabaseAdmin->select('user_documents', "user_id=eq.{$user_id}", $token);
                                if ($userDocsRes['code'] == 200 && !empty($userDocsRes['data'])) {
                                    foreach ($userDocsRes['data'] as $doc) {
                                        // Tìm tên loại tài liệu
                                        $typeName = "Tài liệu #{$doc['document_type_id']}";
                                        foreach ($docTypes as $dt) {
                                            if ($dt['id'] == $doc['document_type_id']) {
                                                $typeName = $dt['type_name'];
                                                break;
                                            }
                                        }
                                        echo "
                                    <div class='list-group-item d-flex justify-content-between align-items-center py-3'>
                                        <div>
                                            <div class='fw-bold small'>{$typeName}</div>
                                            <a href='{$doc['drive_file_url']}' target='_blank' class='text-brand small text-decoration-none'>Xem file</a>
                                        </div>
                                        <div>
                                            <button class='btn btn-sm btn-outline-danger' onclick='deleteDocument(\"{$doc['id']}\")'>Xóa</button>
                                        </div>
                                    </div>";
                                    }
                                } else {
                                    echo "<div class='p-4 text-center text-muted small'>Chưa có tài liệu nào.</div>";
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Confirm Delete -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white border-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-octagon me-2"></i> Xác nhận Xóa Tài Liệu
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <i class="bi bi-trash text-danger mb-3 d-block" style="font-size: 3rem;"></i>
                    <p class="mb-0 fs-5">Bạn có chắc chắn muốn xóa bản sao tài liệu này?</p>
                    <p class="text-muted small mt-2">Hành động này không thể hoàn tác.</p>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Hủy bỏ</button>
                    <button type="button" class="btn btn-danger px-4" id="btnConfirmDeleteDoc">Xóa tài liệu</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ============================================================
        // Địa danh 2 cấp: Tỉnh/TP → Phường/Xã
        // ============================================================
        // ============================================================
        // Reusable Combobox Factory + Address Section Builder
        // ============================================================
        (function () {
            const API_BASE = '<?php echo BASE_URL; ?>/api/dia_danh.php';

            // ---- Combobox factory ----
            function makeCombo({ triggerEl, dropdown, searchEl, listEl, clearEl, onClear }) {
                triggerEl.addEventListener('click', () => {
                    if (triggerEl.disabled) return;
                    const isOpen = dropdown.classList.contains('open');
                    closeAll();
                    if (!isOpen) {
                        dropdown.classList.add('open');
                        searchEl.value = '';
                        searchEl.dispatchEvent(new Event('input'));
                        setTimeout(() => searchEl.focus(), 50);
                    }
                });
                searchEl.addEventListener('input', () => {
                    const q = searchEl.value.toLowerCase().trim();
                    listEl.querySelectorAll('.combo-option').forEach(opt => {
                        opt.style.display = opt.textContent.toLowerCase().includes(q) ? '' : 'none';
                    });
                    const visible = [...listEl.querySelectorAll('.combo-option')].filter(o => o.style.display !== 'none');
                    let noRes = listEl.querySelector('.no-result');
                    if (!visible.length) {
                        if (!noRes) { noRes = document.createElement('div'); noRes.className = 'combo-option no-result'; noRes.textContent = 'Không tìm thấy kết quả'; listEl.appendChild(noRes); }
                    } else { if (noRes) noRes.remove(); }
                });
                if (clearEl && onClear) clearEl.addEventListener('click', onClear);
            }

            function closeAll() {
                document.querySelectorAll('.combo-dropdown.open').forEach(d => d.classList.remove('open'));
            }
            document.addEventListener('click', e => {
                if (!e.target.closest('.combo-wrapper')) closeAll();
            });

            function renderOptions(listEl, items, onSelect) {
                listEl.innerHTML = '';
                items.forEach(item => {
                    const div = document.createElement('div');
                    div.className = 'combo-option';
                    div.textContent = item.name;
                    div.dataset.code = item.code;
                    div.addEventListener('click', () => { onSelect(item); closeAll(); });
                    listEl.appendChild(div);
                });
            }

            // ============================================================
            // Section builder for province/ward combos
            // ============================================================
            function initAddressSection(cfg) {
                const pInput = document.getElementById(cfg.provinceInputId);
                const pDropdown = document.getElementById(cfg.provinceDropdownId);
                const pSearch = document.getElementById(cfg.provinceSearchId);
                const pList = document.getElementById(cfg.provinceListId);
                const pName = document.getElementById(cfg.provinceNameId);
                const pClear = document.getElementById(cfg.provinceClearId);

                const wInput = document.getElementById(cfg.wardInputId);
                const wDropdown = document.getElementById(cfg.wardDropdownId);
                const wSearch = document.getElementById(cfg.wardSearchId);
                const wList = document.getElementById(cfg.wardListId);
                const wName = document.getElementById(cfg.wardNameId);
                const wClear = document.getElementById(cfg.wardClearId);

                const addrDetail = cfg.addressDetailId ? document.getElementById(cfg.addressDetailId) : null;

                function clearWard() { wInput.value = ''; wName.value = ''; wClear.style.display = 'none'; }
                function clearProvince() { pInput.value = ''; pName.value = ''; pClear.style.display = 'none'; clearWard(); wInput.disabled = true; }

                function selectProvince(p, skipWardReset) {
                    pInput.value = p.name; pName.value = p.name; pClear.style.display = 'block';
                    if (!skipWardReset) { clearWard(); wInput.disabled = false; }
                    fetch(`${API_BASE}?action=wards&province_code=${encodeURIComponent(p.code)}`)
                        .then(r => r.json())
                        .then(wards => renderOptions(wList, wards, selectWard));
                }
                function selectWard(w) { wInput.value = w.name; wName.value = w.name; wClear.style.display = 'block'; }

                makeCombo({ triggerEl: pInput, dropdown: pDropdown, searchEl: pSearch, listEl: pList, clearEl: pClear, onClear: clearProvince });
                makeCombo({ triggerEl: wInput, dropdown: wDropdown, searchEl: wSearch, listEl: wList, clearEl: wClear, onClear: clearWard });

                // Load provinces + restore
                fetch(API_BASE + '?action=provinces')
                    .then(r => r.json())
                    .then(provinces => {
                        renderOptions(pList, provinces, p => selectProvince(p));
                        if (addrDetail && cfg.savedAddressDetail) addrDetail.value = cfg.savedAddressDetail;
                        if (cfg.savedProvince) {
                            const match = provinces.find(p => p.name === cfg.savedProvince);
                            if (match) {
                                selectProvince(match, true);
                                fetch(`${API_BASE}?action=wards&province_code=${encodeURIComponent(match.code)}`)
                                    .then(r => r.json())
                                    .then(wards => {
                                        renderOptions(wList, wards, selectWard);
                                        wInput.disabled = false;
                                        if (cfg.savedWard) {
                                            const mw = wards.find(w => w.name === cfg.savedWard);
                                            if (mw) selectWard(mw);
                                        }
                                    });
                            }
                        }
                    })
                    .catch(() => console.warn('Không thể tải dữ liệu địa danh.'));
            }

            // ============================================================
            // 1. Địa chỉ cá nhân
            // ============================================================
            initAddressSection({
                provinceInputId: 'provinceInput', provinceDropdownId: 'provinceDropdown',
                provinceSearchId: 'provinceSearch', provinceListId: 'provinceList',
                provinceNameId: 'provinceName', provinceClearId: 'provinceClear',
                wardInputId: 'wardInput', wardDropdownId: 'wardDropdown',
                wardSearchId: 'wardSearch', wardListId: 'wardList',
                wardNameId: 'wardName', wardClearId: 'wardClear',
                addressDetailId: 'addressDetail',
                savedProvince: <?php echo json_encode($profile['province'] ?? ''); ?>,
                savedWard: <?php echo json_encode($profile['ward'] ?? ''); ?>,
                savedAddressDetail: <?php echo json_encode($profile['address_detail'] ?? ''); ?>
            });

            // ============================================================
            // 2. Địa chỉ trường THPT
            // ============================================================
            initAddressSection({
                provinceInputId: 'schoolProvinceInput', provinceDropdownId: 'schoolProvinceDropdown',
                provinceSearchId: 'schoolProvinceSearch', provinceListId: 'schoolProvinceList',
                provinceNameId: 'schoolProvinceName', provinceClearId: 'schoolProvinceClear',
                wardInputId: 'schoolWardInput', wardDropdownId: 'schoolWardDropdown',
                wardSearchId: 'schoolWardSearch', wardListId: 'schoolWardList',
                wardNameId: 'schoolWardName', wardClearId: 'schoolWardClear',
                addressDetailId: 'schoolAddressDetail',
                savedProvince: <?php echo json_encode($profile['school_province'] ?? ''); ?>,
                savedWard: <?php echo json_encode($profile['school_ward'] ?? ''); ?>,
                savedAddressDetail: <?php echo json_encode($profile['school_address_detail'] ?? ''); ?>
            });
        })();

        // ============================================================
        const GAS_URL = '<?php echo GAS_WEBAPP_URL; ?>';
        const USER_ID = '<?php echo $user_id; ?>';

        document.getElementById('uploadBtn').addEventListener('click', async function () {
            const fileInput = document.getElementById('fileInput');
            const docTypeId = document.getElementById('docTypeSelect').value;
            const statusDiv = document.getElementById('uploadStatus');
            const progress = document.getElementById('uploadProgress');

            if (!docTypeId) { showNotifyModal('Vui lòng chọn loại tài liệu!', 'warning'); return; }
            if (fileInput.files.length === 0) { showNotifyModal('Vui lòng chọn file!', 'warning'); return; }
            if (!GAS_URL) { showNotifyModal('Chưa cấu hình Google Apps Script URL trong .env!', 'danger'); return; }

            const file = fileInput.files[0];
            const reader = new FileReader();

            statusDiv.className = 'small mt-2 text-center text-brand';
            statusDiv.innerText = 'Đang mã hóa file...';
            progress.classList.remove('d-none');
            this.disabled = true;

            reader.onload = async function () {
                const base64 = reader.result.split(',')[1];
                statusDiv.innerText = 'Đang tải hồ sơ lên Google Drive...';

                try {
                    // Tên file: DOC_D{docTypeId}_{cccdLast6}_{timestamp}.{ext}
                    // Ví dụ: DOC_D3_789012_1710567890123.pdf
                    const ext = file.type === 'application/pdf' ? 'pdf' : (file.type === 'image/png' ? 'png' : 'jpg');
                    const cccdRaw = "<?php echo addslashes($profile['identity_card'] ?? '000000'); ?>";
                    const cccd6 = cccdRaw.replace(/\D/g, '').slice(-6);
                    const safeFileName = `DOC_D${docTypeId}_${cccd6}_${Date.now()}.${ext}`;

                    // 1. Upload lên Google Drive qua GAS
                    const response = await fetch(GAS_URL, {
                        method: 'POST',
                        body: JSON.stringify({
                            base64: base64,
                            fileName: safeFileName,
                            mimeType: file.type
                        })
                    });


                    const gasData = await response.json();

                    if (gasData.status === 'success') {
                        statusDiv.innerText = 'Lưu thông tin vào hệ thống...';

                        // 2. Lưu URL vào Supabase qua PHP proxy
                        const saveResponse = await fetch('save_document.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: `doc_type_id=${docTypeId}&file_url=${encodeURIComponent(gasData.webViewLink)}`
                        });

                        const saveResult = await saveResponse.json();
                        if (saveResult.success) {
                            statusDiv.className = 'small mt-2 text-center text-success';
                            statusDiv.innerText = 'Tải lên thành công!';
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            throw new Error(saveResult.message || 'Lỗi lưu database');
                        }
                    } else {
                        throw new Error(gasData.message || 'Lỗi GAS');
                    }
                } catch (err) {
                    statusDiv.className = 'small mt-2 text-center text-danger';
                    statusDiv.innerText = 'Lỗi: ' + err.message;
                    document.getElementById('uploadBtn').disabled = false;
                    progress.classList.add('d-none');
                }
            };
            reader.readAsDataURL(file);
        });

        let docIdToDelete = null;
        const confirmDeleteModal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));

        function deleteDocument(docId) {
            docIdToDelete = docId;
            confirmDeleteModal.show();
        }

        document.getElementById('btnConfirmDeleteDoc').addEventListener('click', async function () {
            if (!docIdToDelete) return;

            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Đang xóa...';

            try {
                const res = await fetch('delete_document.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: docIdToDelete })
                });
                const data = await res.json();
                if (data.success) {
                    location.reload();
                } else {
                    showNotifyModal('Lỗi: ' + data.message, 'danger');
                    confirmDeleteModal.hide();
                }
            } catch (err) {
                showNotifyModal('Lỗi kết nối khi xóa tài liệu!', 'danger');
                confirmDeleteModal.hide();
            } finally {
                this.disabled = false;
                this.innerHTML = 'Xóa tài liệu';
            }
        });
    </script>
    </div>
    </div>
    </div>
    </div>


    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>

</html>