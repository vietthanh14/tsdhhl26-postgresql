<?php
require_once __DIR__ . '/includes/admin_init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add_method') {
        $res = $supabaseAdmin->insert('admission_methods', ['method_name' => $_POST['method_name'], 'application_fee' => $_POST['application_fee'] ?? 0]);
        if (in_array($res['code'], [201, 200, 204])) $_SESSION['msg'] = "Thêm Phương thức XT thành công!";
        else $_SESSION['err'] = "Lỗi thêm phương thức: " . json_encode($res['data']);
        Cache::flush(); header("Location: manage_methods.php"); exit;
    }
    elseif ($action === 'edit_method') {
        $res = $supabaseAdmin->update('admission_methods', 'id', $_POST['id'], ['method_name' => $_POST['method_name'], 'application_fee' => $_POST['application_fee'] ?? 0]);
        if (in_array($res['code'], [200, 204])) $_SESSION['msg'] = "Cập nhật Phương thức XT thành công!";
        else $_SESSION['err'] = "Lỗi sửa phương thức: " . json_encode($res['data']);
        Cache::flush(); header("Location: manage_methods.php"); exit;
    }
    elseif ($action === 'delete_method') {
        $res = $supabaseAdmin->delete('admission_methods', 'id', $_POST['id']);
        if (in_array($res['code'], [200, 204])) $_SESSION['msg'] = "Xóa Phương thức XT thành công!";
        else $_SESSION['err'] = "Lỗi: Không thể xóa phương thức đang được sử dụng.";
        Cache::flush(); header("Location: manage_methods.php"); exit;
    }
    elseif ($action === 'bulk_update_fee') {
        $ids = json_decode($_POST['method_ids'] ?? '[]', true);
        $new_fee = (int)($_POST['new_fee'] ?? 0);
        if (!empty($ids) && $new_fee >= 0) {
            $success = 0;
            foreach ($ids as $id) { $res = $supabaseAdmin->update('admission_methods', 'id', (int)$id, ['application_fee' => $new_fee]); if (in_array($res['code'], [200, 204])) $success++; }
            $_SESSION['msg'] = "Đã cập nhật lệ phí cho {$success}/" . count($ids) . " phương thức thành công!";
        } else { $_SESSION['err'] = "Dữ liệu không hợp lệ."; }
        Cache::flush(); header("Location: manage_methods.php"); exit;
    }
}

$methodsRes = $supabaseAdmin->select('admission_methods', 'order=id.asc');
$methods = $methodsRes['code'] == 200 ? $methodsRes['data'] : [];

$pageTitle = 'Quản lý Phương thức Xét tuyển & Lệ phí';
include __DIR__ . '/includes/admin_header.php';
?>

<div class="d-flex flex-wrap justify-content-end align-items-center mb-3 gap-2">
    <div class="d-flex gap-2 align-items-center">
        <div class="input-group input-group-sm" style="width: 250px;">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
            <input type="text" class="form-control border-start-0" placeholder="Tìm phương thức..." oninput="filterTable(this.value, 'methodsBody')">
        </div>
        <button class="btn btn-sm btn-brand" data-bs-toggle="modal" data-bs-target="#addMethodModal">+ Thêm PT</button>
    </div>
</div>

<!-- Bulk Update Toolbar -->
<div class="alert alert-warning border-0 shadow-sm d-none align-items-center gap-3 flex-wrap" id="bulkFeeToolbar">
    <span class="fw-semibold"><i class="bi bi-check2-square me-1"></i> Đã chọn <span id="bulkSelectedCount" class="badge bg-dark">0</span> phương thức</span>
    <div class="input-group input-group-sm" style="width: 220px;">
        <span class="input-group-text bg-white">Lệ phí mới</span>
        <input type="number" id="bulkFeeInput" class="form-control" placeholder="VD: 300000" min="0">
        <span class="input-group-text bg-white">đ</span>
    </div>
    <button class="btn btn-sm btn-warning fw-bold" onclick="submitBulkFeeUpdate()"><i class="bi bi-pencil-square me-1"></i> Cập nhật hàng loạt</button>
    <button class="btn btn-sm btn-outline-secondary" onclick="clearBulkSelection()"><i class="bi bi-x-lg"></i> Bỏ chọn</button>
</div>

<form method="POST" id="bulkFeeForm" class="d-none">
    <input type="hidden" name="action" value="bulk_update_fee">
    <input type="hidden" name="new_fee" id="bulkFeeHidden">
    <input type="hidden" name="method_ids" id="bulkMethodIdsHidden">
</form>

<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 w-100">
                <thead class="table-light">
                    <tr>
                        <th style="width:40px;"><input class="form-check-input" type="checkbox" id="selectAllMethods" onchange="toggleSelectAll(this)"></th>
                        <th>ID</th>
                        <th>Tên Phương thức</th>
                        <th>Lệ phí (VNĐ)</th>
                        <th class="text-end">Hành động</th>
                    </tr>
                </thead>
                <tbody id="methodsBody">
                    <?php foreach($methods as $mt): ?>
                    <tr>
                        <td><input class="form-check-input method-bulk-chk" type="checkbox" value="<?php echo $mt['id']; ?>" onchange="updateBulkToolbar()"></td>
                        <td><span class="text-muted small">#<?php echo $mt['id']; ?></span></td>
                        <td class="fw-semibold text-brand"><?php echo htmlspecialchars($mt['method_name']); ?></td>
                        <td class="text-danger fw-semibold"><?php echo number_format($mt['application_fee'] ?? 0, 0, ',', '.'); ?> đ</td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-warning me-1" onclick="openEditMethodModal('<?php echo $mt['id']; ?>', '<?php echo htmlspecialchars($mt['method_name'], ENT_QUOTES); ?>', '<?php echo $mt['application_fee'] ?? 0; ?>')" title="Sửa"><i class="bi bi-pencil"></i></button>
                            <form method="POST" class="d-inline" onsubmit="event.preventDefault(); window.confirmDelete ? confirmDelete(this, 'Xóa phương thức này?') : (confirm('Xóa phương thức này?') && this.submit());">
                                <input type="hidden" name="action" value="delete_method"><input type="hidden" name="id" value="<?php echo $mt['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Xóa"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Thêm PT -->
<div class="modal fade" id="addMethodModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content border-0 shadow">
        <form method="POST">
            <div class="modal-header bg-brand text-white border-0"><h5 class="modal-title fw-bold">Thêm Phương thức Xét tuyển</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><input type="hidden" name="action" value="add_method"><div class="mb-3"><label class="form-label">Tên phương thức</label><input type="text" name="method_name" class="form-control" required placeholder="VD: Xét tuyển học bạ..."></div><div class="mb-3"><label class="form-label">Lệ phí (VNĐ)</label><input type="number" name="application_fee" class="form-control" required value="300000" min="0"></div></div>
            <div class="modal-footer"><button type="submit" class="btn btn-brand w-100">Lưu</button></div>
        </form>
    </div></div>
</div>

<!-- Modal Sửa PT -->
<div class="modal fade" id="editMethodModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content border-0 shadow">
        <form method="POST">
            <div class="modal-header bg-brand text-white border-0"><h5 class="modal-title fw-bold">Sửa Phương thức</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><input type="hidden" name="action" value="edit_method"><input type="hidden" name="id" id="edit_method_id"><div class="mb-3"><label class="form-label">Tên phương thức</label><input type="text" name="method_name" id="edit_method_name" class="form-control" required></div><div class="mb-3"><label class="form-label">Lệ phí (VNĐ)</label><input type="number" name="application_fee" id="edit_method_fee" class="form-control" required min="0"></div></div>
            <div class="modal-footer"><button type="submit" class="btn btn-warning w-100">Cập nhật</button></div>
        </form>
    </div></div>
</div>

<!-- Modal Xác nhận Cập nhật Hàng loạt -->
<div class="modal fade" id="confirmBulkFeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow">
        <div class="modal-header bg-warning border-0"><h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i> Cập nhật Lệ phí Hàng loạt</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body p-4 text-center">
            <i class="bi bi-currency-exchange text-warning mb-3 d-block" style="font-size: 3rem;"></i>
            <p class="mb-0 fs-5" id="bulkFeeConfirmMsg"></p>
        </div>
        <div class="modal-footer border-0 justify-content-center pb-4">
            <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Hủy</button>
            <button type="button" class="btn btn-warning px-4 fw-bold" onclick="document.getElementById('bulkFeeForm').submit(); this.innerHTML='<span class=\'spinner-border spinner-border-sm\'></span> Đang cập nhật...'; this.classList.add('disabled');"><i class="bi bi-check-lg me-1"></i> Xác nhận</button>
        </div>
    </div></div>
</div>

<script>
function openEditMethodModal(id, name, fee) {
    document.getElementById('edit_method_id').value = id;
    document.getElementById('edit_method_name').value = name;
    document.getElementById('edit_method_fee').value = fee;
    new bootstrap.Modal(document.getElementById('editMethodModal')).show();
}
function toggleSelectAll(m) { document.querySelectorAll('.method-bulk-chk').forEach(c => c.checked = m.checked); updateBulkToolbar(); }
function updateBulkToolbar() {
    const checked = document.querySelectorAll('.method-bulk-chk:checked');
    const tb = document.getElementById('bulkFeeToolbar');
    if (checked.length > 0) { tb.classList.remove('d-none'); tb.classList.add('d-flex'); document.getElementById('bulkSelectedCount').textContent = checked.length; }
    else { tb.classList.add('d-none'); tb.classList.remove('d-flex'); }
    const all = document.querySelectorAll('.method-bulk-chk');
    document.getElementById('selectAllMethods').checked = all.length > 0 && checked.length === all.length;
}
function clearBulkSelection() { document.querySelectorAll('.method-bulk-chk').forEach(c => c.checked = false); document.getElementById('selectAllMethods').checked = false; updateBulkToolbar(); }
function submitBulkFeeUpdate() {
    const fee = document.getElementById('bulkFeeInput').value;
    if (!fee || fee < 0) { showToast('Nhập lệ phí hợp lệ!', 'warning'); return; }
    const ids = Array.from(document.querySelectorAll('.method-bulk-chk:checked')).map(c => c.value);
    if (ids.length === 0) { showToast('Chưa chọn phương thức!', 'warning'); return; }
    document.getElementById('bulkFeeConfirmMsg').innerHTML = 'Cập nhật <strong>' + new Intl.NumberFormat('vi-VN').format(fee) + 'đ</strong> cho <strong>' + ids.length + '</strong> phương thức?';
    document.getElementById('bulkFeeHidden').value = fee;
    document.getElementById('bulkMethodIdsHidden').value = JSON.stringify(ids);
    new bootstrap.Modal(document.getElementById('confirmBulkFeeModal')).show();
}
</script>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
