<?php
// admin/users.php
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

// Xu ly CAP NHAT TAI KHOAN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_user') {
    $user_id = $_POST['user_id'];
    $data = [
        'full_name' => trim($_POST['full_name']),
        'identity_card' => trim($_POST['identity_card']),
        'contact_email' => trim($_POST['contact_email']),
        'date_of_birth' => !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null,
        'phone_number' => trim($_POST['phone_number']),
        'address' => trim($_POST['address']),
        'updated_at' => date('Y-m-d H:i:sP')
    ];
    $res = $supabaseAdmin->update('user_profiles', 'id', $user_id, $data);
    if (in_array($res['code'], [200, 204])) {
        $_SESSION['msg'] = "Cập nhật thông tin thí sinh thành công!";
    } else {
        $_SESSION['err'] = "Lỗi cập nhật: " . json_encode($res['data']);
    }
    header("Location: users.php"); exit;
}

// Lay danh sach tai khoan thi sinh
$usersRes = $supabaseAdmin->select('user_profiles', 'order=created_at.desc');
$users = ($usersRes['code'] == 200) ? $usersRes['data'] : [];

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Thí sinh - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="/tsdhhl26/assets/css/public.css">
    
    <style>
        :root { --brand-color: #1A3A6E; --sidebar-bg: #1A3A6E; }
        body { background-color: #f7f9fc; font-family: 'Inter', sans-serif; font-size: 0.9rem;}
        .sidebar { background-color: var(--sidebar-bg); min-height: 100vh; padding-top: 25px; position: fixed; height: 100%; z-index: 1000;}
        .sidebar a { color: #cbd5e1; text-decoration: none; padding: 12px 24px; display: block; border-left: 3px solid transparent; font-weight: 500; }
        .sidebar a:hover, .sidebar a.active { background-color: rgba(255,255,255,0.05); color: #fff; border-left-color: #3b82f6; }
        .main-content { margin-left: 16.666667%; padding: 30px; transition: margin-left 0.3s; }
        @media (max-width: 767.98px) {
            .main-content { margin-left: 0 !important; padding: 15px !important; }
        }
        table.dataTable td { vertical-align: middle; }
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
        <a href="/tsdhhl26/admin/applications.php">Quản lý Hồ sơ</a>
        <a href="/tsdhhl26/admin/documents.php">Tài liệu tải lên</a>
        <a href="/tsdhhl26/admin/users.php" class="active">Quản lý Thí sinh</a>
        <hr class="text-secondary mx-3">
        <a href="/tsdhhl26/admin/logout.php" class="text-danger">Đăng xuất</a>
    </div>

    <!-- Main Content -->
    <div class="col-md-10 main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold mb-0 text-brand">Quản lý Thông tin Thí sinh</h3>
        </div>

        <?php if($message): ?><div class="alert alert-success alert-dismissible fade show"><?php echo $message; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
        <?php if($error): ?><div class="alert alert-danger alert-dismissible fade show"><?php echo $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle w-100" id="usersTable">
                        <thead class="table-light text-muted">
                            <tr>
                                <th>Họ và Tên</th>
                                <th>Thông tin liên hệ</th>
                                <th>CMND / CCCD</th>
                                <th>Ngày sinh</th>
                                <th>Địa chỉ</th>
                                <th>Ngày đăng ký</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                            <tr>
                                <td>
                                    <strong class="d-block text-brand"><?php echo htmlspecialchars($u['full_name'] ?? 'Không rõ'); ?></strong>
                                    <span class="text-muted small">ID: <?php echo htmlspecialchars(substr($u['id'], 0, 8) . '...'); ?></span>
                                </td>
                                <td>
                                    <span class="d-block small"><i class="bi bi-envelope text-muted"></i> <?php echo htmlspecialchars($u['contact_email'] ?? 'N/A'); ?></span>
                                    <span class="d-block small mt-1"><i class="bi bi-telephone text-muted"></i> <?php echo htmlspecialchars($u['phone_number'] ?? 'N/A'); ?></span>
                                </td>
                                <td>
                                    <span class="d-block fw-semibold text-dark"><?php echo htmlspecialchars($u['identity_card'] ?? 'N/A'); ?></span>
                                </td>
                                <td>
                                    <?php if(!empty($u['date_of_birth'])): ?>
                                        <span><?php echo date('d/m/Y', strtotime($u['date_of_birth'])); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted small">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="small text-muted" style="max-width: 200px; display: inline-block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($u['address'] ?? ''); ?>">
                                        <?php echo htmlspecialchars($u['address'] ?? 'N/A'); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="d-block small text-muted"><?php echo date('d/m/Y H:i', strtotime($u['created_at'])); ?></span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-warning" onclick="editUser('<?php echo $u['id']; ?>', '<?php echo htmlspecialchars($u['full_name'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($u['identity_card'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($u['contact_email'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($u['phone_number'] ?? '', ENT_QUOTES); ?>', '<?php echo $u['date_of_birth'] ?? ''; ?>', '<?php echo htmlspecialchars($u['address'] ?? '', ENT_QUOTES); ?>')"><i class="bi bi-pencil-square"></i></button>
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

<!-- Modal Edit User -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Chỉnh sửa Thông tin Thí sinh</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="update_user">
                <input type="hidden" name="user_id" id="edit_user_id">
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Họ và Tên</label>
                        <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">CMND/CCCD</label>
                        <input type="text" name="identity_card" id="edit_identity_card" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email liên hệ</label>
                        <input type="email" name="contact_email" id="edit_contact_email" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Số điện thoại</label>
                        <input type="text" name="phone_number" id="edit_phone_number" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Ngày sinh</label>
                        <input type="date" name="date_of_birth" id="edit_date_of_birth" class="form-control">
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Địa chỉ</label>
                        <textarea name="address" id="edit_address" class="form-control" rows="2"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-warning">Lưu thay đổi</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
    function editUser(id, name, idCard, email, phone, dob, address) {
        document.getElementById('edit_user_id').value = id;
        document.getElementById('edit_full_name').value = name;
        document.getElementById('edit_identity_card').value = idCard;
        document.getElementById('edit_contact_email').value = email;
        document.getElementById('edit_phone_number').value = phone;
        document.getElementById('edit_date_of_birth').value = dob;
        document.getElementById('edit_address').value = address;
        
        new bootstrap.Modal(document.getElementById('editUserModal')).show();
    }

    $(document).ready(function() {
        $('#usersTable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json"
            },
            "order": [[5, "desc"]], // Sắp xếp theo ngày đăng ký
            "pageLength": 50
        });
    });
</script>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
