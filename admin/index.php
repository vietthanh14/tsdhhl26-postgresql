<?php
require_once __DIR__ . '/includes/admin_init.php';

// Các thư mục cần dọn dẹp
$directories_to_clean = [
    'CacheFiles' => __DIR__ . '/../storage/cache',
    'CSVExports' => __DIR__ . '/../uploads/exports'
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

// Lấy thông kê sử dụng hàm đếm HEAD (Zero Egress)
$totalApps = $supabaseAdmin->count('applications');
$totalUsers = $supabaseAdmin->count('user_profiles');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/logo.png">
    <title>Bảng Điều Khiển Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
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
            <h3 class="fw-bold mb-4 text-brand">Tổng quan Hệ Thống Tuyển Sinh</h3>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show shadow-sm" role="alert">
                    <i class="bi bi-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>-fill me-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="card stat-card p-4 h-100">
                        <div class="text-muted small fw-bold text-uppercase mb-1">TỔNG HỒ SƠ ĐÃ NỘP</div>
                        <div class="fs-2 fw-bold text-brand"><?php echo $totalApps; ?></div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card stat-card p-4 h-100">
                        <div class="text-muted small fw-bold text-uppercase mb-1">TÀI KHOẢN THÍ SINH</div>
                        <div class="fs-2 fw-bold text-brand"><?php echo $totalUsers; ?></div>
                    </div>
                </div>
            </div>
            
            <div class="mt-4 p-4 bg-white rounded-3 shadow-sm border-0 mb-4">
                <h5 class="fw-bold mb-3 text-dark">Dọn dẹp hệ thống</h5>
                <p class="text-muted small mb-4">Xoá các tệp tin tạm và tệp xuất (CSV) để giải phóng dung lượng cho máy chủ.</p>
                
                <div class="row g-4">
                    <!-- Thống kê Tệp Cache -->
                    <div class="col-md-6">
                        <div class="card border-0 bg-light h-100">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-primary bg-opacity-10 p-2 rounded-circle me-3">
                                        <i class="bi bi-hdd-network text-primary fs-4"></i>
                                    </div>
                                    <div>
                                        <h6 class="card-title fw-bold mb-0">Bộ nhớ đệm (Cache)</h6>
                                        <ul class="list-unstyled mb-0 small text-muted mt-1">
                                            <li><i class="bi bi-file-earmark me-1"></i>Số lượng: <?php echo number_format($cache_stats['count']); ?> tệp</li>
                                            <li><i class="bi bi-database me-1"></i>Dung lượng: <?php echo format_size($cache_stats['size']); ?></li>
                                        </ul>
                                    </div>
                                </div>
                                <form method="POST" action="" onsubmit="event.preventDefault(); confirmDelete(this, 'Bạn có chắc chắn muốn xoá bộ nhớ đệm (Cache)?');">
                                    <input type="hidden" name="action" value="clear_cache">
                                    <button type="submit" class="btn btn-sm btn-outline-primary w-100 fw-medium" <?php echo $cache_stats['count'] == 0 ? 'disabled' : ''; ?>>
                                        <i class="bi bi-eraser me-1"></i> Xoá Cache
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Thống kê Tệp CSV -->
                    <div class="col-md-6">
                        <div class="card border-0 bg-light h-100">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-success bg-opacity-10 p-2 rounded-circle me-3">
                                        <i class="bi bi-file-earmark-spreadsheet text-success fs-4"></i>
                                    </div>
                                    <div>
                                        <h6 class="card-title fw-bold mb-0">Tệp trích xuất (CSV)</h6>
                                        <ul class="list-unstyled mb-0 small text-muted mt-1">
                                            <li><i class="bi bi-file-earmark-text me-1"></i>Số lượng: <?php echo number_format($export_stats['count']); ?> tệp</li>
                                            <li><i class="bi bi-database me-1"></i>Dung lượng: <?php echo format_size($export_stats['size']); ?></li>
                                        </ul>
                                    </div>
                                </div>
                                <form method="POST" action="" onsubmit="event.preventDefault(); confirmDelete(this, 'Bạn có chắc chắn muốn xoá các tệp CSV đã trích xuất?');">
                                    <input type="hidden" name="action" value="clear_exports">
                                    <button type="submit" class="btn btn-sm btn-outline-success w-100 fw-medium" <?php echo $export_stats['count'] == 0 ? 'disabled' : ''; ?>>
                                        <i class="bi bi-trash3 me-1"></i> Xoá Tệp CSV
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hành động chung -->
                <div class="mt-3 bg-danger bg-opacity-10 p-3 rounded d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h6 class="text-danger fw-bold mb-0">Dọn dẹp toàn diện</h6>
                        <small class="text-dark">Giải phóng: <strong><?php echo format_size($cache_stats['size'] + $export_stats['size']); ?></strong></small>
                    </div>
                    <form method="POST" action="" onsubmit="event.preventDefault(); confirmDelete(this, 'Bạn có chắc chắn muốn xoá sạch toàn bộ Cache và CSV đã trích xuất không?');">
                        <input type="hidden" name="action" value="clear_all">
                        <button type="submit" class="btn btn-sm btn-danger fw-bold px-3 shadow-sm rounded-pill" <?php echo ($cache_stats['count'] + $export_stats['count']) == 0 ? 'disabled' : ''; ?>>
                            <i class="bi bi-fire me-1"></i> Thực thi
                        </button>
                    </form>
                </div>
            </div>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
