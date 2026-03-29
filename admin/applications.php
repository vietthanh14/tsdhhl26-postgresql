<?php
require_once __DIR__ . '/includes/admin_init.php';

// Xu ly POST (Cap nhat trang thai)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Cập nhật 1 hồ sơ
    if ($action === 'update_status') {
        $app_id = $_POST['app_id'];
        $data = [
            'status' => $_POST['status'],
            'admin_notes' => trim($_POST['admin_notes'] ?? ''),
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
    
    // Cập nhật hàng loạt (Bulk Update) — 1 API call duy nhất thay vì N calls
    if ($action === 'bulk_update') {
        $app_ids  = array_filter($_POST['app_ids'] ?? []);
        $bulk_type = $_POST['bulk_type'] ?? '';

        if (empty($app_ids)) {
            $_SESSION['err'] = "Bạn chưa chọn hồ sơ nào.";
        } else {
            $data = ['updated_at' => date('Y-m-d H:i:sP')];

            if ($bulk_type === 'mark_pending')    $data['status'] = 'PENDING';
            elseif ($bulk_type === 'mark_approved') $data['status'] = 'APPROVED';
            elseif ($bulk_type === 'mark_rejected') $data['status'] = 'REJECTED';

            if (isset($_POST['bulk_admin_notes']) && trim($_POST['bulk_admin_notes']) !== '') {
                $data['admin_notes'] = trim($_POST['bulk_admin_notes']);
            }

            // Gọi 1 lần duy nhất: PATCH /applications?id=in.(id1,id2,...)
            $res = $supabaseAdmin->updateBulk('applications', 'id', array_values($app_ids), $data);

            if (in_array($res['code'], [200, 204])) {
                $count = count($app_ids);
                $_SESSION['msg'] = "Đã cập nhật hàng loạt thành công {$count} hồ sơ!";
            } else {
                $_SESSION['err'] = "Lỗi cập nhật hàng loạt: " . json_encode($res['data'] ?? []);
            }
        }
        header("Location: applications.php"); exit;
    }
}

// Lay danh sach dot tuyen sinh
$periodsRes = $supabaseAdmin->select('admission_periods', 'order=id.desc');
$periods = ($periodsRes['code'] == 200) ? $periodsRes['data'] : [];

// Phân trang & Lọc
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 50;
$offset = ($page - 1) * $limit;

$statusFilter = $_GET['status'] ?? '';
$searchStr = trim($_GET['search'] ?? '');

$filterArray = [];
if ($statusFilter !== '') {
    $filterArray[] = "status=eq.{$statusFilter}";
}

if ($searchStr !== '') {
    $encodedSearch = urlencode('%' . $searchStr . '%');
    $userFilter = "or=(full_name.ilike.{$encodedSearch},identity_card.ilike.{$encodedSearch},phone_number.ilike.{$encodedSearch})&select=id";
    $searchUsersRes = $supabaseAdmin->select('user_profiles', $userFilter);
    $searchUserIds = ($searchUsersRes['code'] == 200 && is_array($searchUsersRes['data'])) ? array_column($searchUsersRes['data'], 'id') : [];
    
    if (empty($searchUserIds)) {
        // Tìm không ra user nào thì set điều kiện không khớp
        $filterArray[] = "user_id=eq.00000000-0000-0000-0000-000000000000";
    } else {
        $inList = SupabaseClient::buildInList($searchUserIds);
        $filterArray[] = "user_id=in.({$inList})";
    }
}

$filterQuery = !empty($filterArray) ? implode('&', $filterArray) . '&' : '';

$totalApps = $supabaseAdmin->count('applications', rtrim($filterQuery, '&'));
$totalPages = $totalApps > 0 ? ceil($totalApps / $limit) : 1;

// Lay danh sach ho so (Paginated)
$query = $filterQuery . "select=*,admission_periods(name),majors(major_name),admission_methods(method_name,application_fee)&order=submitted_at.desc&limit={$limit}&offset={$offset}";
$appsRes = $supabaseAdmin->select('applications', $query);
$applications = ($appsRes['code'] == 200) ? $appsRes['data'] : [];

// Lay danh sach user profiles de map thu cong (Chỉ mảng userID hiện tại)
$userProfilesMap = $supabaseAdmin->fetchUserProfilesMap(array_column($applications, 'user_id'));

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
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/logo.png">
    <title>Quản lý Hồ sơ Ứng viên - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/public.css">
    
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="container-fluid p-0">
    <div class="row m-0">
        <!-- Sidebar -->
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <h3 class="fw-bold m-0 text-brand">Quản lý Hồ sơ Đăng ký Xét tuyển</h3>
                <div class="d-flex gap-2 ms-auto align-items-center flex-wrap">
                    <form method="GET" class="d-flex m-0 gap-2">
                        <select name="status" class="form-select form-select-sm shadow-sm" style="min-width: 140px;" onchange="this.form.submit()">
                            <option value="">-- Tất cả trạng thái --</option>
                            <option value="PENDING" <?php echo ($statusFilter == 'PENDING') ? 'selected' : ''; ?>>Chờ duyệt</option>
                            <option value="APPROVED" <?php echo ($statusFilter == 'APPROVED') ? 'selected' : ''; ?>>Hợp lệ</option>
                            <option value="REJECTED" <?php echo ($statusFilter == 'REJECTED') ? 'selected' : ''; ?>>Từ chối</option>
                        </select>
                        <input type="text" name="search" class="form-control form-control-sm shadow-sm border-brand" placeholder="Tên, CMND, SĐT..." value="<?php echo htmlspecialchars($searchStr ?? ''); ?>" style="min-width: 180px;">
                        <button type="submit" class="btn btn-sm btn-brand shadow-sm"><i class="bi bi-search me-1"></i>Tìm</button>
                        <?php if(!empty($searchStr) || !empty($statusFilter)): ?>
                        <a href="applications.php" class="btn btn-sm btn-outline-secondary" title="Xóa tìm kiếm"><i class="bi bi-x-lg"></i></a>
                        <?php endif; ?>
                    </form>
                    <button type="button" class="btn btn-sm btn-outline-success shadow-sm" id="btnExportDocs">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Xuất Excel (CSV)
                    </button>
                </div>
            </div>

            <?php include __DIR__ . '/../includes/flash_messages.php'; ?>

            <form method="POST" action="" id="bulkForm">
                <input type="hidden" name="action" value="bulk_update">
                <input type="hidden" name="bulk_type" id="bulkTypeInput" value="">
                <input type="hidden" name="bulk_admin_notes" id="bulkAdminNotesInput" value="">

                <div class="bulk-actions mb-4 p-3 rounded-3 border-brand border-start border-5 shadow-sm bg-white d-none" id="bulkActionsPanel">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div>
                            <span class="fw-bold text-brand me-2"><i class="bi bi-check2-all"></i> Đã chọn <span id="selectedCount" class="badge bg-brand">0</span> hồ sơ:</span>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="submitBulk('mark_pending')"><i class="bi bi-hourglass-split"></i> Chờ duyệt</button>
                            <button type="button" class="btn btn-sm btn-outline-brand" onclick="submitBulk('mark_approved')"><i class="bi bi-check-circle"></i> Phê duyệt</button>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="submitBulk('mark_rejected')"><i class="bi bi-x-circle"></i> Từ chối</button>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle" id="appsTable" width="100%">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 40px" class="text-center"><input type="checkbox" class="form-check-input" id="checkAll"></th>
                                        <th>Thí sinh</th>
                                        <th>Liên hệ & Ngày nộp</th>
                                        <th>Ngành đăng ký</th>
                                        <th>Biên lai</th>
                                        <th>Hồ sơ</th>
                                        <th>Ghi chú</th>
                                        <th class="text-end">Thao tác</th>
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
                                    ?>
                                    <tr>
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input row-checkbox" name="app_ids[]" value="<?php echo htmlspecialchars($app['id']); ?>">
                                        </td>
                                        <td>
                                            <strong class="d-block text-brand"><?php echo htmlspecialchars($user['full_name'] ?? 'Không rõ'); ?></strong>
                                            <span class="text-muted small">CMND: <?php echo htmlspecialchars($user['identity_card'] ?? 'N/A'); ?></span>
                                        </td>
                                        <td class="small">
                                            <div class="mb-1"><i class="bi bi-telephone text-muted me-1"></i> <?php echo htmlspecialchars($user['phone_number'] ?? 'N/A'); ?></div>
                                            <div class="text-muted"><i class="bi bi-clock text-muted me-1"></i> <?php echo date('d/m/Y H:i', strtotime($app['submitted_at'])); ?></div>
                                        </td>
                                        <td class="small">
                                            <strong class="d-block text-dark"><?php echo htmlspecialchars($major['major_name'] ?? 'Không rõ'); ?></strong>
                                            <div class="mt-1 d-flex gap-2 flex-wrap">
                                                <span class="badge bg-light text-dark border-0 shadow-xs" style="font-size: 0.7rem;"><?php echo htmlspecialchars($method['method_name'] ?? 'Không rõ'); ?></span>
                                                <span class="badge bg-light text-info border-0 shadow-xs" style="font-size: 0.7rem;">NV: <?php echo htmlspecialchars($app['priority'] ?? '1'); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <?php 
                                                $feeDisplay = $method['application_fee'] ?? $app['fee_amount'] ?? 0;
                                            ?>
                                            <?php if (!empty($app['receipt_url'])): ?>
                                                <a href="<?php echo htmlspecialchars($app['receipt_url']); ?>" target="_blank" class="btn btn-xs btn-outline-brand py-1 px-2 border-0 bg-light" style="font-size: 0.75rem;">
                                                    <i class="bi bi-image"></i> Biên lai
                                                </a>
                                                <div class="small fw-bold mt-1 text-center" style="font-size: 0.7rem;"><?php echo number_format($feeDisplay, 0, ',', '.'); ?>đ</div>
                                            <?php else: ?>
                                                <span class="text-muted italic small">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-status <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                        </td>
                                        <td class="small" style="max-width:180px;">
                                            <?php if (!empty($app['admin_notes'])): ?>
                                                <div class="text-muted" style="font-size:.78rem;white-space:pre-wrap;word-break:break-word;">
                                                    <?php echo htmlspecialchars($app['admin_notes']); ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted" style="font-size:.75rem;">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-outline-brand" onclick="openUpdateModal('<?php echo $app['id']; ?>', '<?php echo $app['status']; ?>', '<?php echo htmlspecialchars($app['admin_notes'] ?? '', ENT_QUOTES); ?>')">
                                                <i class="bi bi-check2-square"></i> Duyệt
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php $queryParams = ['status' => $statusFilter ?? '', 'search' => $searchStr ?? '']; ?>
                        <?php include __DIR__ . '/includes/paginator.php'; ?>
                        <div class="text-center text-muted small mt-2">Tổng số: <?php echo number_format($totalApps); ?> hồ sơ</div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Update Status Single -->
<div class="modal fade" id="updateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="">
                <div class="modal-header bg-brand text-white border-0">
                    <h5 class="modal-title fw-bold">Cập nhật Hồ sơ</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="app_id" id="modal_app_id">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted text-uppercase mb-2">Trạng thái Hồ sơ</label>
                        <select class="form-select border-0 bg-light" name="status" id="modal_status">
                            <option value="PENDING">Chờ duyệt</option>
                            <option value="APPROVED">Hợp lệ (Trúng tuyển)</option>
                            <option value="REJECTED">Từ chối (Tài liệu không hợp lệ)</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label fw-bold small text-muted text-uppercase mb-2">Ghi chú của Quản trị viên</label>
                        <textarea class="form-control border-0 bg-light" name="admin_notes" id="modal_notes" rows="3" placeholder="Nhập ghi chú (nếu có)..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" class="btn btn-brand w-100 py-2 fw-bold">XÁC NHẬN CẬP NHẬT</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Confirm Bulk Action -->
<div class="modal fade" id="confirmBulkModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-octagon me-2"></i> Xác nhận thao tác</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <i class="bi bi-exclamation-triangle text-danger mb-3 d-block" style="font-size: 3rem;"></i>
                <p class="mb-0 fs-5">Bạn muốn áp dụng thao tác này cho <strong id="confirmCount" class="badge bg-danger">0</strong> hồ sơ đã chọn không?</p>
                <p class="text-muted small mt-2 mb-3">Hành động này không thể hoàn tác.</p>
                <div class="text-start bg-light p-3 rounded text-muted">
                    <label class="form-label fw-bold small text-uppercase mb-2"><i class="bi bi-pencil-square"></i> Ghi chú hàng loạt (Tùy chọn)</label>
                    <textarea class="form-control border-0 bg-white shadow-sm" id="modal_bulk_notes" rows="2" placeholder="Nhập ghi chú chung áp dụng cho tất cả hồ sơ này..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-0 justify-content-center pb-4">
                <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Hủy bỏ</button>
                <button type="button" class="btn btn-danger px-4" id="btnConfirmBulk">Đồng ý thực hiện</button>
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
            "paging": false,
            "searching": false,
            "info": false,
            "order": [[3, "desc"]], // Sắp xếp theo ngày nộp
            "columnDefs": [
                { "orderable": false, "targets": 0 } // Không sắp xếp cột Checkbox
            ]
        });

        // Xử lý Tải xuống bằng File vật lý từ Server (Vượt bypass mọi Extension IDM)
        $('#btnExportDocs').on('click', function(e) {
            e.preventDefault();
            var btn = $(this);
            var originalHtml = btn.html();
            btn.html('<span class="spinner-border spinner-border-sm"></span> Đang tải...').prop('disabled', true);
            
            // Xóa cache trình duyệt bằng tham số thời gian
            var cacheBuster = new Date().getTime();
            
            $.ajax({
                url: 'api/export_csv.php?t=' + cacheBuster,
                method: 'GET',
                dataType: 'json',
                success: function(res) {
                    if(res.status === 'success' && res.file_url) {
                        // Kích hoạt download bằng cách trỏ tới URL vật lý
                        // IDM hay bất cứ Extension nào cũng không thể chặn được dạng này
                        var link = document.createElement('a');
                        link.href = res.file_url;
                        link.download = res.filename;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    } else {
                        alert("Lỗi xuất file: " + (res.message || 'Unknown'));
                    }
                },
                error: function(err) {
                    alert("Có lỗi xảy ra khi tạo file xuất. Vui lòng thử lại.");
                    console.error(err);
                },
                complete: function() {
                    btn.html(originalHtml).prop('disabled', false);
                }
            });
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
                $('#bulkActionsPanel').removeClass('d-none').hide().slideDown(200);
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
        document.getElementById('bulkAdminNotesInput').value = document.getElementById('modal_bulk_notes').value;
        document.getElementById('bulkForm').submit();
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Đang xử lý...';
    });

    const updateModal = new bootstrap.Modal(document.getElementById('updateModal'));
    function openUpdateModal(appId, currentStatus, notes) {
        document.getElementById('modal_app_id').value = appId;
        document.getElementById('modal_status').value = currentStatus;
        document.getElementById('modal_notes').value = notes || '';
        updateModal.show();
    }
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>

