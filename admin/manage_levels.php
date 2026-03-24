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
    if ($action === 'add_level') {
        $res = $supabaseAdmin->insert('education_levels', ['name' => $_POST['name'], 'description' => $_POST['description']]);
        if (in_array($res['code'], [201, 200, 204])) $_SESSION['msg'] = "Thêm Hệ đào tạo thành công!";
        else $_SESSION['err'] = "Lỗi thêm hệ đào tạo (có thể trùng tên): " . json_encode($res['data']);
        Cache::flush(); header("Location: manage_levels.php"); exit;
    }
    elseif ($action === 'edit_level') {
        $res = $supabaseAdmin->update('education_levels', 'id', $_POST['id'], ['name' => $_POST['name'], 'description' => $_POST['description']]);
        if (in_array($res['code'], [200, 204])) $_SESSION['msg'] = "Cập nhật Hệ đào tạo thành công!";
        else $_SESSION['err'] = "Lỗi sửa hệ đào tạo: " . json_encode($res['data']);
        Cache::flush(); header("Location: manage_levels.php"); exit;
    }
    elseif ($action === 'delete_level') {
        $res = $supabaseAdmin->delete('education_levels', 'id', $_POST['id']);
        if (in_array($res['code'], [200, 204])) $_SESSION['msg'] = "Xóa Hệ đào tạo thành công!";
        else $_SESSION['err'] = "Lỗi: Không thể xóa Hệ đang có chứa ngành học.";
        Cache::flush(); header("Location: manage_levels.php"); exit;
    }
}

$levelsRes = $supabaseAdmin->select('education_levels', 'order=id.asc');
$levels = $levelsRes['code'] == 200 ? $levelsRes['data'] : [];

$pageTitle = 'Quản lý Hệ Đào Tạo';
include __DIR__ . '/includes/admin_header.php';
?>

<div class="d-flex flex-wrap justify-content-end align-items-center mb-3 gap-2">
    <div class="d-flex gap-2 align-items-center">
        <div class="input-group input-group-sm" style="width: 250px;">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
            <input type="text" class="form-control border-start-0" placeholder="Tìm hệ đào tạo..." oninput="filterTable(this.value, 'levelsBody')">
        </div>
        <button class="btn btn-sm btn-brand" data-bs-toggle="modal" data-bs-target="#addLevelModal">+ Thêm Hệ</button>
    </div>
</div>

<div class="bg-white p-4 rounded-3 shadow-sm border-0">
<table class="table table-hover align-middle mb-0">
    <thead class="table-light"><tr><th>ID</th><th>Tên Hệ Đào Tạo</th><th>Mô tả</th><th>Hành động</th></tr></thead>
    <tbody id="levelsBody">
        <?php foreach($levels as $l): ?>
        <tr>
            <td><?php echo $l['id']; ?></td>
            <td class="fw-semibold"><?php echo htmlspecialchars($l['name']); ?></td>
            <td><?php echo htmlspecialchars($l['description'] ?? ''); ?></td>
            <td>
                <button class="btn btn-sm btn-outline-warning" onclick="openEditLevelModal('<?php echo $l['id']; ?>', '<?php echo htmlspecialchars($l['name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($l['description'] ?? '', ENT_QUOTES); ?>')">Sửa</button>
                <form method="POST" class="d-inline" onsubmit="event.preventDefault(); confirmDelete(this, 'Xóa hệ đào tạo này?');">
                    <input type="hidden" name="action" value="delete_level"><input type="hidden" name="id" value="<?php echo $l['id']; ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Xóa</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>

<!-- Modal Thêm Hệ -->
<div class="modal fade" id="addLevelModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content border-0 shadow">
        <form method="POST">
            <div class="modal-header bg-brand text-white border-0"><h5 class="modal-title fw-bold">Thêm Hệ Đào Tạo</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="action" value="add_level">
                <div class="mb-3"><label class="form-label">Tên hệ</label><input type="text" name="name" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Mô tả ngắn</label><input type="text" name="description" class="form-control"></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-brand w-100">Lưu hệ đào tạo</button></div>
        </form>
    </div></div>
</div>

<!-- Modal Sửa Hệ -->
<div class="modal fade" id="editLevelModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content border-0 shadow">
        <form method="POST">
            <div class="modal-header bg-brand text-white border-0"><h5 class="modal-title fw-bold">Sửa Hệ Đào Tạo</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="action" value="edit_level"><input type="hidden" name="id" id="edit_level_id">
                <div class="mb-3"><label class="form-label">Tên hệ</label><input type="text" name="name" id="edit_level_name" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Mô tả ngắn</label><input type="text" name="description" id="edit_level_description" class="form-control"></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-warning w-100">Cập nhật</button></div>
        </form>
    </div></div>
</div>

<script>
function openEditLevelModal(id, name, desc) {
    document.getElementById('edit_level_id').value = id;
    document.getElementById('edit_level_name').value = name;
    document.getElementById('edit_level_description').value = desc;
    new bootstrap.Modal(document.getElementById('editLevelModal')).show();
}
</script>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
