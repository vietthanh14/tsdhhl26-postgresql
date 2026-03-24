<?php
// candidate/index.php
session_start();
require_once __DIR__ . '/../lib/SupabaseClient.php';

// Kiểm tra xem đã đăng nhập chưa
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
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
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$profile = $profileResponse['data'][0];

// Lấy danh sách hồ sơ (đã nộp) của thí sinh với các join liên quan
$appsQuery = "select=*,majors(major_name,zalo_link,education_levels(name)),admission_periods(name)&user_id=eq.{$user_id}&order=priority.asc,submitted_at.desc";
$appsResponse = $supabase->select('applications', $appsQuery, $token);
$applications = ($appsResponse['code'] == 200) ? $appsResponse['data'] : [];

// Lấy danh sách phương thức xét tuyển (từ cache, TTL 1 giờ)
require_once __DIR__ . '/../lib/Cache.php';
$methodsData = Cache::remember('admission_methods', 3600, function() use ($supabase) {
    $res = $supabase->select('admission_methods', 'select=id,method_name&order=id.asc');
    return ($res['code'] == 200) ? $res['data'] : [];
});
$methodsMap = [];
foreach ($methodsData as $mt) { $methodsMap[$mt['id']] = $mt['method_name']; }
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
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/public.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/dashboard.css">
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
                        <p class="mb-0">Hồ sơ cá nhân của bạn chưa đầy đủ (Số điện thoại, địa chỉ...). Vui lòng <a href="<?php echo BASE_URL; ?>/candidate/profile.php" class="alert-link">cập nhật thông tin</a> trước khi đăng ký xét tuyển.</p>
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
                                <!-- Desktop: Table (hidden on mobile) -->
                                <div class="table-responsive d-none d-md-block">
                                    <table class="table table-hover mb-0 align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="ps-3" style="width:90px; white-space:nowrap;">NV</th>
                                                <th>Hệ đào tạo</th>
                                                <th>Ngành học</th>
                                                <th>Phương thức XT</th>
                                                <th>Đợt xét tuyển</th>
                                                <th>Lệ phí</th>
                                                <th>Trạng thái</th>
                                                <th>Nhóm Zalo</th>
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
                                                <td><?php echo htmlspecialchars($app['majors']['education_levels']['name'] ?? 'N/A'); ?></td>
                                                <td class="fw-semibold text-brand"><?php echo htmlspecialchars($app['majors']['major_name'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($methodsMap[$app['admission_method_id']] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($app['admission_periods']['name'] ?? 'N/A'); ?></td>
                                                <td><?php echo number_format($app['fee_amount'], 0, ',', '.'); ?> đ</td>
                                                <td>
                                                    <?php 
                                                        $statusClass = 'bg-secondary';
                                                        $statusText = 'Chưa xác định';
                                                        if($app['status'] == 'PENDING') { $statusClass = 'bg-warning text-dark'; $statusText = '⏳ Chờ duyệt'; }
                                                        elseif($app['status'] == 'APPROVED') { $statusClass = 'bg-success'; $statusText = '✅ Đã duyệt'; }
                                                        elseif($app['status'] == 'REJECTED') { $statusClass = 'bg-danger'; $statusText = '❌ Từ chối'; }
                                                    ?>
                                                    <span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <?php $zalo = $app['majors']['zalo_link'] ?? ''; ?>
                                                    <?php if (!empty($zalo)): ?>
                                                    <a href="<?php echo htmlspecialchars($zalo); ?>" target="_blank" class="text-primary" title="Vào nhóm Zalo" style="font-size: 1.2rem;">
                                                        <i class="bi bi-chat-dots-fill"></i>
                                                    </a>
                                                    <?php else: ?>
                                                    <span class="text-muted">—</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Mobile: Card layout (synced with search page style) -->
                                <div class="d-md-none">
                                    <?php foreach ($applications as $i => $app): ?>
                                    <?php
                                        $isApproved = ($app['status'] === 'APPROVED');
                                        $statusClass = 'bg-secondary'; $statusText = 'Chưa xác định';
                                        if($app['status'] == 'PENDING') { $statusClass = 'bg-warning text-dark'; $statusText = '⏳ Chờ duyệt'; }
                                        elseif($app['status'] == 'APPROVED') { $statusClass = 'bg-success'; $statusText = '✅ Đã duyệt'; }
                                        elseif($app['status'] == 'REJECTED') { $statusClass = 'bg-danger'; $statusText = '❌ Từ chối'; }
                                        $zalo = $app['majors']['zalo_link'] ?? '';
                                        $majorName = htmlspecialchars($app['majors']['major_name'] ?? 'N/A');
                                        $levelName = htmlspecialchars($app['majors']['education_levels']['name'] ?? '');
                                        $titleText = $levelName ? "$majorName — $levelName" : $majorName;
                                    ?>
                                    <div class="result-card mb-3" style="border-radius:12px;overflow:hidden;border:1px solid #e2e8f0;background:#fff;">
                                        <!-- Card header -->
                                        <div style="background:#1A3A6E;padding:14px 16px;display:flex;justify-content:space-between;align-items:center;">
                                            <span style="color:#fff;font-weight:700;font-size:.9rem;"><i class="bi bi-mortarboard me-1"></i><?php echo $titleText; ?></span>
                                            <span class="badge <?php echo $statusClass; ?>" style="font-size:.75rem;padding:5px 12px;border-radius:20px;"><?php echo $statusText; ?></span>
                                        </div>
                                        <!-- Card body -->
                                        <div class="p-3">
                                            <div class="row g-2 small">
                                                <div class="col-5 text-muted fw-medium">Phương thức XT</div>
                                                <div class="col-7"><?php echo htmlspecialchars($methodsMap[$app['admission_method_id']] ?? 'N/A'); ?></div>
                                                <div class="col-5 text-muted fw-medium">Đợt xét tuyển</div>
                                                <div class="col-7"><?php echo htmlspecialchars($app['admission_periods']['name'] ?? 'N/A'); ?></div>
                                                <div class="col-5 text-muted fw-medium">Lệ phí</div>
                                                <div class="col-7 fw-semibold"><?php echo number_format($app['fee_amount'], 0, ',', '.'); ?> đ</div>
                                            </div>
                                            <div class="d-flex align-items-center gap-2 mt-3 pt-2 border-top" style="<?php echo $isApproved ? 'opacity:.4;pointer-events:none;' : ''; ?>">
                                                <small class="text-muted fw-medium">Nguyện vọng:</small>
                                                <input type="number" class="form-control form-control-sm text-center priority-input" 
                                                       value="<?php echo intval($app['priority'] ?? 1); ?>" min="1" max="10"
                                                       data-app-id="<?php echo $app['id']; ?>"
                                                       style="width:55px;font-weight:600;"
                                                       <?php echo $isApproved ? 'disabled' : ''; ?>>
                                                <button class="btn btn-sm btn-outline-success save-priority-btn" title="Lưu thứ tự"
                                                        data-app-id="<?php echo $app['id']; ?>"
                                                        <?php echo $isApproved ? 'disabled' : ''; ?>><i class="bi bi-check-lg"></i></button>
                                            </div>
                                        </div>
                                        <!-- Zalo bar (like download bar on search page) -->
                                        <?php if (!empty($zalo)): ?>
                                        <div style="padding:12px 16px;background:linear-gradient(135deg,#e0f2fe,#bae6fd);border-top:2px solid #0ea5e9;text-align:center;">
                                            <a href="<?php echo htmlspecialchars($zalo); ?>" target="_blank" 
                                               style="display:inline-block;padding:8px 24px;font-size:.88rem;font-weight:700;text-decoration:none;color:#fff;background:linear-gradient(135deg,#0ea5e9,#0284c7);border-radius:8px;transition:transform .2s,box-shadow .2s;">
                                                <i class="bi bi-chat-dots-fill me-1"></i>Vào Nhóm Zalo Ngành
                                            </a>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
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
        if (isNaN(priority) || priority < 1) { showNotifyModal('Thứ tự nguyện vọng phải là số >= 1', 'warning'); return; }

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
                showNotifyModal('Lỗi lưu: ' + (data.message || 'Không rõ'), 'danger');
            }
        } catch(e) {
            showNotifyModal('Lỗi kết nối: ' + e.message, 'danger');
        } finally {
            this.disabled = false;
        }
    });
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>

