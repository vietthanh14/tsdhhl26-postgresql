<?php
// candidate/apply.php
session_start();
require_once __DIR__ . '/../lib/DatabaseClient.php';
require_once __DIR__ . '/../lib/CSRF.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$token = $_SESSION['access_token'] ?? null;
$supabase = new DatabaseClient('anon');

$chkUser = $supabase->select('user_profiles', "id=eq.{$user_id}", $token);
if ($chkUser['code'] == 401 || empty($chkUser['data'])) {
    session_destroy();
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}
$profileData = $chkUser['data'][0];

$supabaseAdmin = new DatabaseClient('service');
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

// Lọc trước danh sách ứng tuyển (priorities) bằng Zero-Egress pattern
$userAppsRes = $supabaseAdmin->select('applications', "select=admission_period_id,priority&user_id=eq.{$user_id}");
$userPrioritiesMap = [];
if ($userAppsRes['code'] == 200) {
    foreach ($userAppsRes['data'] as $app) {
        $pid = $app['admission_period_id'];
        if (!isset($userPrioritiesMap[$pid])) $userPrioritiesMap[$pid] = 0;
        if ($app['priority'] > $userPrioritiesMap[$pid]) $userPrioritiesMap[$pid] = $app['priority'];
    }
}

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

$csrf_token = CSRF::generateToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $redirect_url = $_SERVER['REQUEST_URI'];
    if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
        $_SESSION['apply_err'] = "Yêu cầu không hợp lệ. Vui lòng tải lại trang (Lỗi bảo mật CSRF).";
        header("Location: $redirect_url");
        exit;
    }

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
            // Tự động shift NV (tịnh tiến NV cũ xuống) nếu thêm NV mới (kể cả NV đang trùng)
            $rpcShfitRes = $supabaseAdmin->rpc('shift_application_priority', [
                'p_user_id' => $user_id,
                'p_period_id' => (string)$admission_period_id,
                'p_start_priority' => (int)$priority
            ]);

            if (!in_array($rpcShfitRes['code'], [200, 204])) {
                $errDetail = isset($rpcShfitRes['data']) ? json_encode($rpcShfitRes['data']) : 'Unknown';
                $_SESSION['apply_err'] = "Lỗi Database (RPC shift_priority): Mã " . $rpcShfitRes['code'] . " - " . $errDetail;
                header("Location: apply.php");
                exit;
            }

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
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/logo.png">
    <title>Đăng ký Xét tuyển<?php echo $form_title_suffix; ?> - Tuyển sinh Đại học Hạ Long</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Thêm Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/public.css?v=1.2">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/dashboard.css?v=1.2">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/combo.css?v=1.2">
    <style>
        .wizard-step { animation: fadeIn 0.3s; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .wizard-step .d-flex.justify-content-between { flex-direction: column; gap: 10px; }
        @media (min-width: 768px) { .wizard-step .d-flex.justify-content-between { flex-direction: row; gap: 0; } }
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
                                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3 shadow-sm"
                                        style="width: 70px; height: 70px; background: var(--brand, #1A3A6E);">
                                        <i class="bi bi-check-lg text-white" style="font-size: 2.2rem;"></i>
                                    </div>
                                    <h4 class="fw-bold text-dark">Đăng ký thành công!</h4>
                                    <p class="text-muted mb-4"><?php echo $message; ?></p>

                                    <?php if ($success_info): ?>
                                        <div class="card border-0 shadow-sm mx-auto" style="max-width: 420px; border-radius: .5rem;">
                                            <div class="card-body p-0">
                                                <div class="px-4 py-3"
                                                    style="background: var(--brand, #1A3A6E); border-radius: .5rem .5rem 0 0;">
                                                    <span class="text-white fw-bold small"><i
                                                            class="bi bi-file-earmark-check me-1"></i> Thông tin hồ sơ đã nộp</span>
                                                </div>
                                                <div class="text-start px-4 py-3 border border-top-0" style="border-radius: 0 0 .5rem .5rem;">
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
                                                            class="fw-bold text-danger"><?php echo number_format($success_info['fee'], 0, ',', '.'); ?> đ</span>
                                                    </div>
                                                </div>
                                                <?php if (!empty($success_info['zalo_link'])): ?>
                                                    <div class="px-4 py-3 text-center border border-top-0"
                                                        style="background: #f8fafc; border-radius: 0 0 .5rem .5rem;">
                                                        <p class="mb-2 small text-muted">Tham gia nhóm Zalo để nhận thông báo mới
                                                            nhất</p>
                                                        <a href="<?php echo htmlspecialchars($success_info['zalo_link']); ?>"
                                                            target="_blank" class="btn px-4 py-2 fw-bold text-white shadow-sm"
                                                            style="background: #0068ff; border: none; border-radius: 50px;">
                                                            <i class="bi bi-chat-dots-fill me-2"></i>Vào nhóm Zalo ngành
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <div class="mt-4">
                                        <a href="<?php echo BASE_URL; ?>/candidate/index.php"
                                            class="btn btn-brand px-4 py-2 shadow-sm fw-semibold"><i
                                                class="bi bi-columns-gap me-1"></i> Về Bảng điều khiển</a>
                                    </div>
                                </div>
                            <?php elseif (empty($activePeriods)): ?>
                                <div class="alert alert-warning text-center">
                                    Hiện tại nhà trường chưa mở Đợt tuyển sinh nào. Vui lòng quay lại sau!
                                </div>
                            <?php else: ?>
                                <?php if ($error): ?>
                                    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm custom-alert">
                                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                        <span><?php echo htmlspecialchars($error); ?></span>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                <?php endif; ?>

                                <!-- Component Stepper -->
                                <div class="stepper-wrapper mb-5 px-3">
                                    <div class="stepper-item active" id="stepper-1">
                                        <div class="stepper-circle">1</div>
                                        <div class="stepper-title">Chọn đợt</div>
                                    </div>
                                    <div class="stepper-item" id="stepper-2">
                                        <div class="stepper-circle">2</div>
                                        <div class="stepper-title">Rà soát</div>
                                    </div>
                                    <div class="stepper-item" id="stepper-3">
                                        <div class="stepper-circle">3</div>
                                        <div class="stepper-title">Nguyện vọng</div>
                                    </div>
                                    <div class="stepper-item" id="stepper-4">
                                        <div class="stepper-circle">4</div>
                                        <div class="stepper-title">Thanh toán</div>
                                    </div>
                                </div>

                                <div class="card border-0 shadow-sm" style="border-radius:12px;">
                                    <div class="card-body p-4 p-md-5">
                                        <form method="POST" action="">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                            <!-- Bước 1 -->
                                            <div class="wizard-step" id="step-1">
                                                <h5 class="text-brand fw-bold mb-4 text-center">Bước 1: Chọn Đợt Tuyển Sinh</h5>
                                        <div class="mb-3" <?php echo $selected_level_id ? 'style="display:none;"' : ''; ?>>
                                            <label class="form-label text-muted fw-bold">Hệ Đào tạo <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-select" id="levelSelect" <?php echo $selected_level_id ? '' : 'required'; ?> onchange="filterPeriods()">
                                                <option value="">-- Chọn Hệ đào tạo --</option>
                                                <?php foreach ($levels as $l): ?>
                                                    <option value="<?php echo $l['id']; ?>" <?php echo ($selected_level_id == $l['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($l['name']); ?>
                                                    </option>
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
                                                <h5 class="text-brand fw-bold mb-4 text-center">Bước 2: Rà soát Hồ Sơ</h5>

                                        <?php
                                        // Kiểm tra xem có trường bắt buộc nào đang bị trống không
                                        $missing_req_fields = [];
                                        foreach ($step2_fields as $f) {
                                            if (isset($f['required']) && $f['required'] === true) {
                                                $val = trim($profileData[$f['key']] ?? '');
                                                if ($val === '') {
                                                    $missing_req_fields[] = $f['label'];
                                                }
                                            }
                                        }

                                        // ==== KIỂM TRA TÀI LIỆU BẮT BUỘC THEO TỪNG HỆ ====
                                        // Dùng Zero-Egress pattern: chỉ kéo ID tài liệu đã tải lên
                                        $docsRes = $supabaseAdmin->select('user_documents', "select=document_type_id&user_id=eq.{$user_id}");
                                        $uploaded_doc_types = [];
                                        if ($docsRes['code'] == 200) {
                                            foreach ($docsRes['data'] as $doc) {
                                                $uploaded_doc_types[] = (int)$doc['document_type_id'];
                                            }
                                        }

                                        // Sử dụng cấu hình từ file wrapper, nếu không có thì mặc định chỉ bắt bản sao CCCD
                                        $required_docs = $required_doc_config ?? [2 => 'Ảnh chụp CMND/CCCD'];
                                        $missing_docs_list = [];
                                        
                                        foreach ($required_docs as $req_id => $req_name) {
                                            $is_uploaded = in_array((int)$req_id, $uploaded_doc_types);
                                            $missing_docs_list[$req_id] = [
                                                'name' => $req_name,
                                                'uploaded' => $is_uploaded
                                            ];
                                            if (!$is_uploaded) {
                                                $missing_req_fields[] = $req_name;
                                            }
                                        }

                                        $is_step2_valid = empty($missing_req_fields);
                                        ?>

                                        <div class="row mb-3 bg-light p-3 rounded mx-0 border">
                                            <!-- Hiển thị các trường Text -->
                                            <?php foreach ($step2_fields as $f): ?>
                                                <div class="col-md-6 mb-3">
                                                    <span class="text-muted small d-block mb-1">
                                                        <?php echo htmlspecialchars($f['label']); ?>
                                                        <?php if (isset($f['required']) && $f['required']): ?>
                                                            <span class="text-danger" title="Bắt buộc">*</span>
                                                        <?php endif; ?>
                                                    </span>
                                                    <?php
                                                    $val = $profileData[$f['key']] ?? '';
                                                    if ($f['key'] === 'date_of_birth' && !empty($val)) {
                                                        $val = date('d/m/Y', strtotime($val));
                                                    }
                                                    ?>
                                                    <?php if (empty($val) && isset($f['required']) && $f['required']): ?>
                                                        <strong class="text-danger fs-6"><i class="bi bi-x-circle me-1"></i>Bắt buộc cập nhật</strong>
                                                    <?php else: ?>
                                                        <strong class="text-dark fs-6"><?php echo htmlspecialchars($val ?: 'Chưa cập nhật'); ?></strong>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>

                                            <!-- Phân cách hiển thị Tình trạng Tài liệu Bắt buộc -->
                                            <div class="col-12 mt-2 pt-3 border-top border-light border-opacity-75">
                                                <h6 class="text-brand fw-bold mb-3"><i class="bi bi-folder-check me-2"></i>Tài liệu đính kèm bắt buộc</h6>
                                                
                                                <div class="row">
                                                    <?php foreach ($missing_docs_list as $doc_id => $doc_info): ?>
                                                        <div class="col-md-6 mb-3">
                                                            <span class="text-muted small d-block mb-1">
                                                                <?php echo htmlspecialchars($doc_info['name']); ?> <span class="text-danger" title="Bắt buộc">*</span>
                                                            </span>
                                                            <?php if (!$doc_info['uploaded']): ?>
                                                                <strong class="text-danger fs-6"><i class="bi bi-x-circle me-1"></i>Chưa nộp (Bắt buộc tải lên)</strong>
                                                            <?php else: ?>
                                                                <strong class="text-success fs-6"><i class="bi bi-file-earmark-check-fill me-1"></i>Đã nộp</strong>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <?php if (!$is_step2_valid): ?>
                                            <div class="alert alert-danger small py-2 mb-4">
                                                <i class="bi bi-exclamation-triangle-fill text-danger me-1"></i>
                                                Hồ sơ của bạn còn thiếu các thông tin bắt buộc: <strong><?php echo implode(', ', $missing_req_fields); ?></strong>.<br>
                                                Vui lòng <a href="<?php echo BASE_URL; ?>/candidate/profile.php" class="fw-bold text-decoration-none" target="_blank">cập nhật hồ sơ</a>, sau đó <strong>tải lại trang này</strong> để tiếp tục nộp.
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-warning small py-2 mb-4">
                                                <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>
                                                Nếu thông tin chưa chính xác, vui lòng <a
                                                    href="<?php echo BASE_URL; ?>/candidate/profile.php"
                                                    class="fw-bold text-decoration-none" target="_blank">cập nhật tại đây</a>
                                                trước khi nộp.
                                            </div>
                                        <?php endif; ?>

                                        <div class="d-flex justify-content-between">
                                            <button type="button" class="btn btn-secondary px-4 py-2 text-white fw-semibold"
                                                onclick="goToStep(1)">&laquo; Quay lại</button>
                                            <button type="button" class="btn btn-brand px-4 py-2 fw-semibold"
                                                <?php echo !$is_step2_valid ? 'disabled' : ''; ?>
                                                onclick="goToStep(3)">Tiếp tục &raquo;</button>
                                        </div>
                                    </div>

                                            <!-- Bước 3 -->
                                            <div class="wizard-step d-none" id="step-3">
                                                <h5 class="text-brand fw-bold mb-4 text-center">Bước 3: Lựa chọn Nguyện Vọng</h5>
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
                                            <label class="form-label text-muted fw-bold">Phương thức xét tuyển <span class="text-danger">*</span></label>
                                            <div class="combo-wrapper" id="methodComboWrapper">
                                                <span class="combo-clear" id="methodComboClear" title="Xóa">&times;</span>
                                                <input type="text" class="form-control combo-input" id="methodComboInput"
                                                    placeholder="-- Vui lòng chọn Ngành học trước --" readonly>
                                                <div class="combo-dropdown" id="methodComboDropdown">
                                                    <div class="combo-search">
                                                        <input type="text" id="methodComboSearch"
                                                            placeholder="🔍 Tìm phương thức..." autocomplete="off">
                                                    </div>
                                                    <div class="combo-list" id="methodComboList"></div>
                                                </div>
                                            </div>
                                            <input type="hidden" name="admission_method_id" required id="methodIdHidden" onchange="updateFeePreview()">
                                        </div>
                                        <div class="mb-4">
                                            <label class="form-label text-muted fw-bold">Thứ tự Nguyện vọng <span
                                                    class="text-danger">*</span></label>
                                            <input type="number" class="form-control" name="priority" id="priorityInput"
                                                min="1" max="10" value="1" required>
                                            <div class="form-text text-muted">Nhập số thứ tự ưu tiên.Có thể chỉnh sửa lại
                                                sau trong Bảng điều khiển.</div>
                                        </div>

                                        <div id="dupCheckMsg" class="d-none alert alert-danger py-2 mb-3"></div>
                                        <div class="d-flex justify-content-between">
                                            <button type="button" class="btn btn-secondary px-4 py-2 text-white fw-semibold"
                                                onclick="goToStep(2)">&laquo; Rà soát lại Profile</button>
                                            <button type="button" class="btn btn-brand px-4 py-2 fw-semibold"
                                                id="goToPaymentBtn" onclick="checkDupThenPay()">Tiếp tục
                                                &raquo;</button>
                                        </div>
                                    </div>

                                            <!-- Bước 4 -->
                                            <div class="wizard-step d-none" id="step-4">
                                                <h5 class="text-brand fw-bold mb-4 text-center">Bước 4: Nộp Lệ phí & Minh chứng</h5>

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
                                                <style>
                                                    .account-info-block { padding: 1rem; }
                                                    @media (min-width: 768px) { 
                                                        .account-info-block { border-left: 1px solid #e2e8f0; padding: 1.5rem !important; }
                                                    }
                                                    @media (max-width: 767.98px) { 
                                                        .account-info-block { border-top: 1px solid #e2e8f0; }
                                                    }
                                                </style>
                                                <div class="col-12 col-md-8 d-flex flex-column justify-content-center account-info-block">
                                                    <div class="row border-bottom border-light border-opacity-50 pb-2 mb-2">
                                                        <div class="col-4 text-muted small pe-1">Chủ TK</div>
                                                        <div class="col-8">
                                                            <div class="fw-bold text-dark" style="font-size:.9rem;">NGUYỄN THỊ THU HIỀN</div>
                                                            <div class="text-muted fst-italic" style="font-size:.75rem;">Phòng Đào tạo — Đại học Hạ Long</div>
                                                        </div>
                                                    </div>
                                                    <div class="row border-bottom border-light border-opacity-50 pb-2 mb-2">
                                                        <div class="col-4 text-muted small pe-1">Số TK</div>
                                                        <div class="col-8">
                                                            <span class="fw-bold text-dark" style="font-family:'Courier New',monospace; font-size:1rem; letter-spacing:1px;">0500 1012 5318 17</span>
                                                        </div>
                                                    </div>
                                                    <div class="row border-bottom border-light border-opacity-50 pb-2 mb-2">
                                                        <div class="col-4 text-muted small pe-1">Ngân hàng</div>
                                                        <div class="col-8 fw-bold text-dark" style="font-size:.85rem;">
                                                            MSB — NHTMCP Hàng Hải VN
                                                        </div>
                                                    </div>
                                                    <div class="row mb-0">
                                                        <div class="col-4 text-muted small pe-1 pt-1">Nội dung</div>
                                                        <div class="col-8">
                                                            <code class="fw-bold text-dark bg-light px-2 py-1 rounded border d-inline-block text-break" style="font-size:.85rem;" id="paymentContentText"></code>
                                                        </div>
                                                    </div>
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
                                    </div>
                                </div>
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

    <script src="<?php echo BASE_URL; ?>/assets/js/gas_uploader.js"></script>
    <script>
        // Mảng cấu hình cho Zero-Egress Auto Suggest
        const USER_PRIORITIES = <?php echo json_encode($userPrioritiesMap); ?>;

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

                // Reset combobox ngành
                selectedMajor = null;
                majorItems = [];
                document.getElementById('majorComboInput').value = '';
                document.getElementById('majorComboInput').placeholder = '-- Vui lòng chọn Đợt Tuyển Sinh trước --';
                document.getElementById('majorComboList').innerHTML = '';
                document.getElementById('majorIdHidden').value = '';
                document.getElementById('majorComboClear').style.display = 'none';

                // Reset combobox phương thức
                selectedMethod = null;
                methodItems = [];
                document.getElementById('methodComboInput').value = '';
                document.getElementById('methodComboInput').placeholder = '-- Vui lòng chọn Ngành học trước --';
                document.getElementById('methodComboList').innerHTML = '';
                document.getElementById('methodIdHidden').value = '';
                document.getElementById('methodComboClear').style.display = 'none';
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

        // ── Method Combobox state ──────────────────────────────────────
        let methodItems = [];   // [{id, name, fee}, ...]
        let selectedMethod = null;

        // ── Bước 3a: Chọn Đợt → fetch Ngành qua AJAX ─────────────────
        async function filterMajors() {
            const periodId = document.querySelector('select[name="admission_period_id"]').value;
            const comboInput = document.getElementById('majorComboInput');
            const comboList = document.getElementById('majorComboList');
            const idHidden = document.getElementById('majorIdHidden');
            const clearBtn = document.getElementById('majorComboClear');

            // Reset combobox phương thức
            selectedMethod = null;
            methodItems = [];
            document.getElementById('methodComboInput').value = '';
            document.getElementById('methodComboInput').title = '';
            document.getElementById('methodComboInput').placeholder = '-- Vui lòng chọn Ngành học trước --';
            document.getElementById('methodComboList').innerHTML = '';
            document.getElementById('methodIdHidden').value = '';
            document.getElementById('methodComboClear').style.display = 'none';
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

            // Zero-Egress Auto Suggest Priority
            const priorityInput = document.getElementById('priorityInput');
            if (USER_PRIORITIES[periodId] !== undefined) {
                priorityInput.value = USER_PRIORITIES[periodId] + 1;
            } else {
                priorityInput.value = 1;
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
                
                // Reset combobox phương thức
                selectedMethod = null;
                methodItems = [];
                document.getElementById('methodComboInput').value = '';
                document.getElementById('methodComboInput').title = '';
                document.getElementById('methodComboInput').placeholder = '-- Vui lòng chọn Ngành học trước --';
                document.getElementById('methodComboList').innerHTML = '';
                document.getElementById('methodIdHidden').value = '';
                document.getElementById('methodComboClear').style.display = 'none';
                updateFeePreview();
            });

            // Method Combobox setup
            const methodInput = document.getElementById('methodComboInput');
            const methodDropdown = document.getElementById('methodComboDropdown');
            const methodSearch = document.getElementById('methodComboSearch');
            const methodClear = document.getElementById('methodComboClear');

            if (methodInput) {
                methodInput.addEventListener('click', () => {
                    if (!methodItems.length) return;
                    methodDropdown.classList.toggle('show');
                    if (methodDropdown.classList.contains('show')) {
                        methodSearch.value = '';
                        renderMethodList('');
                        setTimeout(() => methodSearch.focus(), 50);
                    }
                });

                methodSearch.addEventListener('input', () => {
                    renderMethodList(methodSearch.value);
                });

                methodClear.addEventListener('click', (e) => {
                    e.stopPropagation();
                    selectedMethod = null;
                    methodInput.value = '';
                    methodInput.title = '';
                    document.getElementById('methodIdHidden').value = '';
                    methodClear.style.display = 'none';
                    methodDropdown.classList.remove('show');
                    updateFeePreview();
                });
            }

            // Close dropdowns when clicking outside
            document.addEventListener('click', (e) => {
                const majorWrapper = document.getElementById('majorComboWrapper');
                const methodWrapper = document.getElementById('methodComboWrapper');
                if (majorWrapper && !majorWrapper.contains(e.target)) {
                    document.getElementById('majorComboDropdown')?.classList.remove('show');
                }
                if (methodWrapper && !methodWrapper.contains(e.target)) {
                    document.getElementById('methodComboDropdown')?.classList.remove('show');
                }
            });
        });

        // ── Bước 3b: Chọn Ngành → fetch Phương thức qua AJAX ─────────
        async function filterMethods() {
            const periodId = document.querySelector('select[name="admission_period_id"]').value;
            const majorId = document.getElementById('majorIdHidden').value;
            const comboInput = document.getElementById('methodComboInput');
            const comboList = document.getElementById('methodComboList');
            const idHidden = document.getElementById('methodIdHidden');
            const clearBtn = document.getElementById('methodComboClear');

            selectedMethod = null;
            idHidden.value = '';
            clearBtn.style.display = 'none';
            comboInput.title = '';
            updateFeePreview();

            if (!periodId || !majorId) {
                comboInput.value = '';
                comboInput.placeholder = '-- Vui lòng chọn Ngành học trước --';
                comboList.innerHTML = '';
                methodItems = [];
                return;
            }

            comboInput.value = '';
            comboInput.placeholder = 'Đang tải danh sách phương thức...';
            comboList.innerHTML = '';
            methodItems = [];

            try {
                const data = await fetch(`<?php echo BASE_URL; ?>/candidate/api/methods.php?period_id=${periodId}&major_id=${majorId}`).then(r => r.json());
                
                if (!data.length) {
                    comboInput.placeholder = '-- Ngành này chưa thiết lập phương thức --';
                    return;
                }
                
                methodItems = data.map(m => ({
                    id: m.id,
                    name: m.method_name,
                    fee: m.application_fee || 0
                }));
                comboInput.placeholder = '-- Nhấn để chọn phương thức --';
                renderMethodList('');
            } catch (e) {
                comboInput.placeholder = '-- Lỗi tải dữ liệu --';
            }
        }

        function renderMethodList(filter) {
            const comboList = document.getElementById('methodComboList');
            const lower = filter.toLowerCase();
            const filtered = methodItems.filter(m => m.name.toLowerCase().includes(lower));
            comboList.innerHTML = '';
            
            if (!filtered.length) {
                comboList.innerHTML = '<div class="combo-item text-muted">Không tìm thấy phương thức phù hợp</div>';
                return;
            }
            
            const formatter = new Intl.NumberFormat('vi-VN');
            filtered.forEach(m => {
                const div = document.createElement('div');
                div.className = 'combo-item' + (selectedMethod && selectedMethod.id === m.id ? ' selected' : '');
                div.textContent = `${m.name} (${formatter.format(m.fee)}đ)`;
                div.addEventListener('click', () => selectMethod(m));
                comboList.appendChild(div);
            });
        }

        function selectMethod(m) {
            selectedMethod = m;
            document.getElementById('methodComboInput').value = m.name;
            document.getElementById('methodComboInput').title = m.name;
            document.getElementById('methodIdHidden').value = m.id;
            document.getElementById('methodComboClear').style.display = 'block';
            document.getElementById('methodComboDropdown').classList.remove('show');
            updateFeePreview();
        }

        function updateFeePreview() {
            const fee = selectedMethod ? selectedMethod.fee : 0;
            const formatter = new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' });
            const feeEl = document.getElementById('feePreview');
            if (feeEl) feeEl.innerText = formatter.format(fee);
        }

        async function checkDupThenPay() {
            const periodId = document.getElementById('periodSelect').value;
            const majorId = document.getElementById('majorIdHidden').value;
            const methodId = document.getElementById('methodIdHidden').value;
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
                const r1 = await fetch('api/check_duplicate.php', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': '<?php echo htmlspecialchars($csrf_token); ?>'
                    },
                    body: JSON.stringify({ period_id: periodId, major_id: majorId, method_id: methodId })
                });
                const d1 = await r1.json();
                if (d1.duplicate) {
                    dupMsg.classList.remove('d-none');
                    dupMsg.innerHTML = '<strong>⚠️ Trùng hồ sơ!</strong> Bạn đã đăng ký Ngành học và Phương thức Xét tuyển này trong Đợt này rồi. Vui lòng chọn lại.';
                    return;
                }

                // === Kiểm tra 2: Trùng Thứ tự Nguyện vọng trong Đợt ===
                const r2 = await fetch('api/check_priority_dup.php', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': '<?php echo htmlspecialchars($csrf_token); ?>'
                    },
                    body: JSON.stringify({ period_id: periodId, priority: parseInt(priority) })
                });
                const d2 = await r2.json();
                if (d2.duplicate) {
                    const confirmShift = await showConfirmModal(`Thứ tự NV ${priority} đã được dùng cho lô hồ sơ "${d2.taken_by}".\n\nNếu bạn làm Bước này hệ thống sẽ tự động chèn hồ sơ mới vào NV${priority} và đẩy lùi hồ sơ cũ xuống NV${parseInt(priority)+1}.\n\nBạn có chắc chắn muốn chèn hồ sơ này không?`);
                    if (!confirmShift) {
                        btn.disabled = false;
                        btn.innerText = 'Tiếp tục \xBB';
                        return;
                    }
                }

                // Tất cả đều hợp lệ → sang bước thanh toán
                goToStep(4);

            } catch (e) {
                showNotifyModal('Lỗi kiểm tra: ' + e.message, 'danger');
            } finally {
                btn.disabled = false;
                btn.innerText = 'Tiếp tục »';
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
                if (!document.getElementById('majorIdHidden').value || !document.getElementById('methodIdHidden').value) {
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

                const fee = selectedMethod ? selectedMethod.fee : 0;

                const formatter = new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' });
                document.getElementById('paymentAmountText').innerText = formatter.format(fee);
                document.getElementById('paymentContentText').innerText = content;

                // Populate tóm tắt hồ sơ
                const majorText = selectedMajor ? selectedMajor.name : '';
                const methodText = selectedMethod ? selectedMethod.name : '';
                const priority = document.getElementById('priorityInput').value;
                document.getElementById('summaryMajor').innerText = majorText;
                document.getElementById('summaryMethod').innerText = methodText;
                document.getElementById('summaryPriority').innerText = 'Nguyện vọng ' + priority;

                // VietQR: MSB (BIN 970426), STK 05001012531817, Chủ TK: NGUYEN THI THU HIEN
                const qrUrl = `https://img.vietqr.io/image/970426-05001012531817-compact2.png?amount=${fee}&addInfo=${encodeURIComponent(content)}&accountName=NGUYEN%20THI%20THU%20HIEN`;
                document.getElementById('vietqrImage').src = qrUrl;
            }

            // Update UI Stepper and Forms
            for (let i = 1; i <= 4; i++) {
                // Forms
                let el = document.getElementById('step-' + i);
                if (el) el.classList.add('d-none');
                
                // Stepper states
                let st = document.getElementById('stepper-' + i);
                if (st) {
                    st.classList.remove('active', 'completed');
                    if (i < step) st.classList.add('completed');
                    if (i === step) st.classList.add('active');
                }
            }
            // Show target
            const target = document.getElementById('step-' + step);
            if (target) {
                target.classList.remove('d-none');
                
                // Cuộn mượt mà lên vị trí stepper khi đổi bước
                const stepper = document.querySelector('.stepper-wrapper');
                if (stepper) {
                    // Trừ hao thanh điều hướng header (nếu fixed)
                    const headerOffset = 80;
                    const elementPosition = stepper.getBoundingClientRect().top;
                    const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
                    
                    window.scrollTo({
                        top: offsetPosition,
                        behavior: 'smooth'
                    });
                } else {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            }
        }

        // Upload biên lai qua GasUploader module
        document.getElementById('uploadReceiptBtn').addEventListener('click', function () {
            const periodIdVal = document.getElementById('periodSelect').value;
            const majorIdVal = document.getElementById('majorIdHidden').value;

            GasUploader.upload({
                gasUrl: GAS_URL,
                fileInput: document.getElementById('receiptFileInput'),
                filePrefix: 'BIENL_TS' + periodIdVal + 'M' + majorIdVal,
                identitySuffix: "<?php echo addslashes($profileData['identity_card'] ?? '000000'); ?>",
                statusEl: document.getElementById('uploadReceiptStatus'),
                progressEl: document.getElementById('uploadReceiptProgress'),
                triggerBtn: this,
                onSuccess: function(webViewLink) {
                    var statusEl = document.getElementById('uploadReceiptStatus');
                    statusEl.className = 'small mt-2 text-center text-success fw-bold p-2 bg-success bg-opacity-10 rounded border border-success';
                    statusEl.innerHTML = '<i class="bi bi-check-circle-fill"></i> Đã tải xong.';
                    document.getElementById('receiptUrlInput').value = webViewLink;
                    document.getElementById('submitAppBtn').disabled = false;
                },
                onError: function(msg) { showNotifyModal(msg, 'warning'); }
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>

</html>