<?php
// candidate/apply.php
session_start();
require_once __DIR__ . '/../lib/SupabaseClient.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$token = $_SESSION['access_token'] ?? null;
$supabase = new SupabaseClient('anon');

$chkUser = $supabase->select('user_profiles', "id=eq.{$user_id}", $token);
if ($chkUser['code'] == 401 || empty($chkUser['data'])) {
    session_destroy();
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}
$profileData = $chkUser['data'][0];

$supabaseAdmin = new SupabaseClient('service');
$message = '';
$error = '';

// Lấy danh sách Hệ Đào tạo (từ cache, TTL 1 giờ)
require_once __DIR__ . '/../lib/Cache.php';
$levels = Cache::remember('education_levels', 3600, function () use ($supabaseAdmin) {
    $res = $supabaseAdmin->select('education_levels', 'order=id.asc');
    return ($res['code'] == 200) ? $res['data'] : [];
});

// Hỗ trợ forced level từ các file wrapper (apply_university.php, apply_college.php…)
// Wrapper đặt $forced_level_id (integer ID) thay vì so sánh chuỗi tên — an toàn hơn khi tên thay đổi
$selected_level_id = $_GET['level_id'] ?? '';
if (!$selected_level_id && isset($forced_level_id)) {
    $selected_level_id = $forced_level_id;
}

// Lấy tên hệ để hiển thị trên tiêu đề (chỉ dùng cho UI, không dùng để lọc)
$form_title_suffix = '';
if ($selected_level_id) {
    foreach ($levels as $l) {
        if ((string) $l['id'] === (string) $selected_level_id) {
            $form_title_suffix = " Hệ {$l['name']}";
            break;
        }
    }
}

// Cấu hình bước 2 — được khai báo trong wrapper file (apply_university.php, apply_college.php…)
// Nếu không có wrapper đặt giá trị thì dùng mặc định bên dưới
if (!isset($step2_fields)) {
    $step2_fields = [
        ['label' => 'Họ và tên', 'key' => 'full_name'],
        ['label' => 'Số CMND/CCCD', 'key' => 'identity_card'],
        ['label' => 'Email liên lạc', 'key' => 'contact_email'],
        ['label' => 'Số điện thoại', 'key' => 'phone_number'],
    ];
}

// Kiểm tra xem có đợt tuyển sinh nào đang mở không (chỉ dùng để hiện cảnh báo)
$today = date('Y-m-d');
$periodsRes = $supabaseAdmin->select('admission_periods', "is_active=eq.true&end_date=gte.{$today}");
$activePeriods = ($periodsRes['code'] == 200) ? $periodsRes['data'] : [];

// Danh sách ngành/phương thức sẽ được load động qua AJAX (candidate/api/)
// Không cần fetch toàn bộ ở đây nữa
// Khi POST: fetch ngành và mapping từ DB để validate phía server
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $majorsRes = $supabaseAdmin->select('majors', 'select=*');
    $majors = ($majorsRes['code'] == 200) ? $majorsRes['data'] : [];

    $periodMajorsRes = $supabaseAdmin->select('admission_period_majors', 'select=period_id,major_id');
    $periodMajors = ($periodMajorsRes['code'] == 200) ? $periodMajorsRes['data'] : [];

    $methodsData = Cache::remember('admission_methods', 3600, function () use ($supabaseAdmin) {
        $res = $supabaseAdmin->select('admission_methods', 'select=id,method_name,application_fee');
        return ($res['code'] == 200) ? $res['data'] : [];
    });
    $methodsMap = [];
    foreach ($methodsData as $mt) {
        $methodsMap[$mt['id']] = $mt;
    }
} else {
    // Khi GET: dùng mảng rỗng, dữ liệu load qua AJAX
    $majors = [];
    $periodMajors = [];
}

$majorsMap = [];
foreach ($majors as $m) {
    $majorsMap[$m['id']] = $m;
}

// PRG pattern — đọc thông báo từ session (set bởi POST redirect)
$message = $_SESSION['apply_msg'] ?? '';
$error = $_SESSION['apply_err'] ?? '';
$success_info = $_SESSION['apply_success_info'] ?? null;
unset($_SESSION['apply_msg'], $_SESSION['apply_err'], $_SESSION['apply_success_info']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admission_period_id = $_POST['admission_period_id'] ?? '';
    $major_id = $_POST['major_id'] ?? '';
    $admission_method_id = $_POST['admission_method_id'] ?? '';
    $receipt_url = $_POST['receipt_url'] ?? '';
    $priority = intval($_POST['priority'] ?? 1);
    if ($priority < 1)
        $priority = 1;

    $redirect_url = $_SERVER['REQUEST_URI'];

    if (empty($admission_period_id) || empty($major_id) || empty($admission_method_id)) {
        $_SESSION['apply_err'] = "Vui lòng chọn đầy đủ thông tin: Đợt tuyển sinh, Ngành học và Phương thức xét tuyển.";
    } elseif (!isset($majorsMap[$major_id])) {
        $_SESSION['apply_err'] = "Ngành học không hợp lệ.";
    } elseif (empty($receipt_url)) {
        $_SESSION['apply_err'] = "Vui lòng tải lên ảnh chụp biên lai thanh toán trước khi nộp hồ sơ.";
    } elseif (!filter_var($receipt_url, FILTER_VALIDATE_URL) || !str_starts_with($receipt_url, 'https://')) {
        $_SESSION['apply_err'] = "Link biên lai không hợp lệ. Vui lòng tải lại ảnh biên lai.";
    } else {
        $fee_amount = $methodsMap[$admission_method_id]['application_fee'] ?? 0;

        // == Xác thực phía Server (backend guard) ==
        $validPeriod = false;
        foreach ($activePeriods as $ap) {
            if ((string) $ap['id'] === (string) $admission_period_id) {
                $validPeriod = true;
                break;
            }
        }
        $validMajorInPeriod = false;
        foreach ($periodMajors as $pm) {
            if ((string) $pm['period_id'] === (string) $admission_period_id && (string) $pm['major_id'] === (string) $major_id) {
                $validMajorInPeriod = true;
                break;
            }
        }

        if (!$validPeriod) {
            $_SESSION['apply_err'] = "Đợt tuyển sinh đã đóng hoặc không hợp lệ.";
        } elseif (!$validMajorInPeriod) {
            $_SESSION['apply_err'] = "Ngành học này không thuộc đợt tuyển sinh đã chọn. Vui lòng thực hiện lại từ Bước 1.";
        } else {
            $appData = [
                'user_id' => $user_id,
                'admission_period_id' => $admission_period_id,
                'major_id' => $major_id,
                'admission_method_id' => $admission_method_id,
                'fee_amount' => $fee_amount,
                'priority' => $priority,
                'status' => 'PENDING',
                'payment_status' => 'UNPAID',
                'receipt_url' => $receipt_url
            ];

            $insertRes = $supabaseAdmin->insert('applications', $appData);

            if (in_array($insertRes['code'], [201, 200])) {
                $_SESSION['apply_msg'] = "Nộp hồ sơ và Minh chứng lệ phí thành công!";
                // Lưu thông tin chi tiết để hiển thị trên màn hình thành công
                $edu_level_name = '';
                foreach ($levels as $lv) {
                    if (isset($majorsMap[$major_id]['education_level_id']) && (string) $lv['id'] === (string) $majorsMap[$major_id]['education_level_id']) {
                        $edu_level_name = $lv['name'];
                        break;
                    }
                }
                $_SESSION['apply_success_info'] = [
                    'edu_level' => $edu_level_name,
                    'major' => $majorsMap[$major_id]['major_name'] ?? '',
                    'method' => $methodsMap[$admission_method_id]['method_name'] ?? '',
                    'zalo_link' => $majorsMap[$major_id]['zalo_link'] ?? '',
                    'fee' => $fee_amount,
                ];
            } else {
                if (strpos(json_encode($insertRes['data']), 'duplicate key value') !== false) {
                    $_SESSION['apply_err'] = "Bạn đã đăng ký xét tuyển Ngành này với Phương thức này trong Đợt này rồi. Vui lòng chọn ngành hoặc phương thức khác.";
                } else {
                    $_SESSION['apply_err'] = "Lỗi không xác định khi nộp hồ sơ. Vui lòng thử lại sau.";
                }
            }
        }
    }

    // PRG: redirect về trang hiện tại bằng GET để tránh resubmit
    header("Location: $redirect_url");
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký Xét tuyển<?php echo $form_title_suffix; ?> - Tuyển sinh Đại học Hạ Long</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Thêm Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/public.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/dashboard.css">
    <style>
        /* Wizard-specific styles (apply.php only) */
        .wizard-step {
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .wizard-step .d-flex.justify-content-between {
            flex-direction: column;
            gap: 10px;
        }

        @media (min-width: 768px) {
            .wizard-step .d-flex.justify-content-between {
                flex-direction: row;
                gap: 0;
            }
        }

        /* Combobox (major search) */
        .combo-wrapper {
            position: relative;
        }

        .combo-input {
            cursor: pointer;
            background: #fff !important;
        }

        .combo-dropdown {
            display: none;
            position: absolute;
            z-index: 1050;
            width: 100%;
            background: #fff;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            margin-top: 2px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, .12);
            max-height: 300px;
            overflow: hidden;
        }

        .combo-dropdown.show {
            display: block;
        }

        .combo-search {
            padding: 8px;
            border-bottom: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            background: #fff;
        }

        .combo-search input {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 6px 10px;
            font-size: .88rem;
        }

        .combo-search input:focus {
            outline: none;
            border-color: var(--brand-color);
            box-shadow: 0 0 0 2px rgba(26, 58, 110, .15);
        }

        .combo-list {
            max-height: 230px;
            overflow-y: auto;
        }

        .combo-item {
            padding: 8px 12px;
            cursor: pointer;
            font-size: .9rem;
            border-bottom: 1px solid #f1f5f9;
            transition: background .15s;
        }

        .combo-item:hover {
            background: #e8f0fe;
        }

        .combo-item.selected {
            background: var(--brand-color);
            color: #fff;
        }

        .combo-clear {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #94a3b8;
            font-size: 1.1rem;
            z-index: 2;
            display: none;
        }

        .combo-clear:hover {
            color: #ef4444;
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <div class="row m-0 w-100 p-0 text-start" style="padding: 0; min-height: 80vh;">

        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <div class="col-md-10 content-area">
            <div
                class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-3 border-bottom gap-2">
                <h3 class="fw-bold mb-0 text-dark">Mở Hồ sơ Xét tuyển Mới</h3>
            </div>

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header py-3">
                            <h6 class="mb-0 fw-bold text-brand">Đăng ký Hồ sơ<?php echo $form_title_suffix; ?></h6>
                        </div>
                        <div class="card-body p-4">
                            <?php if ($message): ?>
                                <div class="text-center py-4">
                                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                                        style="width: 70px; height: 70px; background: linear-gradient(135deg, #16a34a, #22c55e);">
                                        <i class="bi bi-check-lg text-white" style="font-size: 2.2rem;"></i>
                                    </div>
                                    <h4 class="fw-bold text-dark">Đăng ký thành công!</h4>
                                    <p class="text-muted mb-4"><?php echo $message; ?></p>

                                    <?php if ($success_info): ?>
                                        <div class="card border-0 shadow-sm mx-auto" style="max-width: 420px;">
                                            <div class="card-body p-0">
                                                <div class="px-4 py-3"
                                                    style="background: linear-gradient(135deg, #1A3A6E, #2563eb); border-radius: .5rem .5rem 0 0;">
                                                    <span class="text-white fw-bold small"><i
                                                            class="bi bi-file-earmark-check me-1"></i> Thông tin hồ sơ đã
                                                        nộp</span>
                                                </div>
                                                <div class="text-start px-4 py-3">
                                                    <?php if (!empty($success_info['edu_level'])): ?>
                                                        <div class="d-flex justify-content-between py-2 border-bottom"
                                                            style="font-size: .88rem;">
                                                            <span class="text-muted">Hệ đào tạo</span>
                                                            <span
                                                                class="fw-bold text-dark"><?php echo htmlspecialchars($success_info['edu_level']); ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="d-flex justify-content-between py-2 border-bottom"
                                                        style="font-size: .88rem;">
                                                        <span class="text-muted">Ngành</span>
                                                        <span
                                                            class="fw-bold text-dark"><?php echo htmlspecialchars($success_info['major']); ?></span>
                                                    </div>
                                                    <?php if (!empty($success_info['method'])): ?>
                                                        <div class="d-flex justify-content-between py-2 border-bottom"
                                                            style="font-size: .88rem;">
                                                            <span class="text-muted">Phương thức</span>
                                                            <span
                                                                class="fw-bold text-dark"><?php echo htmlspecialchars($success_info['method']); ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="d-flex justify-content-between py-2" style="font-size: .88rem;">
                                                        <span class="text-muted">Lệ phí</span>
                                                        <span
                                                            class="fw-bold text-danger"><?php echo number_format($success_info['fee'], 0, ',', '.'); ?>
                                                            đ</span>
                                                    </div>
                                                </div>
                                                <?php if (!empty($success_info['zalo_link'])): ?>
                                                    <div class="px-4 py-3 text-center"
                                                        style="background: #f0fdf4; border-top: 1px solid #dcfce7;">
                                                        <p class="mb-2 small text-muted">Tham gia nhóm Zalo để nhận thông báo mới
                                                            nhất</p>
                                                        <a href="<?php echo htmlspecialchars($success_info['zalo_link']); ?>"
                                                            target="_blank" class="btn px-4 py-2 fw-bold text-white shadow-sm"
                                                            style="background: linear-gradient(135deg, #0068ff, #0098ff); border: none; border-radius: 50px;">
                                                            <i class="bi bi-chat-dots-fill me-2"></i>Vào nhóm Zalo ngành
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <div class="mt-4">
                                        <a href="<?php echo BASE_URL; ?>/candidate/index.php"
                                            class="btn btn-success px-4 py-2 shadow-sm fw-semibold"><i
                                                class="bi bi-columns-gap me-1"></i> Về Bảng điều khiển</a>
                                    </div>
                                </div>
                            <?php elseif (empty($activePeriods)): ?>
                                <div class="alert alert-warning text-center">
                                    Hiện tại nhà trường chưa mở Đợt tuyển sinh nào. Vui lòng quay lại sau!
                                </div>
                            <?php else: ?>
                                <?php if ($error): ?>
                                    <div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

                                <form method="POST" action="">
                                    <!-- Bước 1 -->
                                    <div class="wizard-step" id="step-1">
                                        <h6 class="text-brand fw-bold mb-3 border-bottom pb-2">BƯỚC 1: CHỌN ĐỢT TUYỂN SINH
                                        </h6>
                                        <div class="mb-3" <?php echo $selected_level_id ? 'style="display:none;"' : ''; ?>>
                                            <label class="form-label text-muted fw-bold">Hệ Đào tạo <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-select" id="levelSelect" <?php echo $selected_level_id ? '' : 'required'; ?> onchange="filterPeriods()">
                                                <option value="">-- Chọn Hệ đào tạo --</option>
                                                <?php foreach ($levels as $l): ?>
                                                    <option value="<?php echo $l['id']; ?>" <?php echo ($selected_level_id == $l['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($l['name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="mb-4">
                                            <label class="form-label text-muted fw-bold">Đợt Tuyển sinh <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-select" name="admission_period_id" id="periodSelect"
                                                required onchange="filterMajors()">
                                                <option value="">
                                                    <?php echo $selected_level_id ? 'Đang tải đợt tuyển sinh...' : '-- Vui lòng chọn Hệ Đào tạo trước --'; ?>
                                                </option>
                                            </select>
                                        </div>
                                        <div class="text-end">
                                            <button type="button" class="btn btn-brand px-4 py-2 fw-semibold"
                                                onclick="goToStep(2)">Tiếp tục &raquo;</button>
                                        </div>
                                    </div>

                                    <!-- Bước 2 -->
                                    <div class="wizard-step d-none" id="step-2">
                                        <h6 class="text-brand fw-bold mb-3 border-bottom pb-2">BƯỚC 2: RÀ SOÁT THÔNG TIN CÁ
                                            NHÂN</h6>
                                        <div class="row mb-3 bg-light p-3 rounded mx-0 border">
                                            <?php foreach ($step2_fields as $f): ?>
                                                <div class="col-md-6 mb-3">
                                                    <span
                                                        class="text-muted small d-block mb-1"><?php echo htmlspecialchars($f['label']); ?></span>
                                                    <?php
                                                    $val = $profileData[$f['key']] ?? '';
                                                    if ($f['key'] === 'date_of_birth' && !empty($val)) {
                                                        $val = date('d/m/Y', strtotime($val));
                                                    }
                                                    ?>
                                                    <strong
                                                        class="text-dark fs-6"><?php echo htmlspecialchars($val ?: 'Chưa cập nhật'); ?></strong>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="alert alert-warning small py-2 mb-4">
                                            <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>
                                            Nếu thông tin chưa chính xác, hệ thống có thể từ chối hồ sơ. Vui lòng <a
                                                href="<?php echo BASE_URL; ?>/candidate/profile.php"
                                                class="fw-bold text-decoration-none" target="_blank">cập nhật tại đây</a>
                                            trước khi nộp.
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <button type="button" class="btn btn-secondary px-4 py-2 text-white fw-semibold"
                                                onclick="goToStep(1)">&laquo; Xác chọn lại Đợt</button>
                                            <button type="button" class="btn btn-brand px-4 py-2 fw-semibold"
                                                onclick="goToStep(3)">Chính xác, Tiếp tục &raquo;</button>
                                        </div>
                                    </div>

                                    <!-- Bước 3 -->
                                    <div class="wizard-step d-none" id="step-3">
                                        <h6 class="text-brand fw-bold mb-3 border-bottom pb-2">BƯỚC 3: LỰA CHỌN NGUYỆN VỌNG
                                        </h6>
                                        <div class="mb-3">
                                            <label class="form-label text-muted fw-bold">Ngành học đăng ký xét tuyển <span
                                                    class="text-danger">*</span></label>
                                            <div class="combo-wrapper" id="majorComboWrapper">
                                                <span class="combo-clear" id="majorComboClear" title="Xóa">&times;</span>
                                                <input type="text" class="form-control combo-input" id="majorComboInput"
                                                    placeholder="-- Vui lòng chọn Đợt Tuyển Sinh trước --" readonly>
                                                <div class="combo-dropdown" id="majorComboDropdown">
                                                    <div class="combo-search">
                                                        <input type="text" id="majorComboSearch"
                                                            placeholder="🔍 Tìm ngành..." autocomplete="off">
                                                    </div>
                                                    <div class="combo-list" id="majorComboList"></div>
                                                </div>
                                            </div>
                                            <input type="hidden" name="major_id" id="majorIdHidden" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label text-muted fw-bold">Phương thức xét tuyển <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-select" name="admission_method_id" required
                                                id="methodSelect" onchange="updateFeePreview()">
                                                <option value="">-- Vui lòng chọn Ngành học trước --</option>
                                            </select>
                                        </div>
                                        <div class="mb-4">
                                            <label class="form-label text-muted fw-bold">Thứ tự Nguyện vọng <span
                                                    class="text-danger">*</span></label>
                                            <input type="number" class="form-control" name="priority" id="priorityInput"
                                                min="1" max="10" value="1" required>
                                            <div class="form-text text-muted">Nhập số thứ tự ưu tiên (Nguyện vọng 1 là ưu
                                                tiên cao nhất). Có thể chỉnh sửa lại sau trong Bảng điều khiển.</div>
                                        </div>

                                        <div id="dupCheckMsg" class="d-none alert alert-danger py-2 mb-3"></div>
                                        <div class="d-flex justify-content-between">
                                            <button type="button" class="btn btn-secondary px-4 py-2 text-white fw-semibold"
                                                onclick="goToStep(2)">&laquo; Rà soát lại Profile</button>
                                            <button type="button" class="btn btn-brand px-4 py-2 fw-semibold"
                                                id="goToPaymentBtn" onclick="checkDupThenPay()">Thanh toán bằng VietQR
                                                &raquo;</button>
                                        </div>
                                    </div>

                                    <!-- Bước 4 -->
                                    <div class="wizard-step d-none" id="step-4">
                                        <h6 class="text-brand fw-bold mb-3 border-bottom pb-2">BƯỚC 4: THANH TOÁN LỆ PHÍ
                                        </h6>

                                        <!-- Tóm tắt đăng ký -->
                                        <div class="p-3 mb-3 rounded border"
                                            style="background: rgba(26,58,110,.04); border-color: #e2e8f0 !important;">
                                            <div class="fw-bold text-dark" style="font-size:.92rem;" id="summaryMajor">
                                            </div>
                                            <div class="text-muted" style="font-size:.82rem;">
                                                <span id="summaryMethod"></span> · <span id="summaryPriority"></span>
                                            </div>
                                        </div>

                                        <!-- Thanh toán -->
                                        <div class="border rounded-3 overflow-hidden mb-3"
                                            style="border-color:#e2e8f0 !important;">
                                            <!-- Header -->
                                            <div class="px-3 py-2 d-flex align-items-center justify-content-between"
                                                style="background: #1A3A6E;">
                                                <span class="text-white fw-bold" style="font-size:.85rem;"><i
                                                        class="bi bi-bank me-1"></i>Chuyển khoản ngân hàng</span>

                                            </div>

                                            <!-- Nội dung: QR + Thông tin -->
                                            <div class="row g-0">
                                                <!-- QR -->
                                                <div class="col-12 col-md-4 text-center p-3 d-flex align-items-center justify-content-center"
                                                    style="background:#f8fafc;">
                                                    <img id="vietqrImage" src="" alt="VietQR" class="rounded"
                                                        style="max-width:200px; width:100%; height:auto;">
                                                    <span id="paymentAmountText" class="d-none"></span>
                                                </div>

                                                <!-- Thông tin TK -->
                                                <div class="col-12 col-md-8 p-4 d-flex align-items-center"
                                                    style="border-left:1px solid #e2e8f0;">
                                                    <table class="table table-borderless mb-0 w-100"
                                                        style="font-size:.9rem;">
                                                        <tr>
                                                            <td class="text-muted py-2 pe-3"
                                                                style="width:130px; white-space:nowrap;">Chủ tài khoản</td>
                                                            <td class="py-2">
                                                                <span class="fw-bold text-dark"
                                                                    style="font-size:.95rem;">NGUYỄN THỊ THU HIỀN</span><br>
                                                                <span class="text-muted fst-italic"
                                                                    style="font-size:.78rem;">Phòng Đào tạo — Trường Đại học
                                                                    Hạ Long</span>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td colspan="2" class="py-0">
                                                                <hr class="my-0" style="opacity:.1;">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td class="text-muted py-2 pe-3">Số tài khoản</td>
                                                            <td class="py-2">
                                                                <span class="fw-bold text-dark"
                                                                    style="font-family:'Courier New',monospace; font-size:1rem; letter-spacing:1.5px;">0500
                                                                    1012 5318 17</span>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td colspan="2" class="py-0">
                                                                <hr class="my-0" style="opacity:.1;">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td class="text-muted py-2 pe-3">Ngân hàng</td>
                                                            <td class="fw-bold text-dark py-2" style="font-size:.95rem;">MSB
                                                                — Ngân hàng Thương mại Cổ phần Hàng Hải Việt Nam</td>
                                                        </tr>
                                                        <tr>
                                                            <td colspan="2" class="py-0">
                                                                <hr class="my-0" style="opacity:.1;">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td class="text-muted py-2 pe-3" style="white-space:nowrap;">Nội
                                                                dung chuyển khoản</td>
                                                            <td class="py-2">
                                                                <code
                                                                    class="fw-bold text-dark bg-light px-2 py-1 rounded border"
                                                                    style="font-size:.92rem;"
                                                                    id="paymentContentText"></code>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </div>
                                            </div>

                                            <!-- Cảnh báo -->
                                            <div class="px-3 py-2 text-center"
                                                style="background:#fffbeb; border-top:1px solid #fde68a;">
                                                <small class="text-muted"><i
                                                        class="bi bi-shield-check text-success me-1"></i>Kiểm tra tên người
                                                    nhận: <strong>NGUYEN THI THU HIEN</strong> trước khi chuyển.</small>
                                            </div>
                                        </div>

                                        <!-- Upload biên lai -->
                                        <div class="border rounded-3 p-3 mb-3" style="border-color:#e2e8f0 !important;">
                                            <label class="form-label fw-bold text-dark mb-2" style="font-size:.88rem;">
                                                <i class="bi bi-cloud-arrow-up-fill text-brand me-1"></i>Tải ảnh biên lai
                                                chuyển khoản <span class="text-danger">*</span>
                                            </label>
                                            <div class="d-flex gap-2">
                                                <input type="file" id="receiptFileInput"
                                                    class="form-control form-control-sm" accept=".jpg,.jpeg,.png">
                                                <input type="hidden" name="receipt_url" id="receiptUrlInput" required>
                                                <button type="button" id="uploadReceiptBtn"
                                                    class="btn btn-brand btn-sm fw-bold px-3 flex-shrink-0">
                                                    <i class="bi bi-upload me-1"></i>Tải lên
                                                </button>
                                            </div>
                                            <div id="uploadReceiptProgress" class="progress mt-2 d-none"
                                                style="height:5px;">
                                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-success"
                                                    style="width:100%"></div>
                                            </div>
                                            <div id="uploadReceiptStatus" class="small mt-1 text-muted"
                                                style="font-size:.78rem;">Ảnh cần rõ số tiền và nội dung chuyển khoản.</div>
                                        </div>

                                        <!-- Nút điều hướng -->
                                        <div class="d-flex justify-content-between">
                                            <button type="button" class="btn btn-secondary px-4 py-2 text-white fw-semibold"
                                                onclick="goToStep(3)">&laquo; Sửa Nguyện vọng</button>
                                            <button type="submit" class="btn btn-success px-4 py-2 fw-bold shadow-sm"
                                                id="submitAppBtn" disabled>
                                                <i class="bi bi-check-circle me-1"></i>NỘP HỒ SƠ CHÍNH THỨC
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php if (!empty($step1_info)): ?>
                    <div class="col-md-4">
                        <div class="card" style="position: sticky; top: 80px;">
                            <div class="card-header py-3">
                                <h6 class="mb-0 fw-bold"><i class="bi bi-info-circle me-1"></i> Thông tin hệ đào tạo</h6>
                            </div>
                            <div class="card-body">
                                <?php echo $step1_info; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    </div>

    <script>
        const GAS_URL = '<?php echo GAS_WEBAPP_URL; ?>';
        const USER_ID = '<?php echo $user_id; ?>';

        window.addEventListener('DOMContentLoaded', () => {
            if (document.getElementById('levelSelect')) {
                if (document.getElementById('levelSelect').value !== '') {
                    filterPeriods();
                }
            }
        });

        // ── HELPER ────────────────────────────────────────────────────
        function setLoading(sel, text = 'Đang tải...') {
            sel.innerHTML = `<option value="">${text}</option>`;
            sel.disabled = true;
        }
        function resetSelect(sel, text) {
            sel.innerHTML = `<option value="">${text}</option>`;
            sel.disabled = false;
        }

        // ── Bước 1: Chọn Hệ → fetch Đợt qua AJAX ─────────────────────
        async function filterPeriods() {
            const periodSelect = document.getElementById('periodSelect');
            try {
                const levelId = document.getElementById('levelSelect').value;
                const methodSelect = document.getElementById('methodSelect');

                // Reset combobox ngành
                selectedMajor = null;
                majorItems = [];
                document.getElementById('majorComboInput').value = '';
                document.getElementById('majorComboInput').placeholder = '-- Vui lòng chọn Đợt Tuyển Sinh trước --';
                document.getElementById('majorComboList').innerHTML = '';
                document.getElementById('majorIdHidden').value = '';
                document.getElementById('majorComboClear').style.display = 'none';
                resetSelect(methodSelect, '-- Vui lòng chọn Ngành học trước --');
                updateFeePreview();

                if (!levelId) { resetSelect(periodSelect, '-- Vui lòng chọn Hệ Đào tạo trước --'); return; }

                setLoading(periodSelect, 'Đang tải đợt tuyển sinh...');
                const data = await fetch(`<?php echo BASE_URL; ?>/candidate/api/periods.php?level_id=${levelId}`).then(r => r.json());
                periodSelect.disabled = false;
                if (!data.length) { resetSelect(periodSelect, '-- Hệ này chưa mở đợt tuyển sinh nào --'); return; }
                periodSelect.innerHTML = '<option value="">-- Chọn Đợt Tuyển Sinh --</option>';
                data.forEach(p => periodSelect.appendChild(new Option(p.name, p.id)));
            } catch (e) {
                console.error('filterPeriods error:', e);
                resetSelect(periodSelect, '-- Lỗi tải dữ liệu --');
            }
        }

        // ── Major Combobox state ──────────────────────────────────────
        let majorItems = [];   // [{id, name, fee}, ...]
        let selectedMajor = null;

        // ── Bước 3a: Chọn Đợt → fetch Ngành qua AJAX ─────────────────
        async function filterMajors() {
            const periodId = document.querySelector('select[name="admission_period_id"]').value;
            const methodSelect = document.getElementById('methodSelect');
            const comboInput = document.getElementById('majorComboInput');
            const comboList = document.getElementById('majorComboList');
            const idHidden = document.getElementById('majorIdHidden');
            const clearBtn = document.getElementById('majorComboClear');

            resetSelect(methodSelect, '-- Vui lòng chọn Ngành học trước --');
            selectedMajor = null;
            idHidden.value = '';
            clearBtn.style.display = 'none';
            updateFeePreview();

            if (!periodId) {
                comboInput.value = '';
                comboInput.placeholder = '-- Vui lòng chọn Đợt Tuyển Sinh trước --';
                comboList.innerHTML = '';
                majorItems = [];
                return;
            }

            comboInput.value = '';
            comboInput.placeholder = 'Đang tải danh sách ngành...';
            comboList.innerHTML = '';
            majorItems = [];

            try {
                const data = await fetch(`<?php echo BASE_URL; ?>/candidate/api/majors.php?period_id=${periodId}`).then(r => r.json());
                if (!data.length) {
                    comboInput.placeholder = '-- Đợt này chưa mở ngành nào --';
                    return;
                }
                majorItems = data.map(m => ({ id: m.id, name: m.name }));
                comboInput.placeholder = '-- Nhấn để chọn ngành --';
                renderMajorList('');
            } catch (e) {
                comboInput.placeholder = '-- Lỗi tải dữ liệu --';
            }
        }

        function renderMajorList(filter) {
            const comboList = document.getElementById('majorComboList');
            const lower = filter.toLowerCase();
            const filtered = majorItems.filter(m => m.name.toLowerCase().includes(lower));
            comboList.innerHTML = '';
            if (!filtered.length) {
                comboList.innerHTML = '<div class="combo-item text-muted">Không tìm thấy ngành phù hợp</div>';
                return;
            }
            filtered.forEach(m => {
                const div = document.createElement('div');
                div.className = 'combo-item' + (selectedMajor && selectedMajor.id === m.id ? ' selected' : '');
                div.textContent = m.name;
                div.addEventListener('click', () => selectMajor(m));
                comboList.appendChild(div);
            });
        }

        function selectMajor(m) {
            selectedMajor = m;
            document.getElementById('majorComboInput').value = m.name;
            document.getElementById('majorIdHidden').value = m.id;
            document.getElementById('majorComboClear').style.display = 'block';
            document.getElementById('majorComboDropdown').classList.remove('show');
            updateFeePreview();
            filterMethods();
        }

        // Combobox interactions
        document.addEventListener('DOMContentLoaded', () => {
            const comboInput = document.getElementById('majorComboInput');
            const comboDropdown = document.getElementById('majorComboDropdown');
            const comboSearch = document.getElementById('majorComboSearch');
            const comboClear = document.getElementById('majorComboClear');

            if (!comboInput) return;

            comboInput.addEventListener('click', () => {
                if (!majorItems.length) return;
                comboDropdown.classList.toggle('show');
                if (comboDropdown.classList.contains('show')) {
                    comboSearch.value = '';
                    renderMajorList('');
                    setTimeout(() => comboSearch.focus(), 50);
                }
            });

            comboSearch.addEventListener('input', () => {
                renderMajorList(comboSearch.value);
            });

            comboClear.addEventListener('click', (e) => {
                e.stopPropagation();
                selectedMajor = null;
                comboInput.value = '';
                document.getElementById('majorIdHidden').value = '';
                document.getElementById('majorFeeHidden').value = '0';
                comboClear.style.display = 'none';
                comboDropdown.classList.remove('show');
                resetSelect(document.getElementById('methodSelect'), '-- Vui lòng chọn Ngành học trước --');
                updateFeePreview();
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (!document.getElementById('majorComboWrapper').contains(e.target)) {
                    comboDropdown.classList.remove('show');
                }
            });
        });

        // ── Bước 3b: Chọn Ngành → fetch Phương thức qua AJAX ─────────
        async function filterMethods() {
            const periodId = document.querySelector('select[name="admission_period_id"]').value;
            const majorId = document.getElementById('majorIdHidden').value;
            const methodSelect = document.getElementById('methodSelect');

            if (!periodId || !majorId) { resetSelect(methodSelect, '-- Vui lòng chọn Ngành học trước --'); updateFeePreview(); return; }

            setLoading(methodSelect, 'Đang tải phương thức...');
            try {
                const data = await fetch(`<?php echo BASE_URL; ?>/candidate/api/methods.php?period_id=${periodId}&major_id=${majorId}`).then(r => r.json());
                methodSelect.disabled = false;
                if (!data.length) { resetSelect(methodSelect, '-- Ngành này chưa thiết lập phương thức --'); updateFeePreview(); return; }
                methodSelect.innerHTML = '<option value="" data-fee="0">-- Chọn phương thức xét tuyển --</option>';
                const formatter = new Intl.NumberFormat('vi-VN');
                data.forEach(m => {
                    const opt = new Option(`${m.method_name} (${formatter.format(m.application_fee || 0)}đ)`, m.id);
                    opt.dataset.fee = m.application_fee || 0;
                    methodSelect.appendChild(opt);
                });
                updateFeePreview();
            } catch (e) { resetSelect(methodSelect, '-- Lỗi tải dữ liệu --'); }
        }

        function updateFeePreview() {
            const methodSelect = document.getElementById('methodSelect');
            const selectedOption = methodSelect.options[methodSelect.selectedIndex];
            const fee = selectedOption ? (selectedOption.dataset.fee || 0) : 0;
            const formatter = new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' });
            const feeEl = document.getElementById('feePreview');
            if (feeEl) feeEl.innerText = formatter.format(fee);
        }

        async function checkDupThenPay() {
            const periodId = document.getElementById('periodSelect').value;
            const majorId = document.getElementById('majorIdHidden').value;
            const methodId = document.getElementById('methodSelect').value;
            const priority = document.getElementById('priorityInput').value;
            const dupMsg = document.getElementById('dupCheckMsg');
            const btn = document.getElementById('goToPaymentBtn');

            if (!majorId || !methodId) {
                showNotifyModal('Vui lòng chọn Ngành học và Phương thức xét tuyển trước!', 'warning');
                return;
            }
            if (!priority || parseInt(priority) < 1) {
                showNotifyModal('Vui lòng nhập Thứ tự Nguyện vọng hợp lệ (tối thiểu là 1)!', 'warning');
                return;
            }

            btn.disabled = true;
            btn.innerText = 'Đang kiểm tra...';
            dupMsg.classList.add('d-none');

            try {
                // === Kiểm tra 1: Trùng Ngành + Phương thức trong Đợt ===
                const r1 = await fetch('check_duplicate.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ period_id: periodId, major_id: majorId, method_id: methodId })
                });
                const d1 = await r1.json();
                if (d1.duplicate) {
                    dupMsg.classList.remove('d-none');
                    dupMsg.innerHTML = '<strong>⚠️ Trùng hồ sơ!</strong> Bạn đã đăng ký Ngành học và Phương thức Xét tuyển này trong Đợt này rồi. Vui lòng chọn lại.';
                    return;
                }

                // === Kiểm tra 2: Trùng Thứ tự Nguyện vọng trong Đợt ===
                const r2 = await fetch('check_priority_dup.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ period_id: periodId, priority: parseInt(priority) })
                });
                const d2 = await r2.json();
                if (d2.duplicate) {
                    dupMsg.classList.remove('d-none');
                    dupMsg.innerHTML = `<strong>⚠️ Trùng thứ tự Nguyện vọng!</strong> Nguyện vọng <b>${priority}</b> đã được dùng cho ngành <em>${d2.taken_by}</em>. Bạn có thể đổi sang nguyện vọng khác hoặc thay đổi thứ tự sau trong Bảng điều khiển.`;
                    return;
                }

                // Tất cả đều hợp lệ → sang bước thanh toán
                goToStep(4);

            } catch (e) {
                showNotifyModal('Lỗi kiểm tra: ' + e.message, 'danger');
            } finally {
                btn.disabled = false;
                btn.innerText = 'Thanh toán bằng VietQR »';
            }
        }

        function goToStep(step) {
            // Validation trước khi sang step tiếp
            if (step === 2) {
                if (!document.getElementById('periodSelect').value) {
                    showNotifyModal('Vui lòng chọn Đợt tuyển sinh trước!', 'warning');
                    return;
                }
            }
            if (step === 4) {
                if (!document.getElementById('majorIdHidden').value || !document.getElementById('methodSelect').value) {
                    showNotifyModal('Vui lòng chọn Ngành học và Phương thức xét tuyển trước!', 'warning');
                    return;
                }

                // Tạo mã nội dung chuyển khoản ngắn gọn, truy vết được
                // Định dạng: TS{periodId}M{majorId} {6 số cuối CCCD}
                // Ví dụ: TS1M24 789012  — luôn < 25 ký tự, không cần chuẩn hóa tiếng Việt
                const periodId = document.getElementById('periodSelect').value;
                const majorId = document.getElementById('majorIdHidden').value;
                const cccd = "<?php echo addslashes($profileData['identity_card'] ?? '000000000000'); ?>";
                const cccdLast6 = cccd.replace(/\D/g, '').slice(-6); // 6 số cuối CCCD
                const content = `TS${periodId}M${majorId} ${cccdLast6}`;

                const methodSel = document.getElementById('methodSelect');
                const fee = methodSel.options[methodSel.selectedIndex].dataset.fee || 0;

                const formatter = new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' });
                document.getElementById('paymentAmountText').innerText = formatter.format(fee);
                document.getElementById('paymentContentText').innerText = content;

                // Populate tóm tắt hồ sơ
                const majorText = selectedMajor ? selectedMajor.name : '';
                const methodText = methodSel.options[methodSel.selectedIndex].text;
                const priority = document.getElementById('priorityInput').value;
                document.getElementById('summaryMajor').innerText = majorText;
                document.getElementById('summaryMethod').innerText = methodText;
                document.getElementById('summaryPriority').innerText = 'Nguyện vọng ' + priority;

                // VietQR: MSB (BIN 970426), STK 05001012531817, Chủ TK: NGUYEN THI THU HIEN
                const qrUrl = `https://img.vietqr.io/image/970426-05001012531817-compact2.png?amount=${fee}&addInfo=${encodeURIComponent(content)}&accountName=NGUYEN%20THI%20THU%20HIEN`;
                document.getElementById('vietqrImage').src = qrUrl;
            }

            // Hide all
            for (let i = 1; i <= 4; i++) {
                let el = document.getElementById('step-' + i);
                if (el) el.classList.add('d-none');
            }
            // Show target
            const target = document.getElementById('step-' + step);
            if (target) target.classList.remove('d-none');
        }

        // Google Apps Script File Upload
        document.getElementById('uploadReceiptBtn').addEventListener('click', async function () {
            const fileInput = document.getElementById('receiptFileInput');
            const statusDiv = document.getElementById('uploadReceiptStatus');
            const progress = document.getElementById('uploadReceiptProgress');
            const urlInput = document.getElementById('receiptUrlInput');
            const submitBtn = document.getElementById('submitAppBtn');

            if (fileInput.files.length === 0) { showNotifyModal('Vui lòng chọn file ảnh biên lai!', 'warning'); return; }
            if (!GAS_URL) { showNotifyModal('Hệ thống chưa cấu hình Link Upload Google Drive. Vui lòng báo quản trị.', 'warning'); return; }

            const file = fileInput.files[0];
            const reader = new FileReader();

            statusDiv.className = 'small mt-2 text-center text-brand fw-bold';
            statusDiv.innerText = 'Đang đọc thẻ và tải ảnh lên hệ thống...';
            progress.classList.remove('d-none');
            this.disabled = true;

            reader.onload = async function () {
                const base64 = reader.result.split(',')[1];

                // Tên file: BIENL_TS{periodId}M{majorId}_{cccdLast6}_{timestamp}.{ext}
                // Đồng nhất với mã nội dung chuyển khoản → dễ đối soát trên Google Drive
                const ext = file.type === 'image/png' ? 'png' : 'jpg';
                const periodIdVal = document.getElementById('periodSelect').value;
                const majorIdVal = document.getElementById('majorIdHidden').value;
                const cccdRaw = "<?php echo addslashes($profileData['identity_card'] ?? '000000'); ?>";
                const cccdSuffix = cccdRaw.replace(/\D/g, '').slice(-6);
                const safeFileName = `BIENL_TS${periodIdVal}M${majorIdVal}_${cccdSuffix}_${Date.now()}.${ext}`;

                try {
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
                        statusDiv.className = 'small mt-2 text-center text-success fw-bold p-2 bg-success bg-opacity-10 rounded border border-success';
                        statusDiv.innerHTML = '<i class="bi bi-check-circle-fill"></i> Tải Biên lai thành công! Ảnh đã được ghim vào hồ sơ. Bạn có thể Nộp hồ sơ.';
                        urlInput.value = gasData.webViewLink; // Gắn URL ẩn vào form
                        submitBtn.disabled = false; // Mở khóa nút Nộp Hồ sơ
                    } else {
                        throw new Error(gasData.message || 'Lưu Google Drive thất bại.');
                    }
                } catch (err) {
                    statusDiv.className = 'small mt-2 text-center text-danger fw-bold';
                    statusDiv.innerText = 'Lỗi Tải file: ' + err.message;
                    document.getElementById('uploadReceiptBtn').disabled = false;
                    progress.classList.add('d-none');
                }
            };
            reader.readAsDataURL(file);
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    </div><!-- /container-fluid -->
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>

</html>