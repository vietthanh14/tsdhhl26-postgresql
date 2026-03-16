<?php
// admin/applications.php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: /tsdhhl26/admin/login.php');
    exit;
}
require_once __DIR__ . '/../lib/SupabaseClient.php';
$supabaseAdmin = new SupabaseClient('service');

$message = $_SESSION['msg'] ?? '';
$error = $_SESSION['err'] ?? '';
unset($_SESSION['msg'], $_SESSION['err']);

// Xu ly POST (Cap nhat trang thai)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Cập nhật 1 hồ sơ
    if ($action === 'update_status') {
        $app_id = $_POST['app_id'];
        $data = [
            'status' => $_POST['status'],
            'payment_status' => $_POST['payment_status'],
            'updated_at' => date('Y-m-d H:i:sP')
        ];
        $res = $supabaseAdmin->update('applications', 'id', $app_id, $data);
        if (in_array($res['code'], [200, 204])) {
            $_SESSION['msg'] = "Cập nhật trạng thái hồ sơ thành công!";
        } else {
            $_SESSION['err'] = "Lỗi cập nhật: " . json_encode($res['data']);
        }
        header("Location: applications.php"); exit;
    }
    
    // Cập nhật hàng loạt (Bulk Update)
    if ($action === 'bulk_update') {
        $app_ids = $_POST['app_ids'] ?? [];
        $bulk_type = $_POST['bulk_type'] ?? '';
        
        if (empty($app_ids)) {
            $_SESSION['err'] = "Bạn chưa chọn hồ sơ nào.";
        } else {
            $successCount = 0;
            $data = ['updated_at' => date('Y-m-d H:i:sP')];
            
            if ($bulk_type === 'mark_paid') {
                $data['payment_status'] = 'PAID';
            } elseif ($bulk_type === 'mark_approved') {
                $data['status'] = 'APPROVED';
            } elseif ($bulk_type === 'mark_rejected') {
                $data['status'] = 'REJECTED';
            }
            
            foreach ($app_ids as $id) {
                $res = $supabaseAdmin->update('applications', 'id', $id, $data);
                if (in_array($res['code'], [200, 204])) $successCount++;
            }
            $_SESSION['msg'] = "Đã cập nhật hàng loạt thành công $successCount hồ sơ!";
        }
        header("Location: applications.php"); exit;
    }
}

// Lay danh sach dot tuyen sinh
$periodsRes = $supabaseAdmin->select('admission_periods', 'order=id.desc');
$periods = ($periodsRes['code'] == 200) ? $periodsRes['data'] : [];

// Lay danh sach ho so
$query = 'select=*,admission_periods(name),majors(major_name),admission_methods(method_name)&order=submitted_at.desc';
$appsRes = $supabaseAdmin->select('applications', $query);
$applications = ($appsRes['code'] == 200) ? $appsRes['data'] : [];

// Lay danh sach user profiles de map thu cong 
$usersRes = $supabaseAdmin->select('user_profiles', 'select=id,full_name,identity_card,phone_number');
$userProfilesMap = [];
if ($usersRes['code'] == 200 && is_array($usersRes['data'])) {
    foreach ($usersRes['data'] as $u) {
        $userProfilesMap[$u['id']] = $u;
    }
}

// Map user profiles vao tung application
foreach ($applications as &$app) {
    if (isset($app['user_id']) && isset($userProfilesMap[$app['user_id']])) {
        $app['user_profiles'] = $userProfilesMap[$app['user_id']];
    } else {
        $app['user_profiles'] = [];
    }
}
unset($app);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Hồ sơ Ứng viên - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="/tsdhhl26/assets/css/public.css">
    
    <style>
        :root { --brand-color: #1A3A6E; --sidebar-bg: #0f2444; }
        body { background-color: #f7f9fc; font-family: 'Inter', sans-serif; font-size: 0.9rem;}
        .sidebar { background-color: var(--sidebar-bg); min-height: 100vh; padding-top: 25px; position: fixed; height: 100%; z-index: 1000;}
        .sidebar a { color: #cbd5e1; text-decoration: none; padding: 12px 24px; display: block; border-left: 3px solid transparent; font-weight: 500; }
        .sidebar a:hover, .sidebar a.active { background-color: rgba(255,255,255,0.05); color: #fff; border-left-color: #3b82f6; }
        .main-content { margin-left: 16.666667%; padding: 30px; }
        .badge-status { font-size: 0.8rem; padding: 0.4em 0.6em; }
        table.dataTable td { vertical-align: middle; }
        .bulk-actions { background: rgba(26, 58, 110, 0.05); padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px dashed #1A3A6E; display: none; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="row m-0 w-100 p-0 text-start" style="padding: 0; min-height: 80vh;">
    <!-- Sidebar -->
    <div class="col-md-2 sidebar d-none d-md-block px-0">
        <h5 class="text-white text-center mb-4">ADMIN PORTAL</h5>
        <a href="/tsdhhl26/admin/index.php">Bảng điều khiển</a>
        <a href="/tsdhhl26/admin/admission_settings.php">Cấu hình Đợt/Ngành</a>
        <a href="/tsdhhl26/admin/applications.php" class="active">Quản lý Hồ sơ</a>
        <a href="/tsdhhl26/admin/documents.php">Tài liệu tải lên</a>
        <a href="/tsdhhl26/admin/users.php">Quản lý Thí sinh</a>
        <hr class="text-secondary mx-3">
        <a href="/tsdhhl26/admin/logout.php" class="text-danger">Đăng xuất</a>
    </div>

    <!-- Main Content -->
    <div class="col-md-10 main-content">
        <h3 class="fw-bold mb-4">Quản lý Hồ sơ Đăng ký Xét tuyển</h3>

        <?php if($message): ?><div class="alert alert-success alert-dismissible fade show"><?php echo $message; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
        <?php if($error): ?><div class="alert alert-danger alert-dismissible fade show"><?php echo $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

        <form method="POST" action="" id="bulkForm">
            <input type="hidden" name="action" value="bulk_update">
            <input type="hidden" name="bulk_type" id="bulkTypeInput" value="">

            <div class="bulk-actions" id="bulkActionsPanel">
                <span class="fw-bold text-brand me-3">Đã chọn <span id="selectedCount">0</span> hồ sơ:</span>
                <button type="button" class="btn btn-success btn-sm me-2" onclick="submitBulk('mark_paid')"><i class="bi bi-cash-coin"></i> Đánh dấu Đã Nộp Lệ Phí (PAID)</button>
                <button type="button" class="btn btn-primary btn-sm me-2" onclick="submitBulk('mark_approved')"><i class="bi bi-check-circle"></i> Phê duyệt Hợp lệ (APPROVED)</button>
                <button type="button" class="btn btn-danger btn-sm" onclick="submitBulk('mark_rejected')"><i class="bi bi-x-circle"></i> Từ chối (REJECTED)</button>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered" id="appsTable" width="100%">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40px" class="text-center"><input type="checkbox" class="form-check-input" id="checkAll"></th>
                                    <th>Thí sinh</th>
                                    <th>Thông tin liên hệ</th>
                                    <th>Ngành đăng ký</th>
                                    <th>Biên lai</th>
                                    <th>TT Thanh toán</th>
                                    <th>TT Hồ sơ</th>
                                    <th>Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($applications as $app): 
                                    $user = $app['user_profiles'] ?? [];
                                    $period = $app['admission_periods'] ?? [];
                                    $major = $app['majors'] ?? [];
                                    $method = $app['admission_methods'] ?? [];
                                    
                                    $statusClass = $app['status'] == 'APPROVED' ? 'bg-success' : ($app['status'] == 'REJECTED' ? 'bg-danger' : 'bg-warning text-dark');
                                    $statusText = $app['status'] == 'APPROVED' ? 'Hợp lệ' : ($app['status'] == 'REJECTED' ? 'Từ chối' : 'Chờ duyệt');

                                    $payClass = $app['payment_status'] == 'PAID' ? 'bg-success' : ($app['payment_status'] == 'REFUNDED' ? 'bg-secondary' : 'bg-danger');
                                    $payText = $app['payment_status'] == 'PAID' ? 'Đã nộp' : ($app['payment_status'] == 'REFUNDED' ? 'Hoàn tiền' : 'Chưa nộp');
                                ?>
                                <tr>
                                    <td class="text-center">
                                        <input type="checkbox" class="form-check-input row-checkbox" name="app_ids[]" value="<?php echo htmlspecialchars($app['id']); ?>">
                                    </td>
                                    <td>
                                        <strong class="d-block text-brand"><?php echo htmlspecialchars($user['full_name'] ?? 'Không rõ'); ?></strong>
                                        <span class="text-muted small">CMND: <?php echo htmlspecialchars($user['identity_card'] ?? 'N/A'); ?></span>
                                    </td>
                                    <td>
                                        <span class="d-block small"><i class="bi bi-telephone text-muted"></i> <?php echo htmlspecialchars($user['phone_number'] ?? 'N/A'); ?></span>
                                        <span class="d-block small mt-1 text-muted">Nộp: <?php echo date('d/m/Y H:i', strtotime($app['submitted_at'])); ?></span>
                                    </td>
                                    <td>
                                        <strong class="d-block text-dark"><?php echo htmlspecialchars($major['major_name'] ?? 'Không rõ'); ?></strong>
                                        <span class="badge bg-light text-dark border mt-1" title="Phương thức">
                                            <?php echo htmlspecialchars($method['method_name'] ?? 'Không rõ'); ?>
                                        </span>
                                        <div class="small text-muted mt-1">Đợt: <?php echo htmlspecialchars($period['name'] ?? 'Không rõ'); ?></div>
                                        <div class="small text-info mt-1 fw-bold">NV: <?php echo htmlspecialchars($app['priority'] ?? '1'); ?></div>
                                    </td>
                                    <td>
                                        <?php if (!empty($app['receipt_url'])): ?>
                                            <a href="<?php echo htmlspecialchars($app['receipt_url']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-image"></i> Xem BL
                                            </a>
                                            <div class="small text-muted mt-1"><?php echo number_format($app['fee_amount'], 0, ',', '.'); ?> đ</div>
                                        <?php else: ?>
                                            <span class="text-muted small">Chưa nộp</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-status <?php echo $payClass; ?>"><?php echo $payText; ?></span>
                                    </td>
                                    <td>
                                        <span class="badge badge-status <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-brand" onclick="openUpdateModal('<?php echo $app['id']; ?>', '<?php echo $app['status']; ?>', '<?php echo $app['payment_status']; ?>')">
                                            <i class="bi bi-pencil-square"></i> Duyệt
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Update Status Single -->
<div class="modal fade" id="updateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Cập nhật 1 Hồ sơ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="app_id" id="modal_app_id">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Trạng thái Thanh toán</label>
                        <select class="form-select" name="payment_status" id="modal_payment_status">
                            <option value="UNPAID">Chưa nộp (Hoặc không hợp lệ)</option>
                            <option value="PAID">Đã nộp (Thanh toán thành công)</option>
                            <option value="REFUNDED">Đã hoàn tiền</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Trạng thái Hồ sơ</label>
                        <select class="form-select" name="status" id="modal_status">
                            <option value="PENDING">Chờ duyệt</option>
                            <option value="APPROVED">Hợp lệ (Trúng tuyển)</option>
                            <option value="REJECTED">Từ chối (Tài liệu không hợp lệ)</option>
                        </select>
                        <div class="form-text">Hồ sơ trúng tuyển phải có trạng thái thanh toán PAID.</div>
                    </div>
                </div>
                <div class="modal-footer text-center">
                    <button type="submit" class="btn btn-brand w-100">Cập nhật nhanh</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Confirm Bulk Action -->
<div class="modal fade" id="confirmBulkModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark border-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i> Xác nhận thao tác</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <i class="bi bi-info-circle text-brand" style="font-size: 3rem;"></i>
                <p class="mt-3 mb-0" style="font-size: 1.1rem;">Bạn có chắc chắn muốn áp dụng thao tác này cho <strong id="confirmCount" class="text-danger">0</strong> hồ sơ đã chọn không?</p>
                <p class="text-muted small mt-2 mb-0">Hành động này sẽ thay đổi dữ liệu trên hệ thống ngay lập tức.</p>
            </div>
            <div class="modal-footer border-0 justify-content-center pb-4">
                <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Hủy bỏ</button>
                <button type="button" class="btn btn-brand px-4" id="btnConfirmBulk">Đồng ý thực hiện</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function() {
        var table = $('#appsTable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json"
            },
            "order": [[3, "desc"]], // Sắp xếp theo ngày nộp
            "pageLength": 50,
            "columnDefs": [
                { "orderable": false, "targets": 0 } // Không sắp xếp cột Checkbox
            ]
        });

        // Xử lý Checkbox Chọn tất cả
        $('#checkAll').on('click', function(){
            var rows = table.rows({ 'search': 'applied' }).nodes();
            $('input[type="checkbox"]', rows).prop('checked', this.checked);
            updateBulkPanel();
        });

        $('#appsTable tbody').on('change', 'input[type="checkbox"]', function(){
            if(!this.checked){
                var el = $('#checkAll').get(0);
                if(el && el.checked && ('indeterminate' in el)){
                    el.indeterminate = true;
                }
            }
            updateBulkPanel();
        });

        function updateBulkPanel() {
            var selectedCount = $('.row-checkbox:checked').length;
            $('#selectedCount').text(selectedCount);
            if(selectedCount > 0) {
                $('#bulkActionsPanel').slideDown(200);
            } else {
                $('#bulkActionsPanel').slideUp(200);
                $('#checkAll').prop('checked', false);
            }
        }
    });

    let currentBulkType = '';
    const confirmBulkModal = new bootstrap.Modal(document.getElementById('confirmBulkModal'));

    function submitBulk(type) {
        currentBulkType = type;
        var count = $('.row-checkbox:checked').length;
        document.getElementById('confirmCount').innerText = count;
        confirmBulkModal.show();
    }

    document.getElementById('btnConfirmBulk').addEventListener('click', function() {
        document.getElementById('bulkTypeInput').value = currentBulkType;
        document.getElementById('bulkForm').submit();
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Đang xử lý...';
    });

    const updateModal = new bootstrap.Modal(document.getElementById('updateModal'));
    function openUpdateModal(appId, currentStatus, currentPaymentStatus) {
        document.getElementById('modal_app_id').value = appId;
        document.getElementById('modal_status').value = currentStatus;
        document.getElementById('modal_payment_status').value = currentPaymentStatus;
        updateModal.show();

    }
</script>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>

