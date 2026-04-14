<?php
require_once __DIR__ . '/includes/admin_init.php';

// Phân trang
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 50;
$offset = ($page - 1) * $limit;

$searchStr = trim($_GET['search'] ?? '');
$filterArray = [];
if ($searchStr !== '') {
    $encodedSearch = urlencode('%' . $searchStr . '%');
    $userFilter = "or=(full_name.ilike.{$encodedSearch},identity_card.ilike.{$encodedSearch},phone_number.ilike.{$encodedSearch})&select=id";
    $searchUsersRes = $supabaseAdmin->select('user_profiles', $userFilter);
    $searchUserIds = ($searchUsersRes['code'] == 200 && is_array($searchUsersRes['data'])) ? array_column($searchUsersRes['data'], 'id') : [];
    
    if (empty($searchUserIds)) {
        $filterArray[] = "user_id=eq.00000000-0000-0000-0000-000000000000";
    } else {
        $inList = DatabaseClient::buildInList($searchUserIds);
        $filterArray[] = "user_id=in.({$inList})";
    }
}
$filterQuery = !empty($filterArray) ? implode('&', $filterArray) . '&' : '';

$totalDocs = $supabaseAdmin->count('user_documents', rtrim($filterQuery, '&'));
$totalPages = $totalDocs > 0 ? ceil($totalDocs / $limit) : 1;

// Lay danh sach tai lieu (Paginated)
$query = $filterQuery . "select=*,document_types(type_name)&order=uploaded_at.desc&limit={$limit}&offset={$offset}";
$docsRes = $supabaseAdmin->select('user_documents', $query);
$documents = ($docsRes['code'] == 200) ? $docsRes['data'] : [];

// Lay danh sach user profiles de map thu cong (Chỉ mảng userID hiện tại)
$userProfilesMap = $supabaseAdmin->fetchUserProfilesMap(array_column($documents, 'user_id'));

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
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/logo.png">
    <title>Quản lý Tài liệu - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/public.css">
    
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="container-fluid p-0">
    <div class="row m-0">
        <!-- Sidebar -->
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <h3 class="fw-bold mb-0 text-brand">Quản lý Tài liệu Ứng viên</h3>
                <div class="d-flex gap-2 ms-auto align-items-center flex-wrap">
                    <form method="GET" class="d-flex m-0 gap-2">
                        <input type="text" name="search" class="form-control form-control-sm shadow-sm border-brand" placeholder="Tên, CMND, SĐT..." value="<?php echo htmlspecialchars($searchStr ?? ''); ?>" style="min-width: 180px;">
                        <button type="submit" class="btn btn-sm btn-brand shadow-sm"><i class="bi bi-search me-1"></i>Tìm</button>
                        <?php if(!empty($searchStr)): ?>
                        <a href="documents.php" class="btn btn-sm btn-outline-secondary" title="Xóa tìm kiếm"><i class="bi bi-x-lg"></i></a>
                        <?php endif; ?>
                    </form>
                    <button type="button" class="btn btn-sm btn-success shadow-sm" id="btnExportDocs">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Xuất Excel (CSV)
                    </button>
                </div>
            </div>

            <?php include __DIR__ . '/../includes/flash_messages.php'; ?>

            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle w-100" id="docsTable">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th>Thí sinh</th>
                                    <th>Thông tin liên hệ</th>
                                    <th>Loại tài liệu</th>
                                    <th>Ngày tải lên</th>
                                    <th class="text-end">File đính kèm</th>
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
                                        <span class="badge bg-light text-dark border shadow-xs mt-1 px-2 py-1">
                                            <?php echo htmlspecialchars($doctype['type_name'] ?? 'Không rõ'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="d-block small mt-1 text-muted"><?php echo date('d/m/Y H:i', strtotime($doc['uploaded_at'])); ?></span>
                                    </td>
                                    <td class="text-end">
                                        <?php if (!empty($doc['drive_file_url'])): ?>
                                            <a href="<?php echo htmlspecialchars($doc['drive_file_url']); ?>" target="_blank" class="btn btn-sm btn-outline-brand">
                                                <i class="bi bi-box-arrow-up-right"></i> Xem
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted small italic">Chưa có file</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php $queryParams = ['search' => $searchStr ?? '']; ?>
                    <?php include __DIR__ . '/includes/paginator.php'; ?>
                    <div class="text-center text-muted small mt-2">Tổng số: <?php echo number_format($totalDocs); ?> tài liệu</div>
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
            "paging": false,
            "searching": false,
            "info": false,
            "order": [[3, "desc"]]
        });

        // Xử lý Tải xuống bằng File vật lý từ Server
        $('#btnExportDocs').on('click', function(e) {
            e.preventDefault();
            var btn = $(this);
            var originalHtml = btn.html();
            btn.html('<span class="spinner-border spinner-border-sm"></span> Đang tải...').prop('disabled', true);
            
            var cacheBuster = new Date().getTime();
            
            $.ajax({
                url: 'api/export_docs_csv.php?t=' + cacheBuster,
                method: 'GET',
                dataType: 'json',
                success: function(res) {
                    if(res.status === 'success' && res.file_url) {
                        var link = document.createElement('a');
                        link.href = res.file_url;
                        link.download = res.filename;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    } else {
                        alert("Lỗi xuất file: " + (res.message || 'Unknown'));
                    }
                },
                error: function(err) {
                    alert("Có lỗi xảy ra khi tạo file xuất. Vui lòng thử lại.");
                    console.error(err);
                },
                complete: function() {
                    btn.html(originalHtml).prop('disabled', false);
                }
            });
        });
    });
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
