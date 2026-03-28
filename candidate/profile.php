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
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/logo.png">
    <title>Thông tin cá nhân - Tuyển sinh</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/public.css?v=1.2">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/dashboard.css?v=1.2">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/combo.css?v=1.2">
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
                                    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm custom-alert">
                                        <i class="bi bi-check-circle-fill me-2"></i>
                                        <span><?php echo htmlspecialchars($message); ?></span>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                <?php endif; ?>
                                <?php if ($error): ?>
                                    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm custom-alert">
                                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                        <span><?php echo htmlspecialchars($error); ?></span>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" action="">

                                    <!-- ====== Section 1: Thông tin định danh ====== -->
                                    <h6 class="fw-bold mb-4 pb-2 border-bottom text-brand text-uppercase" style="font-size: .85rem; letter-spacing: .5px;"><i class="bi bi-person-vcard me-2"></i>1. Thông tin định danh</h6>

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
                                    <h6 class="fw-bold mb-4 mt-5 pb-2 border-bottom text-brand text-uppercase" style="font-size: .85rem; letter-spacing: .5px;"><i class="bi bi-house-door me-2"></i>2. Địa chỉ thường trú</h6>

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
                                    <h6 class="fw-bold mb-4 mt-5 pb-2 border-bottom text-brand text-uppercase" style="font-size: .85rem; letter-spacing: .5px;"><i class="bi bi-mortarboard me-2"></i>3. Trường THPT</h6>

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
                                                <option value="Xuất sắc" <?php echo ($profile['academic_performance'] ?? '') === 'Xuất sắc' ? 'selected' : ''; ?>>Xuất sắc(Tốt)</option>
                                                <option value="Giỏi" <?php echo ($profile['academic_performance'] ?? '') === 'Giỏi' ? 'selected' : ''; ?>>Giỏi(Tốt)</option>
                                                <option value="Khá" <?php echo ($profile['academic_performance'] ?? '') === 'Khá' ? 'selected' : ''; ?>>Khá(Khá)</option>
                                                <option value="Trung bình" <?php echo ($profile['academic_performance'] ?? '') === 'Trung bình' ? 'selected' : ''; ?>>Trung bình(Đạt)</option>
                                                <option value="Yếu" <?php echo ($profile['academic_performance'] ?? '') === 'Yếu' ? 'selected' : ''; ?>>Yếu(Chưa đạt)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mt-3 mt-md-0">
                                            <label class="form-label text-muted fw-bold">Hạnh kiểm lớp 12</label>
                                            <select name="conduct" class="form-select">
                                                <option value="">-- Chọn hạnh kiểm --</option>
                                                <option value="Tốt" <?php echo ($profile['conduct'] ?? '') === 'Tốt' ? 'selected' : ''; ?>>Tốt(Tốt)</option>
                                                <option value="Khá" <?php echo ($profile['conduct'] ?? '') === 'Khá' ? 'selected' : ''; ?>>Khá(Khá)</option>
                                                <option value="Trung bình" <?php echo ($profile['conduct'] ?? '') === 'Trung bình' ? 'selected' : ''; ?>>Trung bình(Đạt)</option>
                                                <option value="Yếu" <?php echo ($profile['conduct'] ?? '') === 'Yếu' ? 'selected' : ''; ?>>Yếu(Chưa đạt)</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- ====== Section 4: Ưu tiên ====== -->
                                    <h6 class="fw-bold mb-4 mt-5 pb-2 border-bottom text-brand text-uppercase" style="font-size: .85rem; letter-spacing: .5px;"><i class="bi bi-star me-2"></i>4. Ưu tiên</h6>

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
                                    <h6 class="fw-bold mb-4 mt-5 pb-2 border-bottom text-brand text-uppercase" style="font-size: .85rem; letter-spacing: .5px;"><i class="bi bi-award me-2"></i>5. Đã tốt nghiệp (nếu có)</h6>

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
                                    <h6 class="fw-bold mb-4 mt-5 pb-2 border-bottom text-brand text-uppercase" style="font-size: .85rem; letter-spacing: .5px;"><i class="bi bi-briefcase me-2"></i>6. Công tác hiện tại (nếu có)</h6>

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

                                    <div class="mt-5 d-flex gap-2 justify-content-end bg-light p-3 rounded-3 border">
                                        <button type="submit" class="btn btn-brand px-4 py-2 fw-semibold"><i class="bi bi-save me-2"></i>LƯU THAY ĐỔI</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <!-- Upload Section -->
                        <div class="card border-0 shadow-sm mb-4" style="border-radius:12px; background:linear-gradient(to bottom, #ffffff, #f8fafc);">
                            <div class="card-body p-4">
                                <h6 class="fw-bold text-dark mb-2"><i class="bi bi-cloud-arrow-up me-2 text-brand"></i>Tải lên tài liệu</h6>
                                <div class="text-muted small mb-4" style="line-height: 1.6;">
                                    - Các định dạng hỗ trợ: PDF, JPG, PNG.<br>
                                    - Dung lượng mỗi file không quá 30MB; <br>
                                    - Tải lần lượt từng file theo thứ tự.<br>
                                    - Đảm bảo ảnh chụp/scan rõ ràng, đầy đủ thông tin.
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted text-uppercase mb-2">1. Loại tài liệu</label>
                                    <select id="docTypeSelect" class="form-select border-0 shadow-sm" style="border-radius:8px;">
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
                                <div class="mb-4">
                                    <label class="form-label fw-bold small text-muted text-uppercase mb-2">2. Chọn tập tin</label>
                                    <input type="file" id="fileInput" class="form-control border-0 shadow-sm"
                                        accept=".pdf,.jpg,.jpeg,.png" style="border-radius:8px;">
                                </div>
                                <button id="uploadBtn" class="btn btn-brand w-100 fw-bold py-2 shadow-sm rounded-3">BẮT ĐẦU TẢI LÊN</button>

                                <!-- Progress Bar -->
                                <div id="uploadProgress" class="progress mt-3 d-none rounded-pill" style="height: 6px;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-success"
                                        style="width: 100%"></div>
                                </div>
                                <div id="uploadStatus" class="small mt-2 text-center text-muted fw-medium"></div>
                            </div>
                        </div>

                        <!-- List Section -->
                        <div class="card border-0 shadow-sm" style="border-radius:12px;">
                            <div class="card-header border-bottom-0 bg-white p-4 pb-0">
                                <h6 class="mb-0 fw-bold"><i class="bi bi-folder2-open me-2 text-brand"></i>Tài liệu đã nộp</h6>
                            </div>
                            <div class="card-body p-0">
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
                                        <div class='list-group-item d-flex justify-content-between align-items-center py-3 border-bottom px-4'>
                                            <div>
                                                <div class='fw-semibold text-dark small mb-1'>" . htmlspecialchars($typeName, ENT_QUOTES) . "</div>
                                                <a href='" . htmlspecialchars($doc['drive_file_url'] ?? '', ENT_QUOTES) . "' target='_blank' class='badge bg-light text-brand text-decoration-none border'><i class='bi bi-eye-fill me-1'></i>Xem file</a>
                                            </div>
                                            <div>
                                                <button class='btn btn-sm btn-outline-danger border-0' onclick='deleteDocument(\"{$doc['id']}\")' title='Xóa tài liệu'><i class='bi bi-trash-fill'></i></button>
                                            </div>
                                        </div>";
                                        }
                                    } else {
                                        echo "<div class='p-4 text-center text-muted small fst-italic'>Chưa có tài liệu nào.</div>";
                                    }
                                    ?>
                                </div>
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
    <script src="<?php echo BASE_URL; ?>/assets/js/address_combo.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/gas_uploader.js"></script>
    <script>
        // Address combos — sử dụng module AddressCombo dùng chung
        (function () {
            const API_BASE = '<?php echo BASE_URL; ?>/api/dia_danh.php';

            // 1. Địa chỉ cá nhân
            AddressCombo.init(API_BASE, {
                comboKey: 'provinceInput',
                provinceInputId: 'provinceInput', provinceDropdownId: 'provinceDropdown',
                provinceSearchId: 'provinceSearch', provinceListId: 'provinceList',
                provinceHiddenId: 'provinceName', provinceClearId: 'provinceClear',
                wardInputId: 'wardInput', wardDropdownId: 'wardDropdown',
                wardSearchId: 'wardSearch', wardListId: 'wardList',
                wardHiddenId: 'wardName', wardClearId: 'wardClear',
                addressDetailId: 'addressDetail',
                savedProvince: <?php echo json_encode($profile['province'] ?? ''); ?>,
                savedWard: <?php echo json_encode($profile['ward'] ?? ''); ?>,
                savedAddressDetail: <?php echo json_encode($profile['address_detail'] ?? ''); ?>
            });

            // 2. Địa chỉ trường THPT
            AddressCombo.init(API_BASE, {
                comboKey: 'schoolProvinceInput',
                provinceInputId: 'schoolProvinceInput', provinceDropdownId: 'schoolProvinceDropdown',
                provinceSearchId: 'schoolProvinceSearch', provinceListId: 'schoolProvinceList',
                provinceHiddenId: 'schoolProvinceName', provinceClearId: 'schoolProvinceClear',
                wardInputId: 'schoolWardInput', wardDropdownId: 'schoolWardDropdown',
                wardSearchId: 'schoolWardSearch', wardListId: 'schoolWardList',
                wardHiddenId: 'schoolWardName', wardClearId: 'schoolWardClear',
                savedProvince: <?php echo json_encode($profile['school_province'] ?? ''); ?>,
                savedWard: <?php echo json_encode($profile['school_ward'] ?? ''); ?>
            });
        })();

        // ============================================================
        const GAS_URL = '<?php echo GAS_WEBAPP_URL; ?>';
        const USER_ID = '<?php echo $user_id; ?>';

        document.getElementById('uploadBtn').addEventListener('click', function () {
            const docTypeId = document.getElementById('docTypeSelect').value;
            if (!docTypeId) { showNotifyModal('Vui lòng chọn loại tài liệu!', 'warning'); return; }

            GasUploader.upload({
                gasUrl: GAS_URL,
                fileInput: document.getElementById('fileInput'),
                filePrefix: 'DOC_D' + docTypeId,
                identitySuffix: "<?php echo addslashes($profile['identity_card'] ?? '000000'); ?>",
                statusEl: document.getElementById('uploadStatus'),
                progressEl: document.getElementById('uploadProgress'),
                triggerBtn: this,
                onSuccess: function(webViewLink) {
                    fetch('api/save_document.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'doc_type_id=' + docTypeId + '&file_url=' + encodeURIComponent(webViewLink)
                    })
                    .then(r => r.json())
                    .then(result => {
                        if (result.success) {
                            document.getElementById('uploadStatus').className = 'small mt-2 text-center text-success';
                            document.getElementById('uploadStatus').innerText = 'Đã tải xong!';
                            setTimeout(() => location.reload(), 1500);
                        } else { showNotifyModal('Lỗi: ' + (result.message || 'Lỗi database'), 'danger'); }
                    })
                    .catch(err => showNotifyModal('Lỗi lưu: ' + err.message, 'danger'));
                },
                onError: function(msg) { showNotifyModal(msg, 'warning'); }
            });
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
                const res = await fetch('api/delete_document.php', {
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