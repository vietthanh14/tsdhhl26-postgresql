<?php
// admin/documents.php
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

// Lay danh sach tai lieu
$query = 'select=*,document_types(type_name)&order=uploaded_at.desc';
$docsRes = $supabaseAdmin->select('user_documents', $query);
$documents = ($docsRes['code'] == 200) ? $docsRes['data'] : [];

// Lay danh sach user profiles de map thu cong 
$usersRes = $supabaseAdmin->select('user_profiles', 'select=id,full_name,identity_card,phone_number');
$userProfilesMap = [];
if ($usersRes['code'] == 200 && is_array($usersRes['data'])) {
    foreach ($usersRes['data'] as $u) {
        $userProfilesMap[$u['id']] = $u;
    }
}

// Map user profiles vao tung document
foreach ($documents as &$doc) {
    if (isset($doc['user_id']) && isset($userProfilesMap[$doc['user_id']])) {
        $doc['user_profiles'] = $userProfilesMap[$doc['user_id']];
    } else {
        $doc['user_profiles'] = [];
    }
}
unset($doc);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Tài liệu - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="/tsdhhl26/assets/css/public.css">
    
    <style>
        :root { --brand-color: #1A3A6E; --sidebar-bg: #0f2444; }
        body { background-color: #f7f9fc; font-family: 'Inter', sans-serif; font-size: 0.9rem;}
        .sidebar { background-color: var(--sidebar-bg); min-height: 100vh; padding-top: 25px; position: fixed; height: 100%; z-index: 1000;}
        .sidebar a { color: #cbd5e1; text-decoration: none; padding: 12px 24px; display: block; border-left: 3px solid transparent; font-weight: 500; }
        .sidebar a:hover, .sidebar a.active { background-color: rgba(255,255,255,0.05); color: #fff; border-left-color: #3b82f6; }
        .main-content { margin-left: 16.666667%; padding: 30px; }
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
        <a href="/tsdhhl26/admin/documents.php" class="active">Tài liệu tải lên</a>
        <a href="/tsdhhl26/admin/users.php">Quản lý Thí sinh</a>
        <hr class="text-secondary mx-3">
        <a href="/tsdhhl26/admin/logout.php" class="text-danger">Đăng xuất</a>
    </div>

    <!-- Main Content -->
    <div class="col-md-10 main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold mb-0 text-brand">Quản lý Tài liệu Ứng viên</h3>
        </div>

        <?php if($message): ?><div class="alert alert-success alert-dismissible fade show"><?php echo $message; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
        <?php if($error): ?><div class="alert alert-danger alert-dismissible fade show"><?php echo $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle w-100" id="docsTable">
                        <thead class="table-light text-muted">
                            <tr>
                                <th>Thí sinh</th>
                                <th>Thông tin liên hệ</th>
                                <th>Loại tài liệu</th>
                                <th>Ngày tải lên</th>
                                <th>File đính kèm</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($documents as $doc): 
                                $user = $doc['user_profiles'] ?? [];
                                $doctype = $doc['document_types'] ?? [];
                            ?>
                            <tr>
                                <td>
                                    <strong class="d-block text-brand"><?php echo htmlspecialchars($user['full_name'] ?? 'Không rõ'); ?></strong>
                                    <span class="text-muted small">CMND: <?php echo htmlspecialchars($user['identity_card'] ?? 'N/A'); ?></span>
                                </td>
                                <td>
                                    <span class="d-block small"><i class="bi bi-telephone text-muted"></i> <?php echo htmlspecialchars($user['phone_number'] ?? 'N/A'); ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-info text-dark border mt-1">
                                        <?php echo htmlspecialchars($doctype['type_name'] ?? 'Không rõ'); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="d-block small mt-1 text-muted"><?php echo date('d/m/Y H:i', strtotime($doc['uploaded_at'])); ?></span>
                                </td>
                                <td>
                                    <?php if (!empty($doc['drive_file_url'])): ?>
                                        <a href="<?php echo htmlspecialchars($doc['drive_file_url']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-box-arrow-up-right"></i> Xem Tài liệu
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">Chưa có file</span>
                                    <?php endif; ?>
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

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function() {
        $('#docsTable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json"
            },
            "order": [[3, "desc"]], // Sắp xếp theo ngày tải lên hiển thị mới nhất
            "pageLength": 50
        });
    });
</script>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
