<?php
require_once __DIR__ . '/includes/admin_init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add_major') {
        $data = ['major_code' => $_POST['major_code'], 'major_name' => $_POST['major_name'], 'education_level_id' => $_POST['education_level_id'], 'zalo_link' => trim($_POST['zalo_link'] ?? '')];
        $res = $supabaseAdmin->insert('majors', $data);
        if (in_array($res['code'], [201, 200, 204])) $_SESSION['msg'] = "Thêm Ngành học thành công!";
        else $_SESSION['err'] = "Lỗi thêm ngành: " . ($res['error'] ?? json_encode($res['data']));
        Cache::flush(); header("Location: manage_majors.php"); exit;
    }
    elseif ($action === 'edit_major') {
        $data = ['major_code' => $_POST['major_code'], 'major_name' => $_POST['major_name'], 'education_level_id' => $_POST['education_level_id'], 'zalo_link' => trim($_POST['zalo_link'] ?? '')];
        $res = $supabaseAdmin->update('majors', 'id', $_POST['id'], $data);
        if (in_array($res['code'], [200, 204])) $_SESSION['msg'] = "Cập nhật Ngành học thành công!";
        else $_SESSION['err'] = "Lỗi sửa ngành: " . ($res['error'] ?? json_encode($res['data']));
        Cache::flush(); header("Location: manage_majors.php"); exit;
    }
    elseif ($action === 'delete_major') {
        $res = $supabaseAdmin->delete('majors', 'id', $_POST['id']);
        if (in_array($res['code'], [200, 204])) $_SESSION['msg'] = "Xóa Ngành thành công!";
        else $_SESSION['err'] = "Lỗi xóa ngành: " . ($res['error'] ?? json_encode($res['data']));
        Cache::flush(); header("Location: manage_majors.php"); exit;
    }
}

$levelsRes = $supabaseAdmin->select('education_levels', 'order=id.asc');
$levels = $levelsRes['code'] == 200 ? $levelsRes['data'] : [];

$sql = "SELECT m.*, el.name as education_levels__name 
        FROM majors m 
        LEFT JOIN education_levels el ON m.education_level_id = el.id 
        ORDER BY m.id DESC";
$majorsRes = $supabaseAdmin->rawQuery($sql);
if ($majorsRes['code'] !== 200) {
    die("LỖI ĐỌC DỮ LIỆU: " . $majorsRes['error']);
}
$majors = $majorsRes['data'] ?? [];

$pageTitle = 'Quản lý Ngành Học';
include __DIR__ . '/includes/admin_header.php';
?>

<div class="d-flex flex-wrap justify-content-end align-items-center mb-3 gap-2">
    <div class="d-flex gap-2 align-items-center">
        <div class="input-group input-group-sm" style="width: 250px;">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
            <input type="text" class="form-control border-start-0" placeholder="Tìm ngành học..." oninput="filterTable(this.value, 'majorsBody')">
        </div>
        <button class="btn btn-sm btn-brand" data-bs-toggle="modal" data-bs-target="#addMajorModal">+ Thêm Ngành</button>
    </div>
</div>


<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 w-100">
                <thead class="table-light">
                    <tr>
                        <th>Mã Ngành</th>
                        <th>Tên Ngành</th>
                        <th>Hệ Đào Tạo</th>
                        <th>Nhóm Zalo</th>
                        <th class="text-end">Hành động</th>
                    </tr>
                </thead>
                <tbody id="majorsBody">

                    <?php foreach($majors as $m): ?>
                    <tr>
                        <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($m['major_code']); ?></span></td>
                        <td class="fw-bold text-dark"><?php echo htmlspecialchars($m['major_name']); ?></td>
                        <td><span class="badge bg-info text-white"><?php echo htmlspecialchars($m['education_levels']['name'] ?? 'N/A'); ?></span></td>
                        <td>
                            <?php if (!empty($m['zalo_link'])): ?>
                                <a href="<?php echo htmlspecialchars($m['zalo_link']); ?>" target="_blank" class="btn btn-sm btn-outline-success border-0 px-2 py-1"><i class="bi bi-chat-dots-fill"></i> Nhóm</a>
                            <?php else: ?>
                                <span class="text-muted small">Chưa có</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-warning me-1" onclick="openEditMajorModal('<?php echo $m['id']; ?>', '<?php echo htmlspecialchars($m['major_code'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($m['major_name'], ENT_QUOTES); ?>', '<?php echo $m['education_level_id']; ?>', '<?php echo htmlspecialchars($m['zalo_link'] ?? '', ENT_QUOTES); ?>')" title="Sửa"><i class="bi bi-pencil"></i></button>
                            <form method="POST" class="d-inline" onsubmit="event.preventDefault(); window.confirmDelete ? confirmDelete(this, 'Xóa ngành học này?') : (confirm('Xóa ngành học này?') && this.submit());">
                                <input type="hidden" name="action" value="delete_major"><input type="hidden" name="id" value="<?php echo $m['id']; ?>">
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

<!-- Modal Thêm Ngành -->
<div class="modal fade" id="addMajorModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content border-0 shadow">
        <form method="POST">
            <div class="modal-header bg-brand text-white border-0"><h5 class="modal-title fw-bold">Thêm Ngành Học</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="action" value="add_major">
                <div class="row"><div class="col-4 mb-3"><label class="form-label">Mã ngành</label><input type="text" name="major_code" class="form-control" required></div><div class="col-8 mb-3"><label class="form-label">Tên ngành</label><input type="text" name="major_name" class="form-control" required></div></div>
                <div class="mb-3"><label class="form-label">Thuộc Hệ Đào Tạo</label><select name="education_level_id" class="form-select" required><option value="">-- Chọn hệ --</option><?php foreach($levels as $l): ?><option value="<?php echo $l['id']; ?>"><?php echo htmlspecialchars($l['name']); ?></option><?php endforeach; ?></select></div>
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
                <div class="mb-3"><label class="form-label"><i class="bi bi-chat-dots-fill text-success me-1"></i>Link Zalo</label><input type="url" name="zalo_link" id="edit_major_zalo" class="form-control" placeholder="https://zalo.me/g/..."></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-warning w-100">Cập nhật</button></div>
        </form>
    </div></div>
</div>


<script>
function openEditMajorModal(id, code, name, levelId, zalo) {
    document.getElementById('edit_major_id').value = id; document.getElementById('edit_major_code').value = code;
    document.getElementById('edit_major_name').value = name; document.getElementById('edit_major_level_id').value = levelId;
    document.getElementById('edit_major_zalo').value = zalo || '';
    new bootstrap.Modal(document.getElementById('editMajorModal')).show();
}
</script>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
