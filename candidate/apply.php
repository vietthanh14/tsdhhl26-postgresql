<?php
// candidate/apply.php
session_start();
require_once __DIR__ . '/../lib/SupabaseClient.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /tsdhhl26/auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$token = $_SESSION['access_token'] ?? null;
$supabase = new SupabaseClient('anon');

$chkUser = $supabase->select('user_profiles', "id=eq.{$user_id}", $token);
if ($chkUser['code'] == 401 || empty($chkUser['data'])) {
    session_destroy();
    header('Location: /tsdhhl26/auth/login.php');
    exit;
}
$profileData = $chkUser['data'][0];

$supabaseAdmin = new SupabaseClient('service');
$message = '';
$error = '';

// Lấy danh sách Đợt tuyển sinh đang mở
$periodsRes = $supabase->select('admission_periods', 'is_active=eq.true&order=created_at.desc');
$activePeriods = ($periodsRes['code'] == 200) ? $periodsRes['data'] : [];

// Lấy danh sách Hệ Đào tạo
$levelsRes = $supabase->select('education_levels', 'order=id.asc');
$levels = ($levelsRes['code'] == 200) ? $levelsRes['data'] : [];

// Hỗ trợ forced level từ các file wrapper (apply_university.php, apply_college.php…)
$selected_level_id = $_GET['level_id'] ?? '';
if (!$selected_level_id && isset($forced_level_name)) {
    foreach ($levels as $l) {
        if (mb_strtolower(trim($l['name'])) === mb_strtolower(trim($forced_level_name))) {
            $selected_level_id = $l['id'];
            break;
        }
    }
}
$form_title_suffix = isset($forced_level_name) ? " Hệ {$forced_level_name}" : '';

// Cấu hình bước 2 — được khai báo trong wrapper file (apply_university.php, apply_college.php…)
// Nếu không có wrapper đặt giá trị thì dùng mặc định bên dưới
if (!isset($step2_fields)) {
    $step2_fields = [
        ['label' => 'Họ và tên',      'key' => 'full_name'],
        ['label' => 'Số CMND/CCCD',   'key' => 'identity_card'],
        ['label' => 'Email liên lạc', 'key' => 'contact_email'],
        ['label' => 'Số điện thoại',  'key' => 'phone_number'],
    ];
}

// Lấy danh sách Ngành học (lọc theo hệ nếu đã xác định — giảm tải dữ liệu gửi xuống trình duyệt)
$majorsQuery = 'select=*,education_levels(name)&order=major_name.asc';
if ($selected_level_id) {
    $majorsQuery .= "&education_level_id=eq.{$selected_level_id}";
}
$majorsRes = $supabase->select('majors', $majorsQuery);
$majors = ($majorsRes['code'] == 200) ? $majorsRes['data'] : [];

// Lấy danh sách Phương thức
$methodsRes = $supabaseAdmin->select('admission_methods', 'order=id.asc');
$methods = ($methodsRes['code'] == 200) ? $methodsRes['data'] : [];

$periodMajorsRes = $supabaseAdmin->select('admission_period_majors', 'select=*');
$periodMajors = ($periodMajorsRes['code'] == 200) ? $periodMajorsRes['data'] : [];

$periodMajorMethodsRes = $supabaseAdmin->select('admission_period_major_methods', 'select=period_id,major_id,method_id');
$periodMajorMethods = ($periodMajorMethodsRes['code'] == 200) ? $periodMajorMethodsRes['data'] : [];

$majorsMap = [];
foreach ($majors as $m) {
    $majorsMap[$m['id']] = $m;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admission_period_id = $_POST['admission_period_id'] ?? '';
    $major_id = $_POST['major_id'] ?? '';
    $admission_method_id = $_POST['admission_method_id'] ?? '';
    $receipt_url = $_POST['receipt_url'] ?? '';
    $priority = intval($_POST['priority'] ?? 1);
    if ($priority < 1) $priority = 1;

    if (empty($admission_period_id) || empty($major_id) || empty($admission_method_id)) {
        $error = "Vui lòng chọn đầy đủ thông tin: Đợt tuyển sinh, Ngành học và Phương thức xét tuyển.";
    } elseif (!isset($majorsMap[$major_id])) {
        $error = "Ngành học không hợp lệ.";
    } elseif (empty($receipt_url)) {
        $error = "Vui lòng tải lên ảnh chụp biên lai thanh toán trước khi nộp hồ sơ.";
    } elseif (!filter_var($receipt_url, FILTER_VALIDATE_URL) || !str_starts_with($receipt_url, 'https://')) {
        $error = "Link biên lai không hợp lệ. Vui lòng tải lại ảnh biên lai.";
    } else {
        $fee_amount = $majorsMap[$major_id]['application_fee'];

        // == Xác thực phía Server (backend guard) ==
        // 1. Kiểm tra đợt tuyển sinh còn mở không
        $validPeriod = false;
        foreach ($activePeriods as $ap) {
            if ((string)$ap['id'] === (string)$admission_period_id) { $validPeriod = true; break; }
        }
        // 2. Kiểm tra ngành có thuộc đợt tuyển sinh không
        $validMajorInPeriod = false;
        foreach ($periodMajors as $pm) {
            if ((string)$pm['period_id'] === (string)$admission_period_id && (string)$pm['major_id'] === (string)$major_id) {
                $validMajorInPeriod = true; break;
            }
        }

        if (!$validPeriod) {
            $error = "Đợt tuyển sinh đã đóng hoặc không hợp lệ.";
        } elseif (!$validMajorInPeriod) {
            $error = "Ngành học này không thuộc đợt tuyển sinh đã chọn. Vui lòng thực hiện lại từ Bước 1.";
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
                $message = "Nộp hồ sơ và Minh chứng lệ phí thành công! Mời bạn quay lại Bảng điều khiển để theo dõi kết quả.";
            } else {
                if (strpos(json_encode($insertRes['data']), 'duplicate key value') !== false) {
                    $error = "Bạn đã đăng ký xét tuyển Ngành này với Phương thức này trong Đợt này rồi. Vui lòng chọn ngành hoặc phương thức khác.";
                } else {
                    $error = "Lỗi không xác định khi nộp hồ sơ. Vui lòng thử lại sau.";
                }
            }
        }
    }
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
    <style>
        :root {
            --brand-color: #1A3A6E;
            --brand-hover: #12284c;
            --sidebar-bg: #0f2444; 
            --bg-color: #f7f9fc;
            --border-radius: 4px;
        }
        body { background-color: var(--bg-color); font-family: 'Inter', sans-serif; color: #333; }
        .sidebar { background-color: var(--sidebar-bg); min-height: 100vh; padding-top: 25px; box-shadow: 2px 0 10px rgba(0,0,0,0.05); }
        .sidebar a { color: #cbd5e1; text-decoration: none; padding: 12px 24px; display: block; border-left: 3px solid transparent; font-weight: 500; transition: all 0.2s; }
        .sidebar a:hover, .sidebar a.active { background-color: rgba(255,255,255,0.05); color: #fff; border-left-color: #3b82f6; }
        .content-area { padding: 40px; }
        .card { border: 1px solid #e2e8f0; border-radius: var(--border-radius); box-shadow: 0 2px 4px rgba(0,0,0,0.02); margin-bottom: 24px; }
        .card-header { border-bottom: 1px solid #e2e8f0; background: white; }
        .btn-brand { background-color: var(--brand-color); color: white; border: none; border-radius: var(--border-radius); }
        .btn-brand:hover { background-color: var(--brand-hover); color: white; }
        .text-brand { color: var(--brand-color) !important; }
        .form-control, .form-select { border-radius: var(--border-radius); border: 1px solid #cbd5e1; }
        .form-control:focus, .form-select:focus { border-color: var(--brand-color); box-shadow: 0 0 0 2px rgba(26, 58, 110, 0.15); }
        .form-label { font-size: 0.85rem; color: #64748b; margin-bottom: 0.3rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .wizard-step { animation: fadeIn 0.3s; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <div class="col-md-10 content-area">
            <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
                <h3 class="fw-bold mb-0 text-dark">Mở Hồ sơ Xét tuyển Mới</h3>
            </div>

            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header py-3">
                            <h6 class="mb-0 fw-bold text-brand">Hệ thống Đăng ký Hồ sơ Toàn trình<?php echo $form_title_suffix; ?> (Tiêu chuẩn 4 bước)</h6>
                        </div>
                        <div class="card-body p-4">
                            <?php if($message): ?>
                                <div class="alert alert-success text-center py-4">
                                    <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                                    <h5 class="mt-3 fw-bold">HOÀN TẤT THÀNH CÔNG</h5>
                                    <p><?php echo $message; ?></p>
                                    <a href="/tsdhhl26/candidate/index.php" class="btn btn-success mt-2 px-4 shadow-sm">Về Bảng điều khiển</a>
                                </div>
                            <?php elseif(empty($activePeriods) || empty($majors)): ?>
                                <div class="alert alert-warning text-center">
                                    Hiện tại nhà trường chưa mở Đợt tuyển sinh nào hoặc danh mục Ngành học trống. Vui lòng quay lại sau!
                                </div>
                            <?php else: ?>
                                <?php if($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

                                <form method="POST" action="">
                                    <!-- Bước 1 -->
                                    <div class="wizard-step" id="step-1">
                                        <h6 class="text-brand fw-bold mb-3 border-bottom pb-2">BƯỚC 1: CHỌN ĐỢT TUYỂN SINH</h6>
                                        <?php if (!empty($step1_info)): ?>
                                        <div class="mb-3 p-3 rounded border-start border-4 border-primary bg-light">
                                            <?php echo $step1_info; ?>
                                        </div>
                                        <?php endif; ?>
                                        <div class="mb-3" <?php echo $selected_level_id ? 'style="display:none;"' : ''; ?>>
                                            <label class="form-label text-muted fw-bold">Hệ Đào tạo <span class="text-danger">*</span></label>
                                            <select class="form-select" id="levelSelect" <?php echo $selected_level_id ? '' : 'required'; ?> onchange="filterPeriods()">
                                                <option value="">-- Chọn Hệ đào tạo --</option>
                                                <?php foreach ($levels as $l): ?>
                                                    <option value="<?php echo $l['id']; ?>" <?php echo ($selected_level_id == $l['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($l['name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="mb-4">
                                            <label class="form-label text-muted fw-bold">Đợt Tuyển sinh <span class="text-danger">*</span></label>
                                            <select class="form-select" name="admission_period_id" id="periodSelect" required onchange="filterMajors()">
                                                <option value="">-- Vui lòng chọn Hệ Đào tạo trước --</option>
                                            </select>
                                        </div>
                                        <div class="text-end">
                                            <button type="button" class="btn btn-brand px-4 py-2 fw-semibold" onclick="goToStep(2)">Tiếp tục &raquo;</button>
                                        </div>
                                    </div>

                                    <!-- Bước 2 -->
                                    <div class="wizard-step d-none" id="step-2">
                                        <h6 class="text-brand fw-bold mb-3 border-bottom pb-2">BƯỚC 2: RÀ SOÁT THÔNG TIN CÁ NHÂN</h6>
                                        <div class="row mb-3 bg-light p-3 rounded mx-0 border">
                                            <?php foreach ($step2_fields as $f): ?>
                                            <div class="col-md-6 mb-3">
                                                <span class="text-muted small d-block mb-1"><?php echo htmlspecialchars($f['label']); ?></span>
                                                <strong class="text-dark fs-6"><?php echo htmlspecialchars($profileData[$f['key']] ?? 'Chưa cập nhật'); ?></strong>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="alert alert-warning small py-2 mb-4">
                                            <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i> 
                                            Nếu thông tin chưa chính xác, hệ thống có thể từ chối hồ sơ. Vui lòng <a href="/tsdhhl26/candidate/profile.php" class="fw-bold text-decoration-none" target="_blank">cập nhật tại đây</a> trước khi nộp.
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <button type="button" class="btn btn-secondary px-4 py-2 text-white fw-semibold" onclick="goToStep(1)">&laquo; Xác chọn lại Đợt</button>
                                            <button type="button" class="btn btn-brand px-4 py-2 fw-semibold" onclick="goToStep(3)">Chính xác, Tiếp tục &raquo;</button>
                                        </div>
                                    </div>

                                    <!-- Bước 3 -->
                                    <div class="wizard-step d-none" id="step-3">
                                        <h6 class="text-brand fw-bold mb-3 border-bottom pb-2">BƯỚC 3: LỰA CHỌN NGUYỆN VỌNG</h6>
                                        <div class="mb-3">
                                            <label class="form-label text-muted fw-bold">Ngành học đăng ký xét tuyển <span class="text-danger">*</span></label>
                                            <select class="form-select" name="major_id" required id="majorSelect" onchange="updateFeePreview(); filterMethods();">
                                                <option value="" data-fee="0">-- Vui lòng chọn Đợt Tuyển Sinh trước --</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label text-muted fw-bold">Phương thức xét tuyển <span class="text-danger">*</span></label>
                                            <select class="form-select" name="admission_method_id" required id="methodSelect">
                                                <option value="">-- Vui lòng chọn Ngành học trước --</option>
                                            </select>
                                        </div>
                                        <div class="mb-4">
                                            <label class="form-label text-muted fw-bold">Thứ tự Nguyện vọng <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" name="priority" id="priorityInput" min="1" max="10" value="1" required>
                                            <div class="form-text text-muted">Nhập số thứ tự ưu tiên (Nguyện vọng 1 là ưu tiên cao nhất). Có thể chỉnh sửa lại sau trong Bảng điều khiển.</div>
                                        </div>
                                        <div class="p-3 mb-4 rounded" style="background-color: rgba(26,58,110,0.05); border-left: 4px solid var(--brand-color);">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-dark fw-semibold">Lệ phí xét tuyển tương ứng:</span>
                                                <span class="fs-4 fw-bold text-danger" id="feePreview">0 đ</span>
                                            </div>
                                        </div>
                                        <div id="dupCheckMsg" class="d-none alert alert-danger py-2 mb-3"></div>
                                        <div class="d-flex justify-content-between">
                                            <button type="button" class="btn btn-secondary px-4 py-2 text-white fw-semibold" onclick="goToStep(2)">&laquo; Rà soát lại Profile</button>
                                            <button type="button" class="btn btn-brand px-4 py-2 fw-semibold" id="goToPaymentBtn" onclick="checkDupThenPay()">Thanh toán bằng VietQR &raquo;</button>
                                        </div>
                                    </div>

                                    <!-- Bước 4 -->
                                    <div class="wizard-step d-none" id="step-4">
                                        <h6 class="text-brand fw-bold mb-3 border-bottom pb-2">BƯỚC 4: THANH TOÁN VÀ TẢI LÊN MINH CHỨNG</h6>
                                        <div class="text-center mb-4">
                                            <div class="d-inline-block bg-white p-3 rounded shadow-sm border mb-3">
                                                <p class="mb-2 fw-bold text-dark"><i class="bi bi-qr-code-scan me-2"></i>Sử dụng App Ngân hàng để quét mã VietQR</p>
                                                <img id="vietqrImage" src="" alt="VietQR" style="width: 200px; height: 200px; object-fit: contain;">
                                                <h5 class="text-danger fw-bold mt-2 mb-0">Tổng tiền: <span id="paymentAmountText"></span></h5>
                                            </div>
                                            <div>
                                                <p class="small text-dark mb-0 bg-warning bg-opacity-10 d-inline-block px-3 py-2 rounded border border-warning">
                                                    Nội dung chuyển khoản (Hệ thống tự điền): <br><strong class="fs-6" id="paymentContentText"></strong>
                                                </p>
                                            </div>
                                            <p class="small text-muted mt-2 mb-0">Vui lòng kiểm tra kỹ nội dung và tên người hưởng thụ "TRUONG DAI HOC HA LONG" hoặc đại diện hợp pháp.</p>
                                        </div>

                                        <div class="mb-4 p-3 border rounded shadow-sm">
                                            <label class="form-label fw-bold text-dark">Tải lên ảnh chụp màn hình Biên lai chuyển khoản <span class="text-danger">*</span></label>
                                            <input type="file" id="receiptFileInput" class="form-control" accept=".jpg,.jpeg,.png">
                                            <input type="hidden" name="receipt_url" id="receiptUrlInput" required>
                                            
                                            <div id="uploadReceiptProgress" class="progress mt-3 d-none" style="height: 6px;">
                                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" style="width: 100%"></div>
                                            </div>
                                            <div id="uploadReceiptStatus" class="small mt-2 text-center text-muted">Vui lòng tải ảnh hóa đơn giao dịch hiển thị rõ số tiền và nội dung.</div>
                                            
                                            <button type="button" id="uploadReceiptBtn" class="btn btn-outline-primary w-100 fw-bold mt-3 border-2"><i class="bi bi-cloud-arrow-up-fill me-1"></i> BẮT ĐẦU TẢI ẢNH BIÊN LAI LÊN HỆ THỐNG</button>
                                        </div>

                                        <div class="form-check mb-4 bg-light p-3 rounded border">
                                            <input class="form-check-input ms-1" type="checkbox" id="flexCheckDefault" required>
                                            <label class="form-check-label text-dark small fw-semibold ms-2" for="flexCheckDefault">
                                                Tôi cam đoan thông tin hồ sơ và ảnh biên lai chuyển tiền là trung thực và hợp pháp.
                                            </label>
                                        </div>

                                        <div class="d-flex justify-content-between">
                                            <button type="button" class="btn btn-secondary px-4 py-2 text-white fw-semibold" onclick="goToStep(3)">&laquo; Sửa Nguyện vọng</button>
                                            <button type="submit" class="btn btn-success fw-bold px-4 py-2 shadow" id="submitAppBtn" disabled>XÁC NHẬN CHÍNH THỨC NỘP HỒ SƠ</button>
                                        </div>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const GAS_URL = '<?php echo GAS_WEBAPP_URL; ?>';
    const USER_ID = '<?php echo $user_id; ?>';

    window.addEventListener('DOMContentLoaded', () => {
        if(document.getElementById('levelSelect')) {
            if(document.getElementById('levelSelect').value !== '') {
                filterPeriods();
            }
        }
    });

    const allPeriods = <?php echo json_encode($activePeriods); ?>;
    const allMethods = <?php echo json_encode($methods); ?>;
    const allPeriodMajorMethods = <?php echo json_encode($periodMajorMethods); ?>;

    const allMajors = <?php 
        $mList = [];
        foreach($majors as $m) {
            $pIds = [];
            foreach($periodMajors as $pm) { if($pm['major_id'] == $m['id']) $pIds[] = strval($pm['period_id']); }
            $mList[] = [
                'id' => $m['id'],
                'name' => '['.($m['education_levels']['name'] ?? 'N/A').'] - '.$m['major_name'].' (Mã: '.($m['major_code'] ?? 'N/A').')',
                'fee' => $m['application_fee'] ?? 0,
                'periods' => $pIds
            ];
        }
        echo json_encode($mList);
    ?>;

    function filterPeriods() {
        const levelId = document.getElementById('levelSelect').value;
        const periodSelect = document.getElementById('periodSelect');
        const majorSelect = document.getElementById('majorSelect');
        const methodSelect = document.getElementById('methodSelect');
        
        const selectedVal = periodSelect.value;
        periodSelect.innerHTML = '<option value="">-- Chọn Đợt Tuyển Sinh --</option>';
        majorSelect.innerHTML = '<option value="" data-fee="0">-- Vui lòng chọn Đợt Tuyển Sinh trước --</option>';
        methodSelect.innerHTML = '<option value="">-- Vui lòng chọn Ngành học trước --</option>';
        
        if(levelId) {
            let hasPeriods = false;
            allPeriods.forEach(p => {
                if(p.education_level_id == levelId) {
                    let opt = document.createElement('option');
                    opt.value = p.id;
                    opt.text = p.name;
                    if(selectedVal && p.id == selectedVal) opt.selected = true;
                    periodSelect.appendChild(opt);
                    hasPeriods = true;
                }
            });
            if(!hasPeriods) {
                periodSelect.innerHTML = '<option value="">-- Hệ này chưa mở đợt tuyển sinh nào --</option>';
            }
        } else {
            periodSelect.innerHTML = '<option value="">-- Vui lòng chọn Hệ Đào tạo trước --</option>';
        }
        updateFeePreview();
        filterMethods();
    }

    function filterMajors() {
        const periodId = document.querySelector('select[name="admission_period_id"]').value;
        const majorSelect = document.getElementById('majorSelect');
        
        const selectedVal = majorSelect.value;
        majorSelect.innerHTML = '<option value="" data-fee="0">-- Chọn Ngành đăng ký --</option>';
        
        if(periodId) {
            let hasMajors = false;
            allMajors.forEach(m => {
                if(m.periods.includes(periodId)) {
                    let opt = document.createElement('option');
                    opt.value = m.id;
                    opt.text = m.name;
                    opt.setAttribute('data-fee', m.fee);
                    if(selectedVal && m.id == selectedVal) opt.selected = true;
                    majorSelect.appendChild(opt);
                    hasMajors = true;
                }
            });
            if(!hasMajors) {
                majorSelect.innerHTML = '<option value="" data-fee="0">-- Đợt tuyển sinh này chưa mở ngành nào --</option>';
            }
        } else {
            majorSelect.innerHTML = '<option value="" data-fee="0">-- Vui lòng chọn Đợt Tuyển Sinh trước --</option>';
        }
        updateFeePreview();
        filterMethods();
    }

    function filterMethods() {
        const periodId = document.querySelector('select[name="admission_period_id"]').value;
        const majorId = document.getElementById('majorSelect').value;
        const methodSelect = document.getElementById('methodSelect');
        
        const selectedVal = methodSelect.value;
        methodSelect.innerHTML = '<option value="">-- Chọn phương thức xét tuyển --</option>';
        
        if(periodId && majorId) {
            let hasMethods = false;
            const validMethodIds = allPeriodMajorMethods
                .filter(m => m.period_id == periodId && m.major_id == majorId)
                .map(m => m.method_id);
                
            allMethods.forEach(method => {
                if(validMethodIds.includes(method.id)) {
                    let opt = document.createElement('option');
                    opt.value = method.id;
                    opt.text = method.method_name;
                    if(selectedVal && method.id == selectedVal) opt.selected = true;
                    methodSelect.appendChild(opt);
                    hasMethods = true;
                }
            });
            if(!hasMethods) {
                methodSelect.innerHTML = '<option value="">-- Ngành này chưa thiết lập phương thức --</option>';
            }
        } else {
            methodSelect.innerHTML = '<option value="">-- Vui lòng chọn Ngành học trước --</option>';
        }
    }

    function updateFeePreview() {
        const select = document.getElementById('majorSelect');
        const selectedOption = select.options[select.selectedIndex];
        const fee = selectedOption ? selectedOption.getAttribute('data-fee') : 0;
        
        const formatter = new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' });
        document.getElementById('feePreview').innerText = formatter.format(fee);
    }

    async function checkDupThenPay() {
        const periodId = document.getElementById('periodSelect').value;
        const majorId  = document.getElementById('majorSelect').value;
        const methodId = document.getElementById('methodSelect').value;
        const priority = document.getElementById('priorityInput').value;
        const dupMsg   = document.getElementById('dupCheckMsg');
        const btn      = document.getElementById('goToPaymentBtn');

        if (!majorId || !methodId) {
            alert('Vui lòng chọn Ngành học và Phương thức xét tuyển trước!');
            return;
        }
        if (!priority || parseInt(priority) < 1) {
            alert('Vui lòng nhập Thứ tự Nguyện vọng hợp lệ (tối thiểu là 1)!');
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

        } catch(e) {
            alert('Lỗi kiểm tra: ' + e.message);
        } finally {
            btn.disabled = false;
            btn.innerText = 'Thanh toán bằng VietQR »';
        }
    }

    function goToStep(step) {
        // Validation trước khi sang step tiếp
        if (step === 2) {
            if (!document.getElementById('periodSelect').value) {
                alert('Vui lòng chọn Hệ đào tạo và Đợt tuyển sinh trước!');
                return;
            }
        }
        if (step === 4) {
            if (!document.getElementById('majorSelect').value || !document.getElementById('methodSelect').value) {
                alert('Vui lòng chọn Ngành học và Phương thức xét tuyển trước!');
                return;
            }

            // Tạo VietQR Data khi sang bước thanh toán
            const fullNameRaw = "<?php echo addslashes($profileData['full_name'] ?? ''); ?>";
            // Lược bỏ dấu tiếng việt + dấu cách cho phù hợp chuẩn ngân hàng
            const normalizeStr = str => str.normalize("NFD").replace(/[\u0300-\u036f]/g, "").replace(/đ/g, "d").replace(/Đ/g, "D").toUpperCase().replace(/[^A-Z0-9]/g, '');
            const fullNameClean = normalizeStr(fullNameRaw);
            const cccd = "<?php echo addslashes($profileData['identity_card'] ?? '000000000000'); ?>";
            
            // Giới hạn max 50 kí tự do một số NH giới hạn nội dung chuyển tiền
            const content = `HD ${fullNameClean} ${cccd}`.substring(0, 50); 
            
            const select = document.getElementById('majorSelect');
            const fee = select.options[select.selectedIndex].getAttribute('data-fee');
            
            const formatter = new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' });
            document.getElementById('paymentAmountText').innerText = formatter.format(fee);
            document.getElementById('paymentContentText').innerText = content;
            
            // Sử dụng Fake Account (Vidu: VietinBank 970415, 113366668888)
            const qrUrl = `https://img.vietqr.io/image/970415-113366668888-compact2.png?amount=${fee}&addInfo=${encodeURIComponent(content)}&accountName=TRUONG%20DAI%20HOC%20HA%20LONG`;
            document.getElementById('vietqrImage').src = qrUrl;
        }

        // Hide all
        for(let i=1; i<=4; i++) {
            let el = document.getElementById('step-'+i);
            if(el) el.classList.add('d-none');
        }
        // Show target
        const target = document.getElementById('step-'+step);
        if(target) target.classList.remove('d-none');
    }

    // Google Apps Script File Upload
    document.getElementById('uploadReceiptBtn').addEventListener('click', async function() {
        const fileInput = document.getElementById('receiptFileInput');
        const statusDiv = document.getElementById('uploadReceiptStatus');
        const progress = document.getElementById('uploadReceiptProgress');
        const urlInput = document.getElementById('receiptUrlInput');
        const submitBtn = document.getElementById('submitAppBtn');
        
        if (fileInput.files.length === 0) { alert('Vui lòng chọn file ảnh biên lai!'); return; }
        if (!GAS_URL) { alert('Hệ thống chưa cấu hình Link Upload Google Drive. Vui lòng báo quản trị.'); return; }

        const file = fileInput.files[0];
        const reader = new FileReader();
        
        statusDiv.className = 'small mt-2 text-center text-primary fw-bold';
        statusDiv.innerText = 'Đang đọc thẻ và tải ảnh lên hệ thống...';
        progress.classList.remove('d-none');
        this.disabled = true;

        reader.onload = async function() {
            const base64 = reader.result.split(',')[1];
            
            try {
                const response = await fetch(GAS_URL, {
                    method: 'POST',
                    body: JSON.stringify({
                        base64: base64,
                        fileName: `RECEIPT_${USER_ID}_${Date.now()}_${file.name}`,
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
</body>
</html>
