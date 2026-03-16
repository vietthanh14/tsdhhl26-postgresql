<?php
// candidate/index.php
session_start();
require_once __DIR__ . '/../lib/SupabaseClient.php';

// Kiểm tra xem đã đăng nhập chưa
if (!isset($_SESSION['user_id'])) {
    header('Location: /tsdhhl26/auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$token = $_SESSION['access_token'] ?? null;
$supabase = new SupabaseClient('anon');

// Lấy thông tin cá nhân của thí sinh
$profileResponse = $supabase->select('user_profiles', "id=eq.{$user_id}", $token);

// Bảo mật: Nếu token hết hạn (401) hoặc user đã bị xoá khỏi DB (empty data), xoá session cũ
if ($profileResponse['code'] == 401 || empty($profileResponse['data'])) {
    session_destroy();
    header('Location: /tsdhhl26/auth/login.php');
    exit;
}

$profile = $profileResponse['data'][0];

// Lấy danh sách hồ sơ (các ngành đã nộp) của thí sinh
// Note: Sẽ cần kết nối (Join) các bảng majos, admission_periods để lấy tên thay vì ID.
// Ở đây Supabase cho phép join tự động thông qua khóa ngoại, ví dụ: select=*,majors(major_name),admission_periods(name)
$appsQuery = "select=*,majors(major_name),admission_periods(name)&user_id=eq.{$user_id}&order=priority.asc,submitted_at.desc";
$appsResponse = $supabase->select('applications', $appsQuery, $token);
$applications = [];
if ($appsResponse['code'] == 200) {
    $applications = $appsResponse['data'];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang Của Bạn - Tuyển sinh Đại học Hạ Long</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/tsdhhl26/assets/css/public.css">

    <style>
        :root {
            --brand-color: #1A3A6E;
            --brand-hover: #12284c;
            --sidebar-bg: #1A3A6E; /* Darker version for sidebar */
            --bg-color: #f7f9fc;
            --border-radius: 4px;
        }
        body { background-color: var(--bg-color); font-family: 'Inter', sans-serif; color: #333; }
        .sidebar { background-color: var(--sidebar-bg); min-height: 100vh; padding-top: 25px; box-shadow: 2px 0 10px rgba(0,0,0,0.05); }
        .sidebar a { color: #cbd5e1; text-decoration: none; padding: 12px 24px; display: block; border-left: 3px solid transparent; font-weight: 500; transition: all 0.2s; }
        .sidebar a:hover, .sidebar a.active { background-color: rgba(255,255,255,0.05); color: #fff; border-left-color: #3b82f6; }
        .content-area { padding: 40px; }
        .card { border: 1px solid #e2e8f0; border-radius: var(--border-radius); box-shadow: 0 2px 4px rgba(0,0,0,0.02); margin-bottom: 24px; }
        .card-header { border-bottom: 1px solid #e2e8f0; background: white; }
        .btn-brand { background-color: var(--brand-color); color: white; border: none; border-radius: var(--border-radius); }
        .btn-brand:hover { background-color: var(--brand-hover); color: white; }
        .text-brand { color: var(--brand-color) !important; }
        .badge { border-radius: 4px; padding: 0.4em 0.6em; font-weight: 500; }
        .table th { font-weight: 600; color: #64748b; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; border-bottom-width: 1px; }
        .table td { vertical-align: middle; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="container-fluid">

    <div class="row">
        <!-- Sidebar Component -->
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="col-md-10 content-area">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-3 border-bottom gap-2">
                <h3 class="fw-bold mb-0 text-dark">Bảng điều khiển</h3>
            </div>

            <div class="row">
                <!-- Cảnh báo nếu chưa cập nhật hồ sơ -->
                <?php if(empty($profile['phone_number']) || empty($profile['address'])): ?>
                <div class="col-12 mb-4">
                    <div class="alert alert-warning border-start border-warning border-4">
                        <h5 class="alert-heading">⚠️ Thông báo quan trọng!</h5>
                        <p class="mb-0">Hồ sơ cá nhân của bạn chưa đầy đủ (Số điện thoại, địa chỉ...). Vui lòng <a href="/tsdhhl26/candidate/profile.php" class="alert-link">cập nhật thông tin</a> trước khi đăng ký xét tuyển.</p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Danh sách hồ sơ đã nộp -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center py-3">
                            <h6 class="mb-0 fw-bold">Hồ sơ xét tuyển của bạn</h6>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($applications)): ?>
                                <div class="text-center py-5 text-muted">
                                    <p class="mb-0">Bạn chưa nộp hồ sơ xét tuyển nào.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0 align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="ps-3" style="width:90px; white-space:nowrap;">Nguyện vọng</th>
                                                <th>Ngành đăng ký</th>
                                                <th>Đợt tuyển sinh</th>
                                                <th>Ngày nộp</th>
                                                <th>Lệ phí</th>
                                                <th>Trạng thái hồ sơ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($applications as $app): ?>
                                            <tr>
                                                <td class="ps-3 text-center">
                                                    <?php $isApproved = ($app['status'] === 'APPROVED'); ?>
                                                    <div class="d-flex align-items-center gap-1" style="width:90px; <?php echo $isApproved ? 'opacity:0.4; pointer-events:none;' : ''; ?>">
                                                        <input type="number" class="form-control form-control-sm text-center priority-input" 
                                                               value="<?php echo intval($app['priority'] ?? 1); ?>" min="1" max="10"
                                                               data-app-id="<?php echo $app['id']; ?>"
                                                               style="width:55px;font-weight:600;"
                                                               <?php echo $isApproved ? 'disabled' : ''; ?>>
                                                        <button class="btn btn-sm btn-outline-success save-priority-btn" title="Lưu thứ tự"
                                                                data-app-id="<?php echo $app['id']; ?>"
                                                                <?php echo $isApproved ? 'disabled' : ''; ?>><i class="bi bi-check-lg"></i></button>
                                                    </div>
                                                </td>
                                                <td class="fw-bold text-brand"><?php echo htmlspecialchars($app['majors']['major_name'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($app['admission_periods']['name'] ?? 'N/A'); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($app['submitted_at'])); ?></td>
                                                <td><?php echo number_format($app['fee_amount'], 0, ',', '.'); ?> đ</td>
                                                <td>
                                                    <?php 
                                                        $statusClass = 'bg-secondary';
                                                        $statusText = 'Chưa xác định';
                                                        if($app['status'] == 'PENDING') { $statusClass = 'bg-warning text-dark'; $statusText = '⏳ Đang chờ duyệt'; }
                                                        elseif($app['status'] == 'APPROVED') { $statusClass = 'bg-success'; $statusText = '✅ Đã duyệt'; }
                                                        elseif($app['status'] == 'REJECTED') { $statusClass = 'bg-danger'; $statusText = '❌ Từ chối'; }
                                                    ?>
                                                    <span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Helper card -->
                <div class="col-md-6 mt-3">
                    <div class="card h-100">
                        <div class="card-body border-start border-info border-4">
                            <h6 class="text-info fw-bold">HƯỚNG DẪN</h6>
                            <p class="text-muted small mb-0">Hồ sơ sẽ chỉ được đưa vào trạng thái duyệt khi bạn đã nộp đầy đủ các tài liệu yêu cầu tại mục <b>Tài liệu của tôi</b> và hoàn tất đóng lệ phí xét tuyển.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('.save-priority-btn').forEach(btn => {
    btn.addEventListener('click', async function() {
        const appId = this.dataset.appId;
        const input = document.querySelector(`.priority-input[data-app-id="${appId}"]`);
        const priority = parseInt(input.value);
        if (isNaN(priority) || priority < 1) { alert('Thứ tự nguyện vọng phải là số >= 1'); return; }

        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        try {
            const res = await fetch('update_priority.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ app_id: appId, priority: priority })
            });
            const data = await res.json();

            if (data.success && data.priority_map) {
                // Cập nhật tất cả các input bị ảnh hưởng bởi insert-and-shift
                for (const [id, newP] of Object.entries(data.priority_map)) {
                    const inp = document.querySelector(`.priority-input[data-app-id="${id}"]`);
                    if (inp) {
                        if (parseInt(inp.value) !== newP) {
                            // Highlight dòng bị dịch chuyển
                            inp.value = newP;
                            inp.closest('tr').style.transition = 'background-color 0.4s';
                            inp.closest('tr').style.backgroundColor = '#fff3cd';
                            setTimeout(() => inp.closest('tr').style.backgroundColor = '', 1200);
                        }
                    }
                }
                // Nút bấm thành công cho dòng được click
                this.innerHTML = '<i class="bi bi-check-lg"></i>';
                this.classList.replace('btn-outline-success', 'btn-success');
                setTimeout(() => {
                    this.classList.replace('btn-success', 'btn-outline-success');
                    this.innerHTML = '<i class="bi bi-check-lg"></i>';
                }, 1500);
            } else {
                alert('Lỗi lưu: ' + (data.message || 'Không rõ'));
            }
        } catch(e) {
            alert('Lỗi kết nối: ' + e.message);
        } finally {
            this.disabled = false;
        }
    });
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>

