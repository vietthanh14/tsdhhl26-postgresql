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
    if ($action === 'add_method') {
        $res = $supabaseAdmin->insert('admission_methods', ['method_name' => $_POST['method_name']]);
        if (in_array($res['code'], [201, 200, 204])) $_SESSION['msg'] = "Thêm Phương thức XT thành công!";
        else $_SESSION['err'] = "Lỗi thêm phương thức: " . json_encode($res['data']);
        Cache::flush(); header("Location: manage_methods.php"); exit;
    }
    elseif ($action === 'edit_method') {
        $res = $supabaseAdmin->update('admission_methods', 'id', $_POST['id'], ['method_name' => $_POST['method_name']]);
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
}

$methodsRes = $supabaseAdmin->select('admission_methods', 'order=id.asc');
$methods = $methodsRes['code'] == 200 ? $methodsRes['data'] : [];

$pageTitle = 'Quản lý Phương thức Xét tuyển';
include __DIR__ . '/includes/admin_header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h5 class="fw-bold m-0">Phương thức Xét tuyển</h5>
    <div class="d-flex gap-2 align-items-center">
        <div class="input-group input-group-sm" style="width: 250px;">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
            <input type="text" class="form-control border-start-0" placeholder="Tìm phương thức..." oninput="filterTable(this.value, 'methodsBody')">
        </div>
        <button class="btn btn-sm btn-brand" data-bs-toggle="modal" data-bs-target="#addMethodModal">+ Thêm PT</button>
    </div>
</div>

<div class="bg-white p-4 rounded-3 shadow-sm border-0">
<table class="table table-hover align-middle mb-0">
    <thead class="table-light"><tr><th>ID</th><th>Tên Phương thức</th><th>Hành động</th></tr></thead>
    <tbody id="methodsBody">
        <?php foreach($methods as $mt): ?>
        <tr>
            <td><?php echo $mt['id']; ?></td>
            <td class="fw-semibold"><?php echo htmlspecialchars($mt['method_name']); ?></td>
            <td>
                <button class="btn btn-sm btn-outline-warning" onclick="openEditMethodModal('<?php echo $mt['id']; ?>', '<?php echo htmlspecialchars($mt['method_name'], ENT_QUOTES); ?>')">Sửa</button>
                <form method="POST" class="d-inline" onsubmit="event.preventDefault(); confirmDelete(this, 'Xóa phương thức này?');">
                    <input type="hidden" name="action" value="delete_method"><input type="hidden" name="id" value="<?php echo $mt['id']; ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Xóa</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>

<!-- Modal Thêm PT -->
<div class="modal fade" id="addMethodModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content border-0 shadow">
        <form method="POST">
            <div class="modal-header bg-brand text-white border-0"><h5 class="modal-title fw-bold">Thêm Phương thức Xét tuyển</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><input type="hidden" name="action" value="add_method"><div class="mb-3"><label class="form-label">Tên phương thức</label><input type="text" name="method_name" class="form-control" required placeholder="VD: Xét tuyển học bạ..."></div></div>
            <div class="modal-footer"><button type="submit" class="btn btn-brand w-100">Lưu</button></div>
        </form>
    </div></div>
</div>

<!-- Modal Sửa PT -->
<div class="modal fade" id="editMethodModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content border-0 shadow">
        <form method="POST">
            <div class="modal-header bg-brand text-white border-0"><h5 class="modal-title fw-bold">Sửa Phương thức</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><input type="hidden" name="action" value="edit_method"><input type="hidden" name="id" id="edit_method_id"><div class="mb-3"><label class="form-label">Tên phương thức</label><input type="text" name="method_name" id="edit_method_name" class="form-control" required></div></div>
            <div class="modal-footer"><button type="submit" class="btn btn-warning w-100">Cập nhật</button></div>
        </form>
    </div></div>
</div>

<script>
function openEditMethodModal(id, name) {
    document.getElementById('edit_method_id').value = id;
    document.getElementById('edit_method_name').value = name;
    new bootstrap.Modal(document.getElementById('editMethodModal')).show();
}
</script>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
