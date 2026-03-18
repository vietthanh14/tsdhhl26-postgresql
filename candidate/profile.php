<?php
// candidate/profile.php
session_start();
require_once __DIR__ . '/../lib/SupabaseClient.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /tsdhhl26/auth/login.php');
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
    $full_name     = trim($_POST['full_name'] ?? '');
    $identity_card = trim($_POST['identity_card'] ?? '');
    $contact_email = trim($_POST['contact_email'] ?? '');
    $date_of_birth = trim($_POST['date_of_birth'] ?? '');
    $phone_number  = trim($_POST['phone_number'] ?? '');
    $province_code = trim($_POST['province_code'] ?? '');
    $province_name = trim($_POST['province_name'] ?? '');
    $ward_code     = trim($_POST['ward_code'] ?? '');
    $ward_name     = trim($_POST['ward_name'] ?? '');
    $address_detail= trim($_POST['address_detail'] ?? '');

    if (empty($full_name)) {
        $_SESSION['profile_error'] = "Họ tên không được để trống.";
    } else {
        $updateData = [
            'full_name'      => $full_name,
            'identity_card'  => $identity_card,
            'contact_email'  => $contact_email,
            'date_of_birth'  => $date_of_birth ?: null,
            'phone_number'   => $phone_number,
            'province'       => $province_name ?: null,
            'ward'           => $ward_name ?: null,
            'address_detail' => $address_detail ?: null,
            'updated_at'     => date('Y-m-d H:i:sP'),
        ];

        $updateRes = $supabaseAdmin->update('user_profiles', 'id', $user_id, $updateData);

        if (in_array($updateRes['code'], [200, 204])) {
            $_SESSION['profile_success'] = "Cập nhật hồ sơ thành công!";
        } else {
            $_SESSION['profile_error'] = "Lỗi cập nhật: " . json_encode($updateRes['data']);
        }
    }

    header('Location: /tsdhhl26/candidate/profile.php');
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
    header('Location: /tsdhhl26/auth/login.php');
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
    <link rel="stylesheet" href="/tsdhhl26/assets/css/public.css">
    <style>
        :root {
            --brand-color: #1A3A6E;
            --brand-hover: #12284c;
            --sidebar-bg: #1A3A6E; 
            --bg-color: #f7f9fc;
            --border-radius: 4px;
        }
        body { background-color: var(--bg-color); font-family: 'Inter', sans-serif; color: #333; }
        .sidebar { background-color: var(--sidebar-bg); min-height: 100vh; padding-top: 25px; box-shadow: 2px 0 10px rgba(0,0,0,0.05); }
        .sidebar a { color: #cbd5e1; text-decoration: none; padding: 12px 24px; display: block; border-left: 3px solid transparent; font-weight: 500; transition: all 0.2s; }
        .sidebar a:hover, .sidebar a.active { background-color: rgba(255,255,255,0.05); color: #fff; border-left-color: #3b82f6; }
        .content-area { padding: 40px; }
        .card { border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); margin-bottom: 24px; transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .card-header { border-bottom: 1px solid #e2e8f0; background: white; border-top-left-radius: 8px; border-top-right-radius: 8px;}
        .btn-brand { background-color: var(--brand-color); color: white; border: none; border-radius: 6px; min-height: 44px; display: inline-flex; align-items: center; justify-content: center; font-weight: 500;}
        .btn-brand:hover { background-color: var(--brand-hover); color: white; }
        .text-brand { color: var(--brand-color) !important; }
        .form-control, .form-select { border-radius: 6px; border: 1px solid #cbd5e1; min-height: 44px; }
        .form-control:focus, .form-select:focus { border-color: var(--brand-color); box-shadow: 0 0 0 2px rgba(26, 58, 110, 0.15); }
        .form-label { font-size: 0.85rem; color: #64748b; margin-bottom: 0.3rem; text-transform: uppercase; letter-spacing: 0.5px;}
        .bg-brand { background-color: var(--brand-color) !important; }
        .content-area { padding: 40px; transition: padding 0.3s; }
        @media (max-width: 767.98px) { .content-area { padding: 20px; } }

        /* Searchable Combobox */
        .combo-wrapper { position: relative; }
        .combo-wrapper .combo-input {
            border-radius: 6px; border: 1px solid #cbd5e1; min-height: 44px;
            width: 100%; padding: .375rem .75rem; font-size: 1rem;
            transition: border-color .15s ease, box-shadow .15s ease;
            background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 16 16'%3E%3Cpath fill='%2364748b' d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E") no-repeat right .75rem center;
            padding-right: 2rem; cursor: pointer;
        }
        .combo-wrapper .combo-input:focus {
            outline: none; border-color: var(--brand-color);
            box-shadow: 0 0 0 2px rgba(26,58,110,.15);
        }
        .combo-wrapper .combo-input:disabled {
            background-color: #f8fafc; cursor: not-allowed; color: #94a3b8;
        }
        .combo-dropdown {
            display: none; position: absolute; top: calc(100% + 4px); left: 0; right: 0;
            background: #fff; border: 1px solid #cbd5e1; border-radius: 8px;
            box-shadow: 0 8px 24px rgba(0,0,0,.1); max-height: 230px;
            overflow-y: auto; z-index: 1050;
        }
        .combo-dropdown.open { display: block; }
        .combo-dropdown .combo-search {
            position: sticky; top: 0; padding: 8px; background: #fff;
            border-bottom: 1px solid #e2e8f0;
        }
        .combo-dropdown .combo-search input {
            width: 100%; border: 1px solid #cbd5e1; border-radius: 6px;
            padding: 6px 10px; font-size: .875rem; outline: none;
        }
        .combo-dropdown .combo-search input:focus { border-color: var(--brand-color); }
        .combo-option {
            padding: 9px 14px; cursor: pointer; font-size: .9rem;
            transition: background .1s;
        }
        .combo-option:hover, .combo-option.active { background: #eff6ff; color: var(--brand-color); }
        .combo-option.no-result { color: #94a3b8; cursor: default; font-style: italic; }
        .combo-clear {
            position: absolute; right: 28px; top: 50%; transform: translateY(-50%);
            cursor: pointer; color: #94a3b8; font-size: .85rem; display: none;
            line-height: 1; z-index: 1;
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
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-3 border-bottom gap-2">
                <h3 class="fw-bold mb-0 text-dark">Thông tin cá nhân</h3>
            </div>

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header py-3">
                            <h6 class="mb-0 fw-bold">Hồ sơ định danh</h6>
                        </div>
                        <div class="card-body">
                            <?php if($message): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>
                            <?php if($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

                            <form method="POST" action="">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label text-muted fw-bold">Họ và Tên đầy đủ <span class="text-danger">*</span></label>
                                        <input type="text" name="full_name" class="form-control" required value="<?php echo htmlspecialchars($profile['full_name'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mt-3 mt-md-0">
                                        <label class="form-label text-muted fw-bold">Tên đăng nhập (Username)</label>
                                        <input type="text" class="form-control bg-light" disabled value="<?php echo htmlspecialchars($profile['username'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label text-muted fw-bold">Số CMND/CCCD <span class="text-danger">*</span></label>
                                        <input type="text" name="identity_card" class="form-control" required value="<?php echo htmlspecialchars($profile['identity_card'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mt-3 mt-md-0">
                                        <label class="form-label text-muted fw-bold">Email nhận thông báo <span class="badge bg-success ms-1">Có thể đổi</span></label>
                                        <input type="email" name="contact_email" class="form-control" placeholder="vidu@gmail.com" value="<?php echo htmlspecialchars($profile['contact_email'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label text-muted fw-bold">Số điện thoại liên hệ</label>
                                        <input type="tel" name="phone_number" class="form-control" value="<?php echo htmlspecialchars($profile['phone_number'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mt-3 mt-md-0">
                                        <label class="form-label text-muted fw-bold">Ngày sinh</label>
                                        <input type="date" name="date_of_birth" class="form-control" value="<?php echo htmlspecialchars($profile['date_of_birth'] ?? ''); ?>">
                                    </div>
                                </div>
                                <!-- Địa danh 2 cấp (Searchable Combobox) -->
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label text-muted fw-bold">Tỉnh / Thành phố</label>
                                        <div class="combo-wrapper" id="provinceWrapper">
                                            <span class="combo-clear" id="provinceClear" title="Xóa">&times;</span>
                                            <input type="text" class="combo-input" id="provinceInput"
                                                   placeholder="-- Chọn Tỉnh/Thành phố --" readonly>
                                            <div class="combo-dropdown" id="provinceDropdown">
                                                <div class="combo-search">
                                                    <input type="text" id="provinceSearch" placeholder="🔍 Tìm tỉnh/thành..." autocomplete="off">
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
                                                    <input type="text" id="wardSearch" placeholder="🔍 Tìm phường/xã..." autocomplete="off">
                                                </div>
                                                <div id="wardList"></div>
                                            </div>
                                        </div>
                                        <input type="hidden" name="ward_name" id="wardName">
                                        <input type="hidden" name="ward_code" id="wardCode">
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label text-muted fw-bold">Địa chỉ chi tiết (số nhà, thôn, đường...)</label>
                                    <input type="text" name="address_detail" id="addressDetail" class="form-control" placeholder="Ví dụ: Số 12, Đường Lê Lợi" value="<?php echo htmlspecialchars($profile['address_detail'] ?? ''); ?>">
                                </div>

                                <button type="submit" class="btn btn-brand px-4 py-2 fw-semibold">LƯU THAY ĐỔI</button>
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
                                    $docTypesRes = $supabaseAdmin->select('document_types', 'select=*');
                                    if ($docTypesRes['code'] == 200) {
                                        foreach ($docTypesRes['data'] as $type) {
                                            echo "<option value='{$type['id']}'>{$type['type_name']}</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">2. Chọn tập tin</label>
                                <input type="file" id="fileInput" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
                            </div>
                            <button id="uploadBtn" class="btn btn-brand btn-sm w-100 fw-bold py-2">BẮT ĐẦU TẢI LÊN</button>
                            
                            <!-- Progress Bar -->
                            <div id="uploadProgress" class="progress mt-3 d-none" style="height: 5px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" style="width: 100%"></div>
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
                                    foreach ($docTypesRes['data'] as $dt) {
                                        if ($dt['id'] == $doc['document_type_id']) { $typeName = $dt['type_name']; break; }
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ============================================================
// Địa danh 2 cấp: Tỉnh/TP → Phường/Xã
// ============================================================
(function () {
    const API_BASE = '/tsdhhl26/api/dia_danh.php';
    const savedProvince      = <?php echo json_encode($profile['province'] ?? ''); ?>;
    const savedWard          = <?php echo json_encode($profile['ward'] ?? ''); ?>;
    const savedAddressDetail = <?php echo json_encode($profile['address_detail'] ?? ''); ?>;

    let allProvinces = [];
    let allWards     = [];
    let selectedProvinceCode = '';

    // ---- Elements ----
    const provinceInput    = document.getElementById('provinceInput');
    const provinceDropdown = document.getElementById('provinceDropdown');
    const provinceSearch   = document.getElementById('provinceSearch');
    const provinceList     = document.getElementById('provinceList');
    const provinceName     = document.getElementById('provinceName');
    const provinceClear    = document.getElementById('provinceClear');

    const wardInput        = document.getElementById('wardInput');
    const wardDropdown     = document.getElementById('wardDropdown');
    const wardSearch       = document.getElementById('wardSearch');
    const wardList         = document.getElementById('wardList');
    const wardName         = document.getElementById('wardName');
    const wardClear        = document.getElementById('wardClear');

    const addressDetail    = document.getElementById('addressDetail');

    // ---- Combobox factory ----
    function makeCombo({ triggerEl, dropdown, searchEl, listEl, onSelect, onClear }) {
        // Mở dropdown khi click vào input
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

        // Lọc danh sách khi gõ
        searchEl.addEventListener('input', () => {
            const q = searchEl.value.toLowerCase().trim();
            listEl.querySelectorAll('.combo-option').forEach(opt => {
                const match = opt.textContent.toLowerCase().includes(q);
                opt.style.display = match ? '' : 'none';
            });
            const visible = [...listEl.querySelectorAll('.combo-option')].filter(o => o.style.display !== 'none');
            // Hàng không kết quả
            let noRes = listEl.querySelector('.no-result');
            if (!visible.length) {
                if (!noRes) { noRes = document.createElement('div'); noRes.className = 'combo-option no-result'; noRes.textContent = 'Không tìm thấy kết quả'; listEl.appendChild(noRes); }
            } else { if (noRes) noRes.remove(); }
        });

        // Nút xóa
        onClear && document.getElementById(triggerEl.id.replace('Input','Clear')).addEventListener('click', () => {
            onClear();
        });

        return { onSelect };
    }

    function closeAll() {
        document.querySelectorAll('.combo-dropdown.open').forEach(d => d.classList.remove('open'));
    }

    // Đóng khi click ra ngoài
    document.addEventListener('click', e => {
        if (!e.target.closest('.combo-wrapper')) closeAll();
    });

    // ---- Render options ----
    function renderProvinces(provinces) {
        provinceList.innerHTML = '';
        provinces.forEach(p => {
            const div = document.createElement('div');
            div.className = 'combo-option';
            div.textContent = p.name;
            div.dataset.code = p.code;
            div.addEventListener('click', () => {
                selectProvince(p);
                closeAll();
            });
            provinceList.appendChild(div);
        });
    }

    function renderWards(wards) {
        wardList.innerHTML = '';
        wards.forEach(w => {
            const div = document.createElement('div');
            div.className = 'combo-option';
            div.textContent = w.name;
            div.dataset.code = w.code;
            div.addEventListener('click', () => {
                selectWard(w);
                closeAll();
            });
            wardList.appendChild(div);
        });
    }

    // ---- Select actions ----
    function selectProvince(p, skipWardReset) {
        selectedProvinceCode = p.code;
        provinceInput.value  = p.name;
        provinceName.value   = p.name;
        provinceClear.style.display = 'block';

        if (!skipWardReset) {
            clearWard();
            wardInput.disabled = false;
        }

        fetch(`${API_BASE}?action=wards&province_code=${encodeURIComponent(p.code)}`)
            .then(r => r.json())
            .then(wards => {
                allWards = wards;
                renderWards(wards);
            });
    }

    function selectWard(w) {
        wardInput.value = w.name;
        wardName.value  = w.name;
        wardClear.style.display = 'block';
    }

    function clearProvince() {
        selectedProvinceCode = '';
        provinceInput.value  = '';
        provinceName.value   = '';
        provinceClear.style.display = 'none';
        clearWard();
        wardInput.disabled = true;
    }

    function clearWard() {
        wardInput.value = '';
        wardName.value  = '';
        wardClear.style.display = 'none';
    }

    // ---- Init ----
    makeCombo({ triggerEl: provinceInput, dropdown: provinceDropdown, searchEl: provinceSearch, listEl: provinceList, onClear: clearProvince });
    makeCombo({ triggerEl: wardInput,     dropdown: wardDropdown,     searchEl: wardSearch,     listEl: wardList,     onClear: clearWard });

    provinceClear.addEventListener('click', clearProvince);
    wardClear.addEventListener('click', clearWard);

    // Khởi động: tải tỉnh
    fetch(API_BASE + '?action=provinces')
        .then(r => r.json())
        .then(provinces => {
            allProvinces = provinces;
            renderProvinces(provinces);

            // Restore
            if (savedAddressDetail && addressDetail) addressDetail.value = savedAddressDetail;
            if (savedProvince) {
                const match = provinces.find(p => p.name === savedProvince);
                if (match) {
                    selectProvince(match, true); // load wards
                    // Sau khi load wards xong mới set ward
                    fetch(`${API_BASE}?action=wards&province_code=${encodeURIComponent(match.code)}`)
                        .then(r => r.json())
                        .then(wards => {
                            allWards = wards;
                            renderWards(wards);
                            wardInput.disabled = false;
                            if (savedWard) {
                                const mw = wards.find(w => w.name === savedWard);
                                if (mw) selectWard(mw);
                            }
                        });
                }
            }
        })
        .catch(() => console.warn('Không thể tải dữ liệu địa danh.'));
})();

// ============================================================
const GAS_URL = '<?php echo GAS_WEBAPP_URL; ?>';
const USER_ID = '<?php echo $user_id; ?>';

document.getElementById('uploadBtn').addEventListener('click', async function() {
    const fileInput = document.getElementById('fileInput');
    const docTypeId = document.getElementById('docTypeSelect').value;
    const statusDiv = document.getElementById('uploadStatus');
    const progress = document.getElementById('uploadProgress');
    
    if (!docTypeId) { alert('Vui lòng chọn loại tài liệu!'); return; }
    if (fileInput.files.length === 0) { alert('Vui lòng chọn file!'); return; }
    if (!GAS_URL) { alert('Chưa cấu hình Google Apps Script URL trong .env!'); return; }

    const file = fileInput.files[0];
    const reader = new FileReader();
    
    statusDiv.className = 'small mt-2 text-center text-brand';
    statusDiv.innerText = 'Đang mã hóa file...';
    progress.classList.remove('d-none');
    this.disabled = true;

    reader.onload = async function() {
        const base64 = reader.result.split(',')[1];
        statusDiv.innerText = 'Đang tải hồ sơ lên Google Drive...';
        
        try {
            // Tên file: DOC_D{docTypeId}_{cccdLast6}_{timestamp}.{ext}
            // Ví dụ: DOC_D3_789012_1710567890123.pdf
            const ext      = file.type === 'application/pdf' ? 'pdf' : (file.type === 'image/png' ? 'png' : 'jpg');
            const cccdRaw  = "<?php echo addslashes($profile['identity_card'] ?? '000000'); ?>";
            const cccd6    = cccdRaw.replace(/\D/g, '').slice(-6);
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

document.getElementById('btnConfirmDeleteDoc').addEventListener('click', async function() {
    if(!docIdToDelete) return;
    
    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Đang xóa...';
    
    try {
        const res = await fetch('delete_document.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id: docIdToDelete})
        });
        const data = await res.json();
        if(data.success) {
            location.reload();
        } else {
            alert('Lỗi: ' + data.message);
            confirmDeleteModal.hide();
        }
    } catch(err) {
        alert('Lỗi kết nối khi xóa tài liệu!');
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

<!-- Modal Confirm Delete (Global) -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-octagon me-2"></i> Xác nhận Xóa Tài Liệu</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
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
<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
