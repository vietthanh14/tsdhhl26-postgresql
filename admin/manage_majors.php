<?php
require_once __DIR__ . '/../config/supabase.php';
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ' . BASE_URL . '/admin/login.php'); exit;
}
require_once __DIR__ . '/../lib/SupabaseClient.php';
require_once __DIR__ . '/../lib/Cache.php';
$supabaseAdmin = new SupabaseClient('service');
$message = $_SESSION['msg'] ?? ''; $error = $_SESSION['err'] ?? '';
unset($_SESSION['msg'], $_SESSION['err']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add_major') {
        $data = ['major_code' => $_POST['major_code'], 'major_name' => $_POST['major_name'], 'education_level_id' => $_POST['education_level_id'], 'application_fee' => $_POST['application_fee'], 'zalo_link' => trim($_POST['zalo_link'] ?? '')];
        $res = $supabaseAdmin->insert('majors', $data);
        if (in_array($res['code'], [201, 200, 204])) $_SESSION['msg'] = "Thêm Ngành học thành công!";
        else $_SESSION['err'] = "Lỗi thêm ngành (có thể trùng mã): " . json_encode($res['data']);
        Cache::flush(); header("Location: manage_majors.php"); exit;
    }
    elseif ($action === 'edit_major') {
        $data = ['major_code' => $_POST['major_code'], 'major_name' => $_POST['major_name'], 'education_level_id' => $_POST['education_level_id'], 'application_fee' => $_POST['application_fee'], 'zalo_link' => trim($_POST['zalo_link'] ?? '')];
        $res = $supabaseAdmin->update('majors', 'id', $_POST['id'], $data);
        if (in_array($res['code'], [200, 204])) $_SESSION['msg'] = "Cập nhật Ngành học thành công!";
        else $_SESSION['err'] = "Lỗi sửa ngành: " . json_encode($res['data']);
        Cache::flush(); header("Location: manage_majors.php"); exit;
    }
    elseif ($action === 'delete_major') {
        $res = $supabaseAdmin->delete('majors', 'id', $_POST['id']);
        if (in_array($res['code'], [200, 204])) $_SESSION['msg'] = "Xóa Ngành thành công!";
        else $_SESSION['err'] = "Lỗi: Không thể xóa ngành đã có hồ sơ.";
        Cache::flush(); header("Location: manage_majors.php"); exit;
    }
    elseif ($action === 'bulk_update_fee') {
        $ids = json_decode($_POST['major_ids'] ?? '[]', true);
        $new_fee = (int)($_POST['new_fee'] ?? 0);
        if (!empty($ids) && $new_fee >= 0) {
            $success = 0;
            foreach ($ids as $id) { $res = $supabaseAdmin->update('majors', 'id', (int)$id, ['application_fee' => $new_fee]); if (in_array($res['code'], [200, 204])) $success++; }
            $_SESSION['msg'] = "Đã cập nhật lệ phí cho {$success}/" . count($ids) . " ngành thành công!";
        } else { $_SESSION['err'] = "Dữ liệu không hợp lệ."; }
        Cache::flush(); header("Location: manage_majors.php"); exit;
    }
}

$levelsRes = $supabaseAdmin->select('education_levels', 'order=id.asc');
$levels = $levelsRes['code'] == 200 ? $levelsRes['data'] : [];
$majorsRes = $supabaseAdmin->select('majors', 'select=*,education_levels(name)&order=id.desc');
$majors = $majorsRes['code'] == 200 ? $majorsRes['data'] : [];

$pageTitle = 'Quản lý Ngành Học & Lệ Phí';
include __DIR__ . '/includes/admin_header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h5 class="fw-bold m-0">Danh sách Ngành Đăng Ký</h5>
    <div class="d-flex gap-2 align-items-center">
        <div class="input-group input-group-sm" style="width: 250px;">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
            <input type="text" class="form-control border-start-0" placeholder="Tìm ngành học..." oninput="filterTable(this.value, 'majorsBody')">
        </div>
        <button class="btn btn-sm btn-brand" data-bs-toggle="modal" data-bs-target="#addMajorModal">+ Thêm Ngành</button>
    </div>
</div>

<!-- Bulk Update Toolbar -->
<div class="alert alert-warning border-0 shadow-sm d-none align-items-center gap-3 flex-wrap" id="bulkFeeToolbar">
    <span class="fw-semibold"><i class="bi bi-check2-square me-1"></i> Đã chọn <span id="bulkSelectedCount" class="badge bg-dark">0</span> ngành</span>
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
    <input type="hidden" name="major_ids" id="bulkMajorIdsHidden">
</form>

<div class="bg-white p-4 rounded-3 shadow-sm border-0">
<table class="table table-hover align-middle mb-0">
    <thead class="table-light"><tr>
        <th style="width:40px;"><input class="form-check-input" type="checkbox" id="selectAllMajors" onchange="toggleSelectAllMajors(this)"></th>
        <th>Mã Ngành</th><th>Tên Ngành</th><th>Hệ Đào Tạo</th><th>Lệ phí (VND)</th><th>Nhóm Zalo</th><th>Hành động</th>
    </tr></thead>
    <tbody id="majorsBody">
        <?php foreach($majors as $m): ?>
        <tr>
            <td><input class="form-check-input major-bulk-chk" type="checkbox" value="<?php echo $m['id']; ?>" onchange="updateBulkToolbar()"></td>
            <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($m['major_code']); ?></span></td>
            <td class="fw-bold text-dark"><?php echo htmlspecialchars($m['major_name']); ?></td>
            <td><span class="badge bg-info text-white"><?php echo htmlspecialchars($m['education_levels']['name'] ?? 'N/A'); ?></span></td>
            <td class="text-danger fw-semibold"><?php echo number_format($m['application_fee'], 0, ',', '.'); ?> đ</td>
            <td>
                <?php if (!empty($m['zalo_link'])): ?>
                    <a href="<?php echo htmlspecialchars($m['zalo_link']); ?>" target="_blank" class="btn btn-sm btn-outline-success rounded-pill px-3"><i class="bi bi-chat-dots-fill"></i> Tham gia</a>
                <?php else: ?><span class="text-muted small">Chưa có</span><?php endif; ?>
            </td>
            <td>
                <button class="btn btn-sm btn-outline-warning" onclick="openEditMajorModal('<?php echo $m['id']; ?>', '<?php echo htmlspecialchars($m['major_code'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($m['major_name'], ENT_QUOTES); ?>', '<?php echo $m['education_level_id']; ?>', '<?php echo $m['application_fee']; ?>', '<?php echo htmlspecialchars($m['zalo_link'] ?? '', ENT_QUOTES); ?>')">Sửa</button>
                <form method="POST" class="d-inline" onsubmit="event.preventDefault(); confirmDelete(this, 'Xóa ngành học này?');">
                    <input type="hidden" name="action" value="delete_major"><input type="hidden" name="id" value="<?php echo $m['id']; ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Xóa</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>

<!-- Modal Thêm Ngành -->
<div class="modal fade" id="addMajorModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content border-0 shadow">
        <form method="POST">
            <div class="modal-header bg-brand text-white border-0"><h5 class="modal-title fw-bold">Thêm Ngành Học</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="action" value="add_major">
                <div class="row"><div class="col-4 mb-3"><label class="form-label">Mã ngành</label><input type="text" name="major_code" class="form-control" required></div><div class="col-8 mb-3"><label class="form-label">Tên ngành</label><input type="text" name="major_name" class="form-control" required></div></div>
                <div class="mb-3"><label class="form-label">Thuộc Hệ Đào Tạo</label><select name="education_level_id" class="form-select" required><option value="">-- Chọn hệ --</option><?php foreach($levels as $l): ?><option value="<?php echo $l['id']; ?>"><?php echo htmlspecialchars($l['name']); ?></option><?php endforeach; ?></select></div>
                <div class="mb-3"><label class="form-label">Lệ phí (VNĐ)</label><input type="number" name="application_fee" class="form-control" required value="300000" min="0"></div>
                <div class="mb-3"><label class="form-label"><i class="bi bi-chat-dots-fill text-success me-1"></i>Link Zalo</label><input type="url" name="zalo_link" class="form-control" placeholder="https://zalo.me/g/..."></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-brand w-100">Lưu ngành học</button></div>
        </form>
    </div></div>
</div>

<!-- Modal Sửa Ngành -->
<div class="modal fade" id="editMajorModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content border-0 shadow">
        <form method="POST">
            <div class="modal-header bg-brand text-white border-0"><h5 class="modal-title fw-bold">Sửa Ngành Học</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="action" value="edit_major"><input type="hidden" name="id" id="edit_major_id">
                <div class="row"><div class="col-4 mb-3"><label class="form-label">Mã ngành</label><input type="text" name="major_code" id="edit_major_code" class="form-control" required></div><div class="col-8 mb-3"><label class="form-label">Tên ngành</label><input type="text" name="major_name" id="edit_major_name" class="form-control" required></div></div>
                <div class="mb-3"><label class="form-label">Thuộc Hệ Đào Tạo</label><select name="education_level_id" id="edit_major_level_id" class="form-select" required><option value="">-- Chọn hệ --</option><?php foreach($levels as $l): ?><option value="<?php echo $l['id']; ?>"><?php echo htmlspecialchars($l['name']); ?></option><?php endforeach; ?></select></div>
                <div class="mb-3"><label class="form-label">Lệ phí (VNĐ)</label><input type="number" name="application_fee" id="edit_major_fee" class="form-control" required min="0"></div>
                <div class="mb-3"><label class="form-label"><i class="bi bi-chat-dots-fill text-success me-1"></i>Link Zalo</label><input type="url" name="zalo_link" id="edit_major_zalo" class="form-control" placeholder="https://zalo.me/g/..."></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-warning w-100">Cập nhật</button></div>
        </form>
    </div></div>
</div>

<!-- Modal Xác nhận Cập nhật Hàng loạt -->
<div class="modal fade" id="confirmBulkFeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow">
        <div class="modal-header bg-warning border-0"><h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i> Cập nhật Hàng loạt</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
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
function openEditMajorModal(id, code, name, levelId, fee, zalo) {
    document.getElementById('edit_major_id').value = id; document.getElementById('edit_major_code').value = code;
    document.getElementById('edit_major_name').value = name; document.getElementById('edit_major_level_id').value = levelId;
    document.getElementById('edit_major_fee').value = fee; document.getElementById('edit_major_zalo').value = zalo || '';
    new bootstrap.Modal(document.getElementById('editMajorModal')).show();
}
function toggleSelectAllMajors(m) { document.querySelectorAll('.major-bulk-chk').forEach(c => c.checked = m.checked); updateBulkToolbar(); }
function updateBulkToolbar() {
    const checked = document.querySelectorAll('.major-bulk-chk:checked');
    const tb = document.getElementById('bulkFeeToolbar');
    if (checked.length > 0) { tb.classList.remove('d-none'); tb.classList.add('d-flex'); document.getElementById('bulkSelectedCount').textContent = checked.length; }
    else { tb.classList.add('d-none'); tb.classList.remove('d-flex'); }
    const all = document.querySelectorAll('.major-bulk-chk');
    document.getElementById('selectAllMajors').checked = all.length > 0 && checked.length === all.length;
}
function clearBulkSelection() { document.querySelectorAll('.major-bulk-chk').forEach(c => c.checked = false); document.getElementById('selectAllMajors').checked = false; updateBulkToolbar(); }
function submitBulkFeeUpdate() {
    const fee = document.getElementById('bulkFeeInput').value;
    if (!fee || fee < 0) { showToast('Nhập lệ phí hợp lệ!', 'warning'); return; }
    const ids = Array.from(document.querySelectorAll('.major-bulk-chk:checked')).map(c => c.value);
    if (ids.length === 0) { showToast('Chưa chọn ngành!', 'warning'); return; }
    document.getElementById('bulkFeeConfirmMsg').innerHTML = 'Cập nhật <strong>' + new Intl.NumberFormat('vi-VN').format(fee) + 'đ</strong> cho <strong>' + ids.length + '</strong> ngành?';
    document.getElementById('bulkFeeHidden').value = fee;
    document.getElementById('bulkMajorIdsHidden').value = JSON.stringify(ids);
    new bootstrap.Modal(document.getElementById('confirmBulkFeeModal')).show();
}
</script>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
