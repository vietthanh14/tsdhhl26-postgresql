<?php
require_once __DIR__ . '/includes/admin_init.php';

// --- POST Handlers ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_period') {
        $level_id = $_POST['education_level_id'];
        $major_ids = $_POST['major_ids'] ?? [];
        $data = [
            'name' => $_POST['name'],
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'education_level_id' => $level_id,
            'is_active' => isset($_POST['is_active']) ? true : false
        ];
        $res = $supabaseAdmin->insert('admission_periods', $data);
        if (in_array($res['code'], [201, 200, 204])) {
            $new_period_id = $res['data'][0]['id'] ?? null;
            if ($new_period_id) {
                $majorRows = array_map(fn($m_id) => ['period_id' => $new_period_id, 'major_id' => (int)$m_id], $major_ids);
                if (!empty($majorRows)) $supabaseAdmin->insert('admission_period_majors', $majorRows);
                $methods_matrix = $_POST['methods'] ?? [];
                $methodRows = [];
                foreach ($major_ids as $m_id) {
                    foreach (($methods_matrix[$m_id] ?? []) as $method_id) {
                        $methodRows[] = ['period_id' => $new_period_id, 'major_id' => (int)$m_id, 'method_id' => (int)$method_id];
                    }
                }
                if (!empty($methodRows)) $supabaseAdmin->insert('admission_period_major_methods', $methodRows);
            }
            $_SESSION['msg'] = "Thêm Đợt tuyển sinh và cấu hình Ngành thành công!";
        } else {
            $_SESSION['err'] = "Lỗi thêm đợt tuyển sinh: " . json_encode($res['data']);
        }
        Cache::flush();
        header("Location: manage_periods.php"); exit;
    }
    elseif ($action === 'delete_period') {
        $res = $supabaseAdmin->delete('admission_periods', 'id', $_POST['id']);
        if (in_array($res['code'], [200, 204])) $_SESSION['msg'] = "Xóa đợt thành công!";
        else $_SESSION['err'] = "Lỗi xóa: Phải xóa các hồ sơ liên quan trước.";
        Cache::flush();
        header("Location: manage_periods.php"); exit;
    }
    elseif ($action === 'copy_period') {
        $source_id = $_POST['id'];
        $srcRes = $supabaseAdmin->select('admission_periods', "id=eq.{$source_id}");
        if ($srcRes['code'] == 200 && !empty($srcRes['data'])) {
            $src = $srcRes['data'][0];
            $newRes = $supabaseAdmin->insert('admission_periods', [
                'name' => 'Bản sao - ' . $src['name'], 'start_date' => $src['start_date'],
                'end_date' => $src['end_date'], 'education_level_id' => $src['education_level_id'], 'is_active' => false
            ]);
            if (in_array($newRes['code'], [201, 200, 204]) && !empty($newRes['data'])) {
                $new_id = $newRes['data'][0]['id'];
                $majorsRes = $supabaseAdmin->select('admission_period_majors', "period_id=eq.{$source_id}&select=major_id");
                if ($majorsRes['code'] == 200 && !empty($majorsRes['data'])) {
                    $supabaseAdmin->insert('admission_period_majors', array_map(fn($pm) => ['period_id' => $new_id, 'major_id' => $pm['major_id']], $majorsRes['data']));
                }
                $methodsRes = $supabaseAdmin->select('admission_period_major_methods', "period_id=eq.{$source_id}&select=major_id,method_id");
                if ($methodsRes['code'] == 200 && !empty($methodsRes['data'])) {
                    $supabaseAdmin->insert('admission_period_major_methods', array_map(fn($mm) => ['period_id' => $new_id, 'major_id' => $mm['major_id'], 'method_id' => $mm['method_id']], $methodsRes['data']));
                }
                $_SESSION['msg'] = "Nhân bản đợt tuyển sinh thành công!";
            } else { $_SESSION['err'] = "Lỗi khi tạo bản sao."; }
        } else { $_SESSION['err'] = "Không tìm thấy đợt gốc."; }
        Cache::flush();
        header("Location: manage_periods.php"); exit;
    }
    elseif ($action === 'edit_period') {
        $id = $_POST['id'];
        $major_ids = $_POST['major_ids'] ?? [];
        $data = [
            'name' => $_POST['name'], 'start_date' => $_POST['start_date'], 'end_date' => $_POST['end_date'],
            'education_level_id' => $_POST['education_level_id'], 'is_active' => isset($_POST['is_active']) ? true : false
        ];
        $res = $supabaseAdmin->update('admission_periods', 'id', $id, $data);
        if (in_array($res['code'], [200, 204])) {
            $supabaseAdmin->delete('admission_period_majors', 'period_id', $id);
            $supabaseAdmin->delete('admission_period_major_methods', 'period_id', $id);
            $majorRows = array_map(fn($m_id) => ['period_id' => (int)$id, 'major_id' => (int)$m_id], $major_ids);
            if (!empty($majorRows)) $supabaseAdmin->insert('admission_period_majors', $majorRows);
            $methods_matrix = $_POST['methods'] ?? [];
            $methodRows = [];
            foreach ($major_ids as $m_id) {
                foreach (($methods_matrix[$m_id] ?? []) as $method_id) {
                    $methodRows[] = ['period_id' => (int)$id, 'major_id' => (int)$m_id, 'method_id' => (int)$method_id];
                }
            }
            if (!empty($methodRows)) $supabaseAdmin->insert('admission_period_major_methods', $methodRows);
            $_SESSION['msg'] = "Cập nhật Đợt tuyển sinh thành công!";
        } else { $_SESSION['err'] = "Lỗi cập nhật: " . json_encode($res['data']); }
        Cache::flush();
        header("Location: manage_periods.php"); exit;
    }
    elseif ($action === 'toggle_period') {
        $supabaseAdmin->update('admission_periods', 'id', $_POST['id'], ['is_active' => $_POST['is_active'] === 'true']);
        Cache::flush();
        header("Location: manage_periods.php"); exit;
    }
}

// --- Fetch Data ---
$sqlP = "SELECT ap.*, el.name as education_levels__name FROM admission_periods ap LEFT JOIN education_levels el ON ap.education_level_id = el.id ORDER BY ap.id DESC";
$periodsRes = $supabaseAdmin->rawQuery($sqlP);
$periods = $periodsRes['code'] == 200 ? $periodsRes['data'] : [];

$levelsRes = $supabaseAdmin->select('education_levels', 'order=id.asc');
$levels = $levelsRes['code'] == 200 ? $levelsRes['data'] : [];

$sqlM = "SELECT m.*, el.name as education_levels__name FROM majors m LEFT JOIN education_levels el ON m.education_level_id = el.id ORDER BY m.id DESC";
$majorsRes = $supabaseAdmin->rawQuery($sqlM);
$majors = $majorsRes['code'] == 200 ? $majorsRes['data'] : [];
$periodMajorsRes = $supabaseAdmin->select('admission_period_majors', 'select=period_id,major_id');
$periodMajors = $periodMajorsRes['code'] == 200 ? $periodMajorsRes['data'] : [];
$methodsRes = $supabaseAdmin->select('admission_methods', 'order=id.asc');
$methods = $methodsRes['code'] == 200 ? $methodsRes['data'] : [];
$periodMajorMethodsRes = $supabaseAdmin->select('admission_period_major_methods', 'select=period_id,major_id,method_id');
$periodMajorMethods = $periodMajorMethodsRes['code'] == 200 ? $periodMajorMethodsRes['data'] : [];

$pageTitle = 'Quản lý Đợt Tuyển Sinh';
include __DIR__ . '/includes/admin_header.php';
?>

<div class="d-flex flex-wrap justify-content-end align-items-center mb-3 gap-2">
    <div class="d-flex gap-2 align-items-center">
        <div class="input-group input-group-sm" style="width: 250px;">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
            <input type="text" class="form-control border-start-0" placeholder="Tìm đợt tuyển sinh..." oninput="filterTable(this.value, 'periodsBody')">
        </div>
        <button class="btn btn-sm btn-brand" data-bs-toggle="modal" data-bs-target="#addPeriodModal">+ Mở Đợt Mới</button>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 w-100">
                <thead class="table-light">
                    <tr>
                        <th>Hệ</th>
                        <th>Tên đợt</th>
                        <th>Bắt đầu</th>
                        <th>Kết thúc</th>
                        <th>Trạng thái</th>
                        <th class="text-end">Hành động</th>
                    </tr>
                </thead>
                <tbody id="periodsBody">
                    <?php foreach($periods as $p): ?>
                    <tr>
                        <td><span class="badge bg-secondary"><?php echo htmlspecialchars($p['education_levels']['name'] ?? 'Chưa rõ'); ?></span></td>
                        <td class="fw-semibold text-brand">
                            <?php echo htmlspecialchars($p['name']); ?><br>
                            <small class="text-muted fw-normal"><?php $count = 0; foreach($periodMajors as $pm) { if($pm['period_id'] == $p['id']) $count++; } echo "Gồm {$count} ngành"; ?></small>
                        </td>
                        <td class="small"><?php echo date('d/m/Y', strtotime($p['start_date'])); ?></td>
                        <td class="small"><?php echo date('d/m/Y', strtotime($p['end_date'])); ?></td>
                        <td><?php echo $p['is_active'] ? '<span class="badge bg-success">Đang Mở</span>' : '<span class="badge bg-secondary">Đã Đóng</span>'; ?></td>
                        <td class="text-end">
                            <?php
                                $mIds = []; foreach($periodMajors as $pm) { if($pm['period_id'] == $p['id']) $mIds[] = strval($pm['major_id']); }
                                $mIdsJson = htmlspecialchars(json_encode($mIds), ENT_QUOTES, 'UTF-8');
                            ?>
                            <div class="btn-group shadow-sm">
                                <button class="btn btn-sm btn-outline-warning" onclick="openEditPeriodModal('<?php echo $p['id']; ?>', '<?php echo htmlspecialchars($p['name'], ENT_QUOTES); ?>', '<?php echo $p['education_level_id']; ?>', '<?php echo $p['start_date']; ?>', '<?php echo $p['end_date']; ?>', <?php echo $p['is_active'] ? 'true' : 'false'; ?>, <?php echo $mIdsJson; ?>)" title="Sửa"><i class="bi bi-pencil"></i></button>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="toggle_period">
                                    <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                    <input type="hidden" name="is_active" value="<?php echo $p['is_active'] ? 'false' : 'true'; ?>">
                                    <button class="btn btn-sm btn-outline-brand" title="<?php echo $p['is_active'] ? 'Đóng đợt' : 'Mở đợt'; ?>">
                                        <i class="bi <?php echo $p['is_active'] ? 'bi-lock' : 'bi-unlock'; ?>"></i>
                                    </button>
                                </form>
                                <form method="POST" class="d-inline" onsubmit="event.preventDefault(); confirmCopy(this);">
                                    <input type="hidden" name="action" value="copy_period"><input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-success" title="Copy"><i class="bi bi-copy"></i></button>
                                </form>
                                <form method="POST" class="d-inline" onsubmit="event.preventDefault(); confirmDelete(this, 'Chắc chắn xóa đợt tuyển sinh này?');">
                                    <input type="hidden" name="action" value="delete_period"><input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Xóa"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Thêm Đợt -->
<div class="modal fade" id="addPeriodModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content border-0 shadow">
        <form method="POST">
            <div class="modal-header bg-brand text-white border-0"><h5 class="modal-title fw-bold">Mở Đợt Tuyển Sinh & Chọn Ngành</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="action" value="add_period">
                <div class="mb-3"><label class="form-label">Tên đợt</label><input type="text" name="name" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Thuộc Hệ Đào Tạo</label>
                    <select name="education_level_id" class="form-select" id="periodLevelSelect" required onchange="filterMajorsForPeriod()">
                        <option value="">-- Chọn hệ --</option>
                        <?php foreach($levels as $l): ?><option value="<?php echo $l['id']; ?>"><?php echo htmlspecialchars($l['name']); ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3 d-none" id="periodMajorsContainer">
                    <label class="form-label">Chọn Ngành xét tuyển</label>
                    <div class="major-checkboxes" id="periodMajorsList">
                        <?php foreach($majors as $m): ?>
                        <div class="form-check major-item" data-level="<?php echo $m['education_level_id']; ?>">
                            <input class="form-check-input" type="checkbox" name="major_ids[]" value="<?php echo $m['id']; ?>" id="chk_m_<?php echo $m['id']; ?>" onchange="toggleMethods(this, 'methods_container_<?php echo $m['id']; ?>')">
                            <label class="form-check-label fw-bold text-dark" for="chk_m_<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['major_code'] . ' - ' . $m['major_name']); ?></label>
                            <div class="ms-4 mt-2 mb-3 bg-white p-2 border rounded d-none methods-container" id="methods_container_<?php echo $m['id']; ?>">
                                <small class="text-secondary d-block mb-2 fw-semibold">Phương thức xét tuyển:</small>
                                <?php foreach($methods as $method): ?>
                                <div class="form-check mb-1">
                                    <input class="form-check-input method-chk" type="checkbox" name="methods[<?php echo $m['id']; ?>][]" value="<?php echo $method['id']; ?>" id="chk_m_<?php echo $m['id']; ?>_method_<?php echo $method['id']; ?>">
                                    <label class="form-check-label small" for="chk_m_<?php echo $m['id']; ?>_method_<?php echo $method['id']; ?>"><?php echo htmlspecialchars($method['method_name']); ?></label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6 mb-3"><label class="form-label">Ngày mở</label><input type="date" name="start_date" class="form-control" required></div>
                    <div class="col-6 mb-3"><label class="form-label">Ngày đóng</label><input type="date" name="end_date" class="form-control" required></div>
                </div>
                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" checked id="activeSwitch"><label class="form-check-label" for="activeSwitch">Kích hoạt ngay</label></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-brand w-100">Lưu đợt</button></div>
        </form>
    </div></div>
</div>

<!-- Modal Sửa Đợt -->
<div class="modal fade" id="editPeriodModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content border-0 shadow">
        <form method="POST">
            <div class="modal-header bg-brand text-white border-0"><h5 class="modal-title fw-bold">Sửa Đợt Tuyển Sinh</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="action" value="edit_period"><input type="hidden" name="id" id="edit_period_id">
                <div class="mb-3"><label class="form-label">Tên đợt</label><input type="text" name="name" id="edit_name" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Thuộc Hệ Đào Tạo</label>
                    <select name="education_level_id" class="form-select" id="editPeriodLevelSelect" required onchange="filterEditMajorsForPeriod()">
                        <option value="">-- Chọn hệ --</option>
                        <?php foreach($levels as $l): ?><option value="<?php echo $l['id']; ?>"><?php echo htmlspecialchars($l['name']); ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3" id="editPeriodMajorsContainer">
                    <label class="form-label">Chọn Ngành xét tuyển</label>
                    <div class="major-checkboxes" id="editPeriodMajorsList">
                        <?php foreach($majors as $m): ?>
                        <div class="form-check edit-major-item" data-level="<?php echo $m['education_level_id']; ?>">
                            <input class="form-check-input" type="checkbox" name="major_ids[]" value="<?php echo $m['id']; ?>" id="edit_chk_m_<?php echo $m['id']; ?>" onchange="toggleMethods(this, 'edit_methods_container_<?php echo $m['id']; ?>')">
                            <label class="form-check-label fw-bold text-dark" for="edit_chk_m_<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['major_code'] . ' - ' . $m['major_name']); ?></label>
                            <div class="ms-4 mt-2 mb-3 bg-white p-2 border rounded d-none methods-container" id="edit_methods_container_<?php echo $m['id']; ?>">
                                <small class="text-secondary d-block mb-2 fw-semibold">Phương thức xét tuyển:</small>
                                <?php foreach($methods as $method): ?>
                                <div class="form-check mb-1">
                                    <input class="form-check-input edit-method-chk" type="checkbox" name="methods[<?php echo $m['id']; ?>][]" value="<?php echo $method['id']; ?>" id="edit_chk_m_<?php echo $m['id']; ?>_method_<?php echo $method['id']; ?>">
                                    <label class="form-check-label small" for="edit_chk_m_<?php echo $m['id']; ?>_method_<?php echo $method['id']; ?>"><?php echo htmlspecialchars($method['method_name']); ?></label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6 mb-3"><label class="form-label">Ngày mở</label><input type="date" name="start_date" id="edit_start_date" class="form-control" required></div>
                    <div class="col-6 mb-3"><label class="form-label">Ngày đóng</label><input type="date" name="end_date" id="edit_end_date" class="form-control" required></div>
                </div>
                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" id="editActiveSwitch"><label class="form-check-label" for="editActiveSwitch">Kích hoạt ngay</label></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-warning w-100">Cập nhật</button></div>
        </form>
    </div></div>
</div>

<!-- Modal Confirm Copy -->
<div class="modal fade" id="confirmCopyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow">
        <div class="modal-header bg-success text-white border-0"><h5 class="modal-title fw-bold"><i class="bi bi-copy me-2"></i> Nhân bản Đợt</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body p-4 text-center">
            <i class="bi bi-files text-success mb-3 d-block" style="font-size: 3rem;"></i>
            <p class="mb-1 fs-5 fw-semibold">Xác nhận nhân bản đợt tuyển sinh?</p>
            <p class="text-muted small">Tạo bản sao gồm Ngành + Phương thức. Đợt mới ở trạng thái <strong>Tạm đóng</strong>.</p>
        </div>
        <div class="modal-footer border-0 justify-content-center pb-4">
            <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Hủy</button>
            <button type="button" class="btn btn-success px-4" id="btnConfirmCopy"><i class="bi bi-copy me-1"></i> Nhân bản</button>
        </div>
    </div></div>
</div>

<script>
const allPeriodMajorMethods = <?php echo json_encode($periodMajorMethods); ?>;
let copyFormToSubmit = null;
let confirmCopyModalInstance = null;
document.addEventListener("DOMContentLoaded", function() {
    confirmCopyModalInstance = new bootstrap.Modal(document.getElementById('confirmCopyModal'));
    document.getElementById('btnConfirmCopy').addEventListener('click', function() {
        if (copyFormToSubmit) { copyFormToSubmit.submit(); this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang sao chép...'; this.classList.add('disabled'); }
    });
});
function confirmCopy(f) { copyFormToSubmit = f; confirmCopyModalInstance.show(); }
function toggleMethods(cb, cId) { const c = document.getElementById(cId); if(cb.checked) c.classList.remove('d-none'); else { c.classList.add('d-none'); c.querySelectorAll('input[type="checkbox"]').forEach(x => x.checked = false); } }
function openEditPeriodModal(id, name, levelId, startDate, endDate, isActive, majorIds) {
    document.getElementById('edit_period_id').value = id; document.getElementById('edit_name').value = name;
    document.getElementById('editPeriodLevelSelect').value = levelId; document.getElementById('edit_start_date').value = startDate;
    document.getElementById('edit_end_date').value = endDate; document.getElementById('editActiveSwitch').checked = isActive;
    filterEditMajorsForPeriod();
    document.querySelectorAll('#editPeriodMajorsList > .edit-major-item > input[type="checkbox"]').forEach(chk => { chk.checked = majorIds.includes(chk.value); toggleMethods(chk, 'edit_methods_container_' + chk.value); });
    document.querySelectorAll('.edit-method-chk').forEach(chk => chk.checked = false);
    allPeriodMajorMethods.forEach(m => { if(m.period_id == id) { const c = document.getElementById('edit_chk_m_' + m.major_id + '_method_' + m.method_id); if(c) c.checked = true; } });
    new bootstrap.Modal(document.getElementById('editPeriodModal')).show();
}
function filterEditMajorsForPeriod() { const lv = document.getElementById('editPeriodLevelSelect').value; document.querySelectorAll('.edit-major-item').forEach(i => { i.style.display = i.getAttribute('data-level') === lv ? 'block' : 'none'; }); }
function filterMajorsForPeriod() {
    const lv = document.getElementById('periodLevelSelect').value; const c = document.getElementById('periodMajorsContainer');
    if(!lv) { c.classList.add('d-none'); return; }
    c.classList.remove('d-none'); let has = false;
    document.querySelectorAll('.major-item').forEach(i => { if(i.getAttribute('data-level') === lv) { i.style.display = 'block'; has = true; } else { i.style.display = 'none'; i.querySelector('input').checked = false; } });
    if(!has) { c.classList.add('d-none'); showToast("Hệ đào tạo này chưa có Ngành nào!", "warning"); document.getElementById('periodLevelSelect').value = ''; }
}
</script>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
