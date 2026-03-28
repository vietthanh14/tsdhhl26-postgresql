<?php
require_once __DIR__ . '/includes/admin_init.php';

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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold mb-0 text-brand">Quản lý Tài liệu Ứng viên</h3>
                <button type="button" class="btn btn-success shadow-sm" id="btnExportDocs">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i> Tải xuống Excel (CSV)
                </button>
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
            "order": [[3, "desc"]], 
            "pageLength": 25,
            "drawCallback": function() {
                $('.dataTables_paginate > .pagination').addClass('pagination-sm mt-3');
            }
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
