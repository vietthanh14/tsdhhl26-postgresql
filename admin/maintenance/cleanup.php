<?php
require_once __DIR__ . '/../includes/admin_init.php';

// Các thư mục cần dọn dẹp
$directories_to_clean = [
    'CacheFiles' => __DIR__ . '/../../storage/cache',
    'CSVExports' => __DIR__ . '/../../uploads/exports'
];

$message = '';
$message_type = '';

// Hàm xóa đệ quy nội dung thư mục
function clear_directory($dir) {
    if (!is_dir($dir)) {
        return ['success' => false, 'error' => "Thư mục không tồn tại: $dir", 'count' => 0];
    }
    
    $count = 0;
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($files as $fileinfo) {
        $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
        if (@$todo($fileinfo->getRealPath())) {
            if ($todo === 'unlink') {
                $count++;
            }
        }
    }
    return ['success' => true, 'count' => $count];
}

// Xử lý khi có request POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'clear_cache' || $_POST['action'] === 'clear_exports' || $_POST['action'] === 'clear_all') {
        
        $total_deleted = 0;
        $success_msg = [];
        $error_msg = [];

        if ($_POST['action'] === 'clear_cache' || $_POST['action'] === 'clear_all') {
            $result = clear_directory($directories_to_clean['CacheFiles']);
            if ($result['success']) {
                $total_deleted += $result['count'];
                $success_msg[] = "Đã dọn dẹp {$result['count']} tệp Cache.";
            } else {
                $error_msg[] = $result['error'];
            }
        }

        if ($_POST['action'] === 'clear_exports' || $_POST['action'] === 'clear_all') {
            $result = clear_directory($directories_to_clean['CSVExports']);
            if ($result['success']) {
                $total_deleted += $result['count'];
                $success_msg[] = "Đã dọn dẹp {$result['count']} tệp CSV Export.";
            } else {
                $error_msg[] = $result['error'];
            }
        }

        if (empty($error_msg)) {
            $message_type = 'success';
            $message = "Dọn dẹp hoàn tất: " . implode(" ", $success_msg);
        } else {
            $message_type = 'warning';
            $message = "Hoàn tất với một số cảnh báo: " . implode(" | ", $error_msg) . " (Đã xoá $total_deleted tệp thành công).";
        }
    }
}

// Lấy thông tin thống kê hiện tại để hiển thị
function get_dir_stats($dir) {
    if (!is_dir($dir)) return ['count' => 0, 'size' => 0];
    $count = 0;
    $size = 0;
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    foreach ($files as $file) {
        if ($file->isFile()) {
            $count++;
            $size += $file->getSize();
        }
    }
    return ['count' => $count, 'size' => $size];
}

function format_size($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

$cache_stats = get_dir_stats($directories_to_clean['CacheFiles']);
$export_stats = get_dir_stats($directories_to_clean['CSVExports']);

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/logo.png">
    <title>Dọn dẹp hệ thống - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/public.css">
</head>
<body class="bg-light">

<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="container-fluid p-0">
    <div class="row m-0">
        <!-- Sidebar -->
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <div class="main-content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4 border-bottom">
                <h1 class="h3 fw-bold text-brand">
                    <i class="bi bi-trash text-danger me-2"></i> Dọn dẹp hệ thống
                </h1>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show shadow-sm" role="alert">
                    <i class="bi bi-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>-fill me-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Thống kê Tệp Cache -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-3">
                                    <i class="bi bi-hdd-network text-primary fs-3"></i>
                                </div>
                                <div>
                                    <h5 class="card-title fw-bold mb-1">Bộ nhớ đệm (Cache)</h5>
                                    <p class="card-text text-muted small mb-0">Các tệp tạm sinh ra để tăng tốc hệ thống và lưu tạm Google Sheets.</p>
                                </div>
                            </div>
                            
                            <ul class="list-group list-group-flush mb-4">
                                <li class="list-group-item px-0 d-flex justify-content-between align-items-center border-0">
                                    <span class="text-secondary"><i class="bi bi-file-earmark me-2"></i>Số lượng tệp:</span>
                                    <span class="badge bg-secondary rounded-pill fs-6"><?php echo number_format($cache_stats['count']); ?></span>
                                </li>
                                <li class="list-group-item px-0 d-flex justify-content-between align-items-center border-0 py-0 pb-2">
                                    <span class="text-secondary"><i class="bi bi-database me-2"></i>Dung lượng chiếm:</span>
                                    <span class="fw-bold text-dark"><?php echo format_size($cache_stats['size']); ?></span>
                                </li>
                            </ul>

                            <form method="POST" action="">
                                <input type="hidden" name="action" value="clear_cache">
                                <button type="submit" class="btn btn-outline-primary w-100 fw-medium" <?php echo $cache_stats['count'] == 0 ? 'disabled' : ''; ?>>
                                    <i class="bi bi-eraser me-1"></i> Xoá Cache
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Thống kê Tệp CSV -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-success bg-opacity-10 p-3 rounded-circle me-3">
                                    <i class="bi bi-file-earmark-spreadsheet text-success fs-3"></i>
                                </div>
                                <div>
                                    <h5 class="card-title fw-bold mb-1">Tệp trích xuất (CSV)</h5>
                                    <p class="card-text text-muted small mb-0">Các bản sao dữ liệu định dạng CSV được trích xuất từ CSDL.</p>
                                </div>
                            </div>

                            <ul class="list-group list-group-flush mb-4">
                                <li class="list-group-item px-0 d-flex justify-content-between align-items-center border-0">
                                    <span class="text-secondary"><i class="bi bi-file-earmark-text me-2"></i>Số lượng tệp:</span>
                                    <span class="badge bg-secondary rounded-pill fs-6"><?php echo number_format($export_stats['count']); ?></span>
                                </li>
                                <li class="list-group-item px-0 d-flex justify-content-between align-items-center border-0 py-0 pb-2">
                                    <span class="text-secondary"><i class="bi bi-database me-2"></i>Dung lượng chiếm:</span>
                                    <span class="fw-bold text-dark"><?php echo format_size($export_stats['size']); ?></span>
                                </li>
                            </ul>

                            <form method="POST" action="">
                                <input type="hidden" name="action" value="clear_exports">
                                <button type="submit" class="btn btn-outline-success w-100 fw-medium" <?php echo $export_stats['count'] == 0 ? 'disabled' : ''; ?>>
                                    <i class="bi bi-trash3 me-1"></i> Xoá Tệp CSV
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hành động chung -->
            <div class="mt-4 card border-danger border-opacity-25 shadow-sm">
                <div class="card-body bg-danger bg-opacity-10 p-4 rounded d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="text-danger fw-bold mb-1">Dọn dẹp toàn diện</h5>
                        <p class="mb-0 text-dark small">Xoá toàn bộ tệp tạm (Cache) và các file dữ liệu trích xuất (CSV) cùng một lúc. Giải phóng: <strong class="fs-6"><?php echo format_size($cache_stats['size'] + $export_stats['size']); ?></strong></p>
                    </div>
                    <form method="POST" action="" onsubmit="return confirm('Bạn có chắc chắn muốn xoá sạch toàn bộ Cache và CSV đã trích xuất không?');">
                        <input type="hidden" name="action" value="clear_all">
                        <button type="submit" class="btn btn-danger fw-bold fs-6 px-4 py-2 shadow-sm rounded-pill" <?php echo ($cache_stats['count'] + $export_stats['count']) == 0 ? 'disabled' : ''; ?>>
                            <i class="bi bi-fire me-2"></i> Thực thi Dọn Dẹp
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>
