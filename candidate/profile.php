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
    $full_name = trim($_POST['full_name'] ?? '');
    $identity_card = trim($_POST['identity_card'] ?? '');
    $contact_email = trim($_POST['contact_email'] ?? '');
    $date_of_birth = trim($_POST['date_of_birth'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (empty($full_name)) {
        $_SESSION['profile_error'] = "Họ tên không được để trống.";
    } else {
        $updateData = [
            'full_name' => $full_name,
            'identity_card' => $identity_card,
            'contact_email' => $contact_email,
            'date_of_birth' => $date_of_birth ? $date_of_birth : null,
            'phone_number' => $phone_number,
            'address' => $address,
            'updated_at' => date('Y-m-d H:i:sP')
        ];
        
        // Gọi Service Role tạm để Update (Bypass RLS safety gap)
        $updateRes = $supabaseAdmin->update('user_profiles', 'id', $user_id, $updateData);
        
        if (in_array($updateRes['code'], [200, 204])) {
            $_SESSION['profile_success'] = "Cập nhật hồ sơ và Email liên hệ thành công!";
        } else {
            $_SESSION['profile_error'] = "Lỗi cập nhật: " . json_encode($updateRes['data']);
        }
    }
    
    // Áp dụng mô hình chuẩn Post-Redirect-Get để tránh lỗi Resubmit form khi F5
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
        .form-control { border-radius: var(--border-radius); border: 1px solid #cbd5e1; }
        .form-control:focus { border-color: var(--brand-color); box-shadow: 0 0 0 2px rgba(26, 58, 110, 0.15); }
        .form-label { font-size: 0.85rem; color: #64748b; margin-bottom: 0.3rem; }
        .bg-brand { background-color: var(--brand-color) !important; }
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
            <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
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
                                <div class="mb-4">
                                    <label class="form-label text-muted fw-bold">Địa chỉ lưu trú</label>
                                    <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($profile['address'] ?? ''); ?></textarea>
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
                                            <a href='{$doc['drive_file_url']}' target='_blank' class='text-primary small text-decoration-none'>Xem file</a>
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
    
    statusDiv.className = 'small mt-2 text-center text-primary';
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
