<?php
require_once __DIR__ . '/../config/supabase.php';

// admin/admission_settings.php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ' . BASE_URL . '/admin/login.php');
    exit;
}
require_once __DIR__ . '/../lib/SupabaseClient.php';
require_once __DIR__ . '/../lib/Cache.php';
$supabaseAdmin = new SupabaseClient('service');
$message = $_SESSION['msg'] ?? '';
$error = $_SESSION['err'] ?? '';
unset($_SESSION['msg'], $_SESSION['err']);

// --- Xử lý POST (Thêm/Sửa/Xóa) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // 1. Quản lý Đợt Tuyển Sinh
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
            // Lấy ID đợt vừa tạo trực tiếp từ response (Prefer: return=representation)
            $new_period_id = $res['data'][0]['id'] ?? null;
            if ($new_period_id) {

                $methods_matrix = $_POST['methods'] ?? [];
                foreach ($major_ids as $m_id) {
                    $supabaseAdmin->insert('admission_period_majors', ['period_id' => $new_period_id, 'major_id' => $m_id]);
                    
                    if (!empty($methods_matrix[$m_id])) {
                        foreach ($methods_matrix[$m_id] as $method_id) {
                            $supabaseAdmin->insert('admission_period_major_methods', [
                                'period_id' => $new_period_id,
                                'major_id' => $m_id,
                                'method_id' => $method_id
                            ]);
                        }
                    }
                }
            }
            $_SESSION['msg'] = "Thêm Đợt tuyển sinh và cấu hình Ngành thành công!";
        }
        else $_SESSION['err'] = "Lỗi thêm đợt tuyển sinh: " . json_encode($res['data']);
        Cache::flush();
        header("Location: admission_settings.php"); exit;
    } 
    elseif ($action === 'delete_period') {
        $res = $supabaseAdmin->delete('admission_periods', 'id', $_POST['id']);
        if (in_array($res['code'], [200, 204])) $_SESSION['msg'] = "Xóa đợt thành công!";
        else $_SESSION['err'] = "Lỗi xóa: Phải xóa các hồ sơ liên quan trước (Ràng buộc dữ liệu).";
        Cache::flush();
        header("Location: admission_settings.php"); exit;
    }
    elseif ($action === 'edit_period') {
        $id = $_POST['id'];
        $level_id = $_POST['education_level_id'];
        $major_ids = $_POST['major_ids'] ?? [];
        
        $data = [
            'name' => $_POST['name'],
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'education_level_id' => $level_id,
            'is_active' => isset($_POST['is_active']) ? true : false
        ];
        
        $res = $supabaseAdmin->update('admission_periods', 'id', $id, $data);
        if (in_array($res['code'], [200, 204])) {
            // Cập nhật majors (B1: Xóa hết mapping cũ của period này)
            $supabaseAdmin->delete('admission_period_majors', 'period_id', $id);
            // Xóa luôn mapping methods (do ON DELETE CASCADE ở DB sẽ tự xóa, nhưng gọi cho chắc chắn nếu chưa cascade)
            $supabaseAdmin->delete('admission_period_major_methods', 'period_id', $id);
            
            // B2: Insert mapping mới
            $methods_matrix = $_POST['methods'] ?? [];
            foreach ($major_ids as $m_id) {
                $supabaseAdmin->insert('admission_period_majors', ['period_id' => $id, 'major_id' => $m_id]);
                
                if (!empty($methods_matrix[$m_id])) {
                    foreach ($methods_matrix[$m_id] as $method_id) {
                        $supabaseAdmin->insert('admission_period_major_methods', [
                            'period_id' => $id,
                            'major_id' => $m_id,
                            'method_id' => $method_id
                        ]);
                    }
                }
            }
            $_SESSION['msg'] = "Cập nhật Đợt tuyển sinh và cấu hình Ngành thành công!";
        } else {
            $_SESSION['err'] = "Lỗi cập nhật đợt tuyển sinh: " . json_encode($res['data']);
        }
        Cache::flush();
        header("Location: admission_settings.php"); exit;
    }
    elseif ($action === 'toggle_period') {
        $res = $supabaseAdmin->update('admission_periods', 'id', $_POST['id'], ['is_active' => $_POST['is_active'] === 'true']);
        Cache::flush();
        header("Location: admission_settings.php"); exit;
    }
    
    // 2. Quản lý Hệ Đào Tạo
    elseif ($action === 'add_level') {
        $res = $supabaseAdmin->insert('education_levels', ['name' => $_POST['name'], 'description' => $_POST['description']]);
        if (in_array($res['code'], [201, 200, 204])) $_SESSION['msg'] = "Thêm Hệ đào tạo thành công!";
        else $_SESSION['err'] = "Lỗi thêm hệ đào tạo (có thể trùng tên): " . json_encode($res['data']);
        Cache::flush();
        header("Location: admission_settings.php"); exit;
    }
    elseif ($action === 'edit_level') {
        $res = $supabaseAdmin->update('education_levels', 'id', $_POST['id'], ['name' => $_POST['name'], 'description' => $_POST['description']]);
        if (in_array($res['code'], [200, 204])) $_SESSION['msg'] = "Cập nhật Hệ đào tạo thành công!";
        else $_SESSION['err'] = "Lỗi sửa hệ đào tạo: " . json_encode($res['data']);
        Cache::flush();
        header("Location: admission_settings.php"); exit;
    }
    elseif ($action === 'delete_level') {
        $res = $supabaseAdmin->delete('education_levels', 'id', $_POST['id']);
        if (in_array($res['code'], [200, 204])) $_SESSION['msg'] = "Xóa Hệ đào tạo thành công!";
        else $_SESSION['err'] = "Lỗi: Không thể xóa Hệ đang có chứa ngành học.";
        Cache::flush();
        header("Location: admission_settings.php"); exit;
    }
    
    // 3. Quản lý Ngành Học
    elseif ($action === 'add_major') {
        $data = [
            'major_code' => $_POST['major_code'],
            'major_name' => $_POST['major_name'],
            'education_level_id' => $_POST['education_level_id'],
            'application_fee' => $_POST['application_fee'],
            'zalo_link' => trim($_POST['zalo_link'] ?? '')
        ];
        $res = $supabaseAdmin->insert('majors', $data);
        if (in_array($res['code'], [201, 200, 204])) $_SESSION['msg'] = "Thêm Ngành học thành công!";
        else $_SESSION['err'] = "Lỗi thêm ngành (có thể trùng mã): " . json_encode($res['data']);
        Cache::flush();
        header("Location: admission_settings.php"); exit;
    }
    elseif ($action === 'edit_major') {
        $data = [
            'major_code' => $_POST['major_code'],
            'major_name' => $_POST['major_name'],
            'education_level_id' => $_POST['education_level_id'],
            'application_fee' => $_POST['application_fee'],
            'zalo_link' => trim($_POST['zalo_link'] ?? '')
        ];
        $res = $supabaseAdmin->update('majors', 'id', $_POST['id'], $data);
        if (in_array($res['code'], [200, 204])) $_SESSION['msg'] = "Cập nhật Ngành học thành công!";
        else $_SESSION['err'] = "Lỗi sửa ngành học: " . json_encode($res['data']);
        Cache::flush();
        header("Location: admission_settings.php"); exit;
    }
    elseif ($action === 'delete_major') {
        $res = $supabaseAdmin->delete('majors', 'id', $_POST['id']);
        if (in_array($res['code'], [200, 204])) $_SESSION['msg'] = "Xóa Ngành thành công!";
        else $_SESSION['err'] = "Lỗi: Không thể xóa ngành đã có hồ sơ nộp vào.";
        Cache::flush();
        header("Location: admission_settings.php"); exit;
    }
}

// --- Fetch Dữ Liệu ---
$periodsRes = $supabaseAdmin->select('admission_periods', 'select=*,education_levels(name)&order=id.desc');
$periods = $periodsRes['code'] == 200 ? $periodsRes['data'] : [];

$levelsRes = $supabaseAdmin->select('education_levels', 'order=id.asc');
$levels = $levelsRes['code'] == 200 ? $levelsRes['data'] : [];

$majorsRes = $supabaseAdmin->select('majors', 'select=*,education_levels(name)&order=id.desc');
$majors = $majorsRes['code'] == 200 ? $majorsRes['data'] : [];

// Fetch mappings to show UI later if needed
$periodMajorsRes = $supabaseAdmin->select('admission_period_majors', 'select=period_id,major_id');
$periodMajors = $periodMajorsRes['code'] == 200 ? $periodMajorsRes['data'] : [];

$methodsRes = $supabaseAdmin->select('admission_methods', 'order=id.asc');
$methods = $methodsRes['code'] == 200 ? $methodsRes['data'] : [];

$periodMajorMethodsRes = $supabaseAdmin->select('admission_period_major_methods', 'select=period_id,major_id,method_id');
$periodMajorMethods = $periodMajorMethodsRes['code'] == 200 ? $periodMajorMethodsRes['data'] : [];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cấu Hình Tuyển Sinh - Admin HALOU</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/public.css">
    <style>
        .major-checkboxes { max-height: 250px; overflow-y: auto; background: #f8fafc; padding: 10px; border: 1px solid #dee2e6; border-radius: 4px; }
        .nav-tabs .nav-link.active { color: var(--brand) !important; border-bottom: 3px solid var(--brand) !important; }
        .btn-brand { background-color: var(--brand, #1A3A6E); color: white; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="container-fluid p-0">
    <div class="row m-0">
        <!-- Sidebar -->
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold mb-0 text-brand">Cấu Hình Thông Số Tuyển Sinh</h3>
            </div>
            
            <?php if($message): ?><div class="alert alert-success border-0 shadow-sm"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
            <?php if($error): ?><div class="alert alert-danger border-0 shadow-sm"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

            <ul class="nav nav-tabs mb-4 border-0 border-bottom" id="settingsTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active px-4 py-3 border-0 bg-transparent text-muted fw-bold" data-bs-toggle="tab" data-bs-target="#periods">Đợt Tuyển Sinh</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link px-4 py-3 border-0 bg-transparent text-muted fw-bold" data-bs-toggle="tab" data-bs-target="#levels">Hệ Đào Tạo</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link px-4 py-3 border-0 bg-transparent text-muted fw-bold" data-bs-toggle="tab" data-bs-target="#majors">Ngành Học & Lệ Phí</button>
                </li>
            </ul>

            <div class="tab-content bg-white p-4 rounded-3 shadow-sm border-0 mb-5">
                <!-- TAB ĐỢT TUYỂN SINH -->
                <div class="tab-pane fade show active" id="periods">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold m-0">Danh sách Đợt</h5>
                        <button class="btn btn-sm btn-brand" data-bs-toggle="modal" data-bs-target="#addPeriodModal">+ Mở Đợt Mới</button>
                    </div>
                    <table class="table table-hover align-middle">
                        <thead class="table-light"><tr><th>Hệ</th><th>Tên đợt</th><th>Bắt đầu</th><th>Kết thúc</th><th>Trạng thái</th><th>Hành động</th></tr></thead>
                        <tbody>
                            <?php foreach($periods as $p): ?>
                            <tr>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($p['education_levels']['name'] ?? 'Chưa rõ'); ?></span></td>
                                <td class="fw-semibold">
                                    <?php echo htmlspecialchars($p['name']); ?><br>
                                    <small class="text-muted">
                                        <?php 
                                        $count = 0; 
                                        foreach($periodMajors as $pm) { if($pm['period_id'] == $p['id']) $count++; }
                                        echo "Gồm {$count} ngành";
                                        ?>
                                    </small>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($p['start_date'])); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($p['end_date'])); ?></td>
                                <td>
                                    <?php if($p['is_active']): ?>
                                        <span class="badge bg-success">Đang Mở</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Đã Đóng</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                        $mIds = [];
                                        foreach($periodMajors as $pm) { if($pm['period_id'] == $p['id']) $mIds[] = strval($pm['major_id']); }
                                        $mIdsJson = htmlspecialchars(json_encode($mIds), ENT_QUOTES, 'UTF-8');
                                    ?>
                                    <button class="btn btn-sm btn-outline-warning" onclick="openEditPeriodModal('<?php echo $p['id']; ?>', '<?php echo htmlspecialchars($p['name'], ENT_QUOTES); ?>', '<?php echo $p['education_level_id']; ?>', '<?php echo $p['start_date']; ?>', '<?php echo $p['end_date']; ?>', <?php echo $p['is_active'] ? 'true' : 'false'; ?>, <?php echo $mIdsJson; ?>)">Sửa</button>

                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="toggle_period"><input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                        <input type="hidden" name="is_active" value="<?php echo $p['is_active'] ? 'false' : 'true'; ?>">
                                        <button class="btn btn-sm btn-outline-brand"><?php echo $p['is_active'] ? 'Đóng' : 'Mở'; ?></button>
                                    </form>
                                    <form method="POST" class="d-inline border-0 p-0" onsubmit="event.preventDefault(); confirmDelete(this, 'Chắc chắn xóa đợt tuyển sinh này?');">
                                        <input type="hidden" name="action" value="delete_period"><input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Xóa</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- TAB HỆ ĐÀO TẠO -->
                <div class="tab-pane fade" id="levels">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold m-0">Các Hệ Đào Tạo</h5>
                        <button class="btn btn-sm btn-brand" data-bs-toggle="modal" data-bs-target="#addLevelModal">+ Thêm Hệ</button>
                    </div>
                    <table class="table table-hover align-middle">
                        <thead class="table-light"><tr><th>ID</th><th>Tên Hệ Đào Tạo</th><th>Mô tả</th><th>Hành động</th></tr></thead>
                        <tbody>
                            <?php foreach($levels as $l): ?>
                            <tr>
                                <td><?php echo $l['id']; ?></td>
                                <td class="fw-semibold"><?php echo htmlspecialchars($l['name']); ?></td>
                                <td><?php echo htmlspecialchars($l['description'] ?? ''); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-warning" onclick="openEditLevelModal('<?php echo $l['id']; ?>', '<?php echo htmlspecialchars($l['name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($l['description'] ?? '', ENT_QUOTES); ?>')">Sửa</button>
                                    <form method="POST" class="d-inline border-0 p-0" onsubmit="event.preventDefault(); confirmDelete(this, 'Xóa hệ đào tạo này?');">
                                        <input type="hidden" name="action" value="delete_level"><input type="hidden" name="id" value="<?php echo $l['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Xóa</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- TAB NGÀNH HỌC -->
                <div class="tab-pane fade" id="majors">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold m-0">Danh sách Ngành Đăng Ký</h5>
                        <button class="btn btn-sm btn-brand" data-bs-toggle="modal" data-bs-target="#addMajorModal">+ Thêm Ngành</button>
                    </div>
                    <table class="table table-hover align-middle">
                        <thead class="table-light"><tr><th>Mã Ngành</th><th>Tên Ngành</th><th>Hệ Đào Tạo</th><th>Lệ phí nộp (VND)</th><th>Nhóm Zalo</th><th>Hành động</th></tr></thead>
                        <tbody>
                            <?php foreach($majors as $m): ?>
                            <tr>
                                <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($m['major_code']); ?></span></td>
                                <td class="fw-bold text-dark"><?php echo htmlspecialchars($m['major_name']); ?></td>
                                <td><span class="badge bg-info text-white"><?php echo htmlspecialchars($m['education_levels']['name'] ?? 'N/A'); ?></span></td>
                                <td class="text-danger fw-semibold"><?php echo number_format($m['application_fee'], 0, ',', '.'); ?> đ</td>
                                <td>
                                    <?php if (!empty($m['zalo_link'])): ?>
                                        <a href="<?php echo htmlspecialchars($m['zalo_link']); ?>" target="_blank" class="btn btn-sm btn-outline-success rounded-pill px-3">
                                            <i class="bi bi-chat-dots-fill"></i> Tham gia
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">Chưa có</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-warning" onclick="openEditMajorModal('<?php echo $m['id']; ?>', '<?php echo htmlspecialchars($m['major_code'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($m['major_name'], ENT_QUOTES); ?>', '<?php echo $m['education_level_id']; ?>', '<?php echo $m['application_fee']; ?>', '<?php echo htmlspecialchars($m['zalo_link'] ?? '', ENT_QUOTES); ?>')">Sửa</button>
                                    <form method="POST" class="d-inline border-0 p-0" onsubmit="event.preventDefault(); confirmDelete(this, 'Bạn có chắc chắn muốn xóa ngành học này?');">
                                        <input type="hidden" name="action" value="delete_major"><input type="hidden" name="id" value="<?php echo $m['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Xóa</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Modal Thêm Đợt -->
<div class="modal fade" id="addPeriodModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <form method="POST">
            <div class="modal-header"><h5 class="modal-title fw-bold">Mở Đợt Tuyển Sinh & Chọn Ngành Áp Dụng</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="action" value="add_period">
                <div class="mb-3"><label class="form-label">Tên đợt (VD: Đợt xét tuyển đợt 2 - 2026)</label><input type="text" name="name" class="form-control" required></div>
                
                <div class="mb-3"><label class="form-label">Thuộc Hệ Đào Tạo</label>
                    <select name="education_level_id" class="form-select" id="periodLevelSelect" required onchange="filterMajorsForPeriod()">
                        <option value="">-- Chọn hệ đào tạo trước --</option>
                        <?php foreach($levels as $l): ?><option value="<?php echo $l['id']; ?>"><?php echo htmlspecialchars($l['name']); ?></option><?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-3 d-none" id="periodMajorsContainer">
                    <label class="form-label">Chọn các Ngành được phép xét tuyển trong đợt này</label>
                    <div class="major-checkboxes" id="periodMajorsList" style="max-height: 250px;">
                        <?php foreach($majors as $m): ?>
                            <div class="form-check major-item" data-level="<?php echo $m['education_level_id']; ?>">
                                <input class="form-check-input" type="checkbox" name="major_ids[]" value="<?php echo $m['id']; ?>" id="chk_m_<?php echo $m['id']; ?>" onchange="toggleMethods(this, 'methods_container_<?php echo $m['id']; ?>')">
                                <label class="form-check-label fw-bold text-dark" for="chk_m_<?php echo $m['id']; ?>">
                                    <?php echo htmlspecialchars($m['major_code'] . ' - ' . $m['major_name']); ?>
                                </label>
                                <!-- Sub-checkboxes for methods -->
                                <div class="ms-4 mt-2 mb-3 bg-white p-2 border rounded d-none methods-container" id="methods_container_<?php echo $m['id']; ?>">
                                    <small class="text-secondary d-block mb-2 fw-semibold">Chọn các Phương thức xét tuyển cho ngành này:</small>
                                    <?php foreach($methods as $method): ?>
                                        <div class="form-check mb-1">
                                            <input class="form-check-input method-chk" type="checkbox" name="methods[<?php echo $m['id']; ?>][]" value="<?php echo $method['id']; ?>" id="chk_m_<?php echo $m['id']; ?>_method_<?php echo $method['id']; ?>">
                                            <label class="form-check-label small" for="chk_m_<?php echo $m['id']; ?>_method_<?php echo $method['id']; ?>">
                                                <?php echo htmlspecialchars($method['method_name']); ?>
                                            </label>
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
                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" checked id="activeSwitch"><label class="form-check-label" for="activeSwitch">Kích hoạt mở đăng ký ngay</label></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-brand w-100">Lưu đợt và cấu hình ngành</button></div>
        </form>
    </div></div>
</div>

<!-- Modal Thêm Hệ -->
<div class="modal fade" id="addLevelModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <form method="POST">
            <div class="modal-header"><h5 class="modal-title fw-bold">Thêm Hệ Đào Tạo</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="action" value="add_level">
                <div class="mb-3"><label class="form-label">Tên hệ (VD: Cao học, Liên thông)</label><input type="text" name="name" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Mô tả ngắn</label><input type="text" name="description" class="form-control"></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-brand w-100">Lưu hệ đào tạo</button></div>
        </form>
    </div></div>
</div>

<!-- Modal Thêm Ngành -->
<div class="modal fade" id="addMajorModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <form method="POST">
            <div class="modal-header"><h5 class="modal-title fw-bold">Thêm Ngành Học</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="action" value="add_major">
                <div class="row">
                    <div class="col-4 mb-3"><label class="form-label">Mã ngành</label><input type="text" name="major_code" class="form-control" required></div>
                    <div class="col-8 mb-3"><label class="form-label">Tên ngành</label><input type="text" name="major_name" class="form-control" required></div>
                </div>
                <div class="mb-3"><label class="form-label">Thuộc Hệ Đào Tạo</label>
                    <select name="education_level_id" class="form-select" required>
                        <option value="">-- Chọn hệ --</option>
                        <?php foreach($levels as $l): ?><option value="<?php echo $l['id']; ?>"><?php echo htmlspecialchars($l['name']); ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3"><label class="form-label">Lệ phí nộp hồ sơ (VNĐ)</label><input type="number" name="application_fee" class="form-control" required value="300000" min="0"></div>
                <div class="mb-3"><label class="form-label"><i class="bi bi-chat-dots-fill text-success me-1"></i>Link nhóm Zalo (không bắt buộc)</label><input type="url" name="zalo_link" class="form-control" placeholder="https://zalo.me/g/..."></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-brand w-100">Lưu ngành học</button></div>
        </form>
    </div></div>
</div>

<!-- Modal Sửa Đợt -->
<div class="modal fade" id="editPeriodModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <form method="POST">
            <div class="modal-header"><h5 class="modal-title fw-bold">Sửa Đợt Tuyển Sinh</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="action" value="edit_period">
                <input type="hidden" name="id" id="edit_period_id">
                <div class="mb-3"><label class="form-label">Tên đợt (VD: Đợt xét tuyển đợt 2 - 2026)</label><input type="text" name="name" id="edit_name" class="form-control" required></div>
                
                <div class="mb-3"><label class="form-label">Thuộc Hệ Đào Tạo</label>
                    <select name="education_level_id" class="form-select" id="editPeriodLevelSelect" required onchange="filterEditMajorsForPeriod()">
                        <option value="">-- Chọn hệ đào tạo --</option>
                        <?php foreach($levels as $l): ?><option value="<?php echo $l['id']; ?>"><?php echo htmlspecialchars($l['name']); ?></option><?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-3" id="editPeriodMajorsContainer">
                    <label class="form-label">Chọn các Ngành xét tuyển được phép</label>
                    <div class="major-checkboxes" id="editPeriodMajorsList" style="max-height: 250px;">
                        <?php foreach($majors as $m): ?>
                            <div class="form-check edit-major-item" data-level="<?php echo $m['education_level_id']; ?>">
                                <input class="form-check-input" type="checkbox" name="major_ids[]" value="<?php echo $m['id']; ?>" id="edit_chk_m_<?php echo $m['id']; ?>" onchange="toggleMethods(this, 'edit_methods_container_<?php echo $m['id']; ?>')">
                                <label class="form-check-label fw-bold text-dark" for="edit_chk_m_<?php echo $m['id']; ?>">
                                    <?php echo htmlspecialchars($m['major_code'] . ' - ' . $m['major_name']); ?>
                                </label>
                                <!-- Sub-checkboxes for methods -->
                                <div class="ms-4 mt-2 mb-3 bg-white p-2 border rounded d-none methods-container" id="edit_methods_container_<?php echo $m['id']; ?>">
                                    <small class="text-secondary d-block mb-2 fw-semibold">Chọn các Phương thức xét tuyển cho ngành này:</small>
                                    <?php foreach($methods as $method): ?>
                                        <div class="form-check mb-1">
                                            <input class="form-check-input edit-method-chk" type="checkbox" name="methods[<?php echo $m['id']; ?>][]" value="<?php echo $method['id']; ?>" id="edit_chk_m_<?php echo $m['id']; ?>_method_<?php echo $method['id']; ?>">
                                            <label class="form-check-label small" for="edit_chk_m_<?php echo $m['id']; ?>_method_<?php echo $method['id']; ?>">
                                                <?php echo htmlspecialchars($method['method_name']); ?>
                                            </label>
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
                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" id="editActiveSwitch"><label class="form-check-label" for="editActiveSwitch">Kích hoạt mở đăng ký ngay</label></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-warning w-100">Bấm Cập nhật thông tin</button></div>
        </form>
    </div></div>
</div>

<!-- Modal Sửa Hệ -->
<div class="modal fade" id="editLevelModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <form method="POST">
            <div class="modal-header"><h5 class="modal-title fw-bold">Sửa Hệ Đào Tạo</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="action" value="edit_level">
                <input type="hidden" name="id" id="edit_level_id">
                <div class="mb-3"><label class="form-label">Tên hệ (VD: Cao học, Liên thông)</label><input type="text" name="name" id="edit_level_name" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Mô tả ngắn</label><input type="text" name="description" id="edit_level_description" class="form-control"></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-warning w-100">Cập nhật hệ đào tạo</button></div>
        </form>
    </div></div>
</div>

<!-- Modal Sửa Ngành -->
<div class="modal fade" id="editMajorModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <form method="POST">
            <div class="modal-header"><h5 class="modal-title fw-bold">Sửa Ngành Học</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="action" value="edit_major">
                <input type="hidden" name="id" id="edit_major_id">
                <div class="row">
                    <div class="col-4 mb-3"><label class="form-label">Mã ngành</label><input type="text" name="major_code" id="edit_major_code" class="form-control" required></div>
                    <div class="col-8 mb-3"><label class="form-label">Tên ngành</label><input type="text" name="major_name" id="edit_major_name" class="form-control" required></div>
                </div>
                <div class="mb-3"><label class="form-label">Thuộc Hệ Đào Tạo</label>
                    <select name="education_level_id" id="edit_major_level_id" class="form-select" required>
                        <option value="">-- Chọn hệ --</option>
                        <?php foreach($levels as $l): ?><option value="<?php echo $l['id']; ?>"><?php echo htmlspecialchars($l['name']); ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3"><label class="form-label">Lệ phí nộp hồ sơ (VNĐ)</label><input type="number" name="application_fee" id="edit_major_fee" class="form-control" required min="0"></div>
                <div class="mb-3"><label class="form-label"><i class="bi bi-chat-dots-fill text-success me-1"></i>Link nhóm Zalo (không bắt buộc)</label><input type="url" name="zalo_link" id="edit_major_zalo" class="form-control" placeholder="https://zalo.me/g/..."></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-warning w-100">Cập nhật ngành học</button></div>
        </form>
    </div></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Modal Confirm Delete (Global) -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-octagon me-2"></i> Xác nhận Xóa</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <i class="bi bi-trash text-danger mb-3 d-block" style="font-size: 3rem;"></i>
                <p class="mb-0 fs-5" id="confirmDeleteMsg">Bạn có chắc chắn muốn xóa mục này?</p>
                <p class="text-muted small mt-2">Hành động này không thể hoàn tác.</p>
            </div>
            <div class="modal-footer border-0 justify-content-center pb-4">
                <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Hủy bỏ</button>
                <button type="button" class="btn btn-danger px-4" id="btnConfirmDelete">Xóa</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    let deleteFormToSubmit = null;
    let confirmDeleteModalInstance = null;
    
    document.addEventListener("DOMContentLoaded", function() {
        confirmDeleteModalInstance = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
        
        document.getElementById('btnConfirmDelete').addEventListener('click', function() {
            if (deleteFormToSubmit) {
                deleteFormToSubmit.submit();
                this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Đang xóa...';
                this.classList.add('disabled');
            }
        });
    });

    function confirmDelete(formElement, message) {
        deleteFormToSubmit = formElement;
        document.getElementById('confirmDeleteMsg').innerText = message;
        confirmDeleteModalInstance.show();
    }

function openEditLevelModal(id, name, description) {
    document.getElementById('edit_level_id').value = id;
    document.getElementById('edit_level_name').value = name;
    document.getElementById('edit_level_description').value = description;
    new bootstrap.Modal(document.getElementById('editLevelModal')).show();
}

function openEditMajorModal(id, code, name, levelId, fee, zalo) {
    document.getElementById('edit_major_id').value = id;
    document.getElementById('edit_major_code').value = code;
    document.getElementById('edit_major_name').value = name;
    document.getElementById('edit_major_level_id').value = levelId;
    document.getElementById('edit_major_fee').value = fee;
    document.getElementById('edit_major_zalo').value = zalo || '';
    new bootstrap.Modal(document.getElementById('editMajorModal')).show();
}
const allPeriodMajorMethods = <?php echo json_encode($periodMajorMethods); ?>;

function toggleMethods(checkbox, containerId) {
    const container = document.getElementById(containerId);
    if(checkbox.checked) {
        container.classList.remove('d-none');
    } else {
        container.classList.add('d-none');
        // Uncheck all methods inside if major is unchecked
        container.querySelectorAll('input[type="checkbox"]').forEach(chk => chk.checked = false);
    }
}

function openEditPeriodModal(id, name, levelId, startDate, endDate, isActive, majorIds) {
    document.getElementById('edit_period_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('editPeriodLevelSelect').value = levelId;
    document.getElementById('edit_start_date').value = startDate;
    document.getElementById('edit_end_date').value = endDate;
    document.getElementById('editActiveSwitch').checked = isActive;
    
    filterEditMajorsForPeriod();
    
    // Check included majors AND trigger toggleMethods
    const checkboxes = document.querySelectorAll('#editPeriodMajorsList > .edit-major-item > input[type="checkbox"]');
    checkboxes.forEach(chk => {
        chk.checked = majorIds.includes(chk.value);
        toggleMethods(chk, 'edit_methods_container_' + chk.value);
    });

    // Check included methods
    // First, uncheck all edit methods
    document.querySelectorAll('.edit-method-chk').forEach(chk => chk.checked = false);
    // Then check only valid ones
    allPeriodMajorMethods.forEach(mapping => {
        if(mapping.period_id == id) {
            const chkId = 'edit_chk_m_' + mapping.major_id + '_method_' + mapping.method_id;
            const chk = document.getElementById(chkId);
            if(chk) chk.checked = true;
        }
    });
    
    new bootstrap.Modal(document.getElementById('editPeriodModal')).show();
}

function filterEditMajorsForPeriod() {
    const levelId = document.getElementById('editPeriodLevelSelect').value;
    const items = document.querySelectorAll('.edit-major-item');
    items.forEach(item => {
        if(item.getAttribute('data-level') === levelId) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
            // Không uncheck ở đây để không làm mất lựa chọn hiện tại nếu họ click nhầm
        }
    });
}
function filterMajorsForPeriod() {
    const levelId = document.getElementById('periodLevelSelect').value;
    const container = document.getElementById('periodMajorsContainer');
    const items = document.querySelectorAll('.major-item');
    
    if(!levelId) {
        container.classList.add('d-none');
        return;
    }
    
    container.classList.remove('d-none');
    let hasMajors = false;
    items.forEach(item => {
        if(item.getAttribute('data-level') === levelId) {
            item.style.display = 'block';
            hasMajors = true;
        } else {
            item.style.display = 'none';
            item.querySelector('input').checked = false; // Uncheck hidden ones
        }
    });
    
    if(!hasMajors) {
        container.classList.add('d-none');
        alert("Hệ đào tạo này chưa có Ngành học nào. Hãy tạo Ngành học trước!");
        document.getElementById('periodLevelSelect').value = '';
    }
}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
