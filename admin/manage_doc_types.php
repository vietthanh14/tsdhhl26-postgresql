<?php
require_once __DIR__ . '/includes/admin_init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add_doc_type') {
        $res = $supabaseAdmin->insert('document_types', ['type_name' => trim($_POST['type_name'])]);
        if (in_array($res['code'], [201, 200, 204])) $_SESSION['msg'] = "Thêm danh mục tài liệu thành công!";
        else $_SESSION['err'] = "Lỗi thêm danh mục (có thể trùng tên): " . json_encode($res['data']);
        Cache::flush(); header("Location: manage_doc_types.php"); exit;
    }
    elseif ($action === 'edit_doc_type') {
        $res = $supabaseAdmin->update('document_types', 'id', $_POST['id'], ['type_name' => trim($_POST['type_name'])]);
        if (in_array($res['code'], [200, 204])) $_SESSION['msg'] = "Cập nhật danh mục thành công!";
        else $_SESSION['err'] = "Lỗi cập nhật: " . json_encode($res['data']);
        Cache::flush(); header("Location: manage_doc_types.php"); exit;
    }
    elseif ($action === 'delete_doc_type') {
        $res = $supabaseAdmin->delete('document_types', 'id', $_POST['id']);
        if (in_array($res['code'], [200, 204])) $_SESSION['msg'] = "Xóa danh mục thành công!";
        else $_SESSION['err'] = "Lỗi: Không thể xóa danh mục đang có tài liệu liên kết.";
        Cache::flush(); header("Location: manage_doc_types.php"); exit;
    }
}

$typesRes = $supabaseAdmin->select('document_types', 'order=id.asc');
$docTypes = $typesRes['code'] == 200 ? $typesRes['data'] : [];

$pageTitle = 'Cấu hình Danh mục Tài liệu';
include __DIR__ . '/includes/admin_header.php';
?>

<div class="d-flex flex-wrap justify-content-end align-items-center mb-3 gap-2">
    <div class="d-flex gap-2 align-items-center">
        <div class="input-group input-group-sm" style="width: 250px;">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
            <input type="text" class="form-control border-start-0" placeholder="Tìm danh mục..." oninput="filterTable(this.value, 'docTypesBody')">
        </div>
        <button class="btn btn-sm btn-brand" data-bs-toggle="modal" data-bs-target="#addDocTypeModal">+ Thêm Danh mục</button>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 w-100">
                <thead class="table-light">
                    <tr>
                        <th style="width:80px;">ID</th>
                        <th>Tên Danh mục Tài liệu</th>
                        <th class="text-end" style="width:200px;">Hành động</th>
                    </tr>
                </thead>
                <tbody id="docTypesBody">
                    <?php if(empty($docTypes)): ?>
                    <tr><td colspan="3" class="text-center text-muted py-4"><i class="bi bi-folder2-open d-block mb-2" style="font-size:2rem;"></i>Chưa có danh mục tài liệu nào</td></tr>
                    <?php else: ?>
                    <?php foreach($docTypes as $dt): ?>
                    <tr>
                        <td><span class="badge bg-light text-dark border">#<?php echo $dt['id']; ?></span></td>
                        <td class="fw-semibold text-brand"><?php echo htmlspecialchars($dt['type_name']); ?></td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-warning me-1" onclick="openEditDocTypeModal('<?php echo $dt['id']; ?>', '<?php echo htmlspecialchars($dt['type_name'], ENT_QUOTES); ?>')" title="Sửa"><i class="bi bi-pencil"></i></button>
                            <form method="POST" class="d-inline" onsubmit="event.preventDefault(); window.confirmDelete ? confirmDelete(this, 'Xóa danh mục tài liệu &quot;<?php echo htmlspecialchars($dt['type_name'], ENT_QUOTES); ?>&quot;?') : (confirm('Xóa danh mục tài liệu này?') && this.submit());">
                                <input type="hidden" name="action" value="delete_doc_type"><input type="hidden" name="id" value="<?php echo $dt['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Xóa"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">
    <div class="alert alert-info border-0 shadow-sm small mb-0">
        <i class="bi bi-info-circle me-2"></i>
        Danh mục tài liệu dùng để phân loại các file mà thí sinh tải lên (VD: Ảnh 3x4, CMND/CCCD, Bằng tốt nghiệp, Học bạ...). 
        Thí sinh sẽ chọn danh mục trước khi upload file.
    </div>
</div>

<!-- Modal Thêm Danh mục -->
<div class="modal fade" id="addDocTypeModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content border-0 shadow">
        <form method="POST">
            <div class="modal-header bg-brand text-white border-0"><h5 class="modal-title fw-bold">Thêm Danh mục Tài liệu</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="action" value="add_doc_type">
                <div class="mb-3">
                    <label class="form-label">Tên danh mục <span class="text-danger">*</span></label>
                    <input type="text" name="type_name" class="form-control" required placeholder="VD: Ảnh chân dung 3x4, CCCD mặt trước...">
                </div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-brand w-100">Lưu danh mục</button></div>
        </form>
    </div></div>
</div>

<!-- Modal Sửa Danh mục -->
<div class="modal fade" id="editDocTypeModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content border-0 shadow">
        <form method="POST">
            <div class="modal-header bg-brand text-white border-0"><h5 class="modal-title fw-bold">Sửa Danh mục Tài liệu</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="action" value="edit_doc_type">
                <input type="hidden" name="id" id="edit_doc_type_id">
                <div class="mb-3">
                    <label class="form-label">Tên danh mục <span class="text-danger">*</span></label>
                    <input type="text" name="type_name" id="edit_doc_type_name" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-warning w-100">Cập nhật</button></div>
        </form>
    </div></div>
</div>

<script>
function openEditDocTypeModal(id, name) {
    document.getElementById('edit_doc_type_id').value = id;
    document.getElementById('edit_doc_type_name').value = name;
    new bootstrap.Modal(document.getElementById('editDocTypeModal')).show();
}
</script>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
