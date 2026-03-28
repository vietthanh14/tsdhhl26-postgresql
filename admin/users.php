<?php
require_once __DIR__ . '/includes/admin_init.php';

// Xu ly CAP NHAT TAI KHOAN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'update_user') {
        $user_id = $_POST['user_id'];
        $data = [
            'full_name' => trim($_POST['full_name']),
            'identity_card' => trim($_POST['identity_card']),
            'contact_email' => trim($_POST['contact_email']),
            'date_of_birth' => !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null,
            'phone_number' => trim($_POST['phone_number']),
            'gender' => !empty($_POST['gender']) ? trim($_POST['gender']) : null,
            'ethnicity' => !empty($_POST['ethnicity']) ? trim($_POST['ethnicity']) : null,
            'province' => !empty($_POST['province']) ? trim($_POST['province']) : null,
            'ward' => !empty($_POST['ward']) ? trim($_POST['ward']) : null,
            'address_detail' => !empty($_POST['address_detail']) ? trim($_POST['address_detail']) : null,
            'school_name' => !empty($_POST['school_name']) ? trim($_POST['school_name']) : null,
            'school_province' => !empty($_POST['school_province']) ? trim($_POST['school_province']) : null,
            'school_ward' => !empty($_POST['school_ward']) ? trim($_POST['school_ward']) : null,
            'school_address_detail' => !empty($_POST['school_address_detail']) ? trim($_POST['school_address_detail']) : null,
            'priority_area' => !empty($_POST['priority_area']) ? trim($_POST['priority_area']) : null,
            'academic_performance' => !empty($_POST['academic_performance']) ? trim($_POST['academic_performance']) : null,
            'conduct' => !empty($_POST['conduct']) ? trim($_POST['conduct']) : null,
            'graduation_year' => !empty($_POST['graduation_year']) ? (int)$_POST['graduation_year'] : null,
            'priority_object' => !empty($_POST['priority_object']) ? trim($_POST['priority_object']) : null,
            'prev_degree_level' => !empty($_POST['prev_degree_level']) ? trim($_POST['prev_degree_level']) : null,
            'prev_major' => !empty($_POST['prev_major']) ? trim($_POST['prev_major']) : null,
            'prev_admission_date' => !empty($_POST['prev_admission_date']) ? $_POST['prev_admission_date'] : null,
            'prev_graduation_date' => !empty($_POST['prev_graduation_date']) ? $_POST['prev_graduation_date'] : null,
            'prev_graduation_rank' => !empty($_POST['prev_graduation_rank']) ? trim($_POST['prev_graduation_rank']) : null,
            'prev_diploma_school' => !empty($_POST['prev_diploma_school']) ? trim($_POST['prev_diploma_school']) : null,
            'prev_diploma_date' => !empty($_POST['prev_diploma_date']) ? $_POST['prev_diploma_date'] : null,
            'current_position' => !empty($_POST['current_position']) ? trim($_POST['current_position']) : null,
            'current_workplace' => !empty($_POST['current_workplace']) ? trim($_POST['current_workplace']) : null,
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

    if ($_POST['action'] === 'reset_password') {
        $user_id = $_POST['user_id'];
        $new_password = trim($_POST['new_password']);
        if (strlen($new_password) < 6) {
            $_SESSION['err'] = "Mật khẩu mới phải có ít nhất 6 ký tự.";
        } else {
            $res = $supabaseAdmin->updateAuthUser($user_id, ['password' => $new_password]);
            if (in_array($res['code'], [200, 204])) {
                $_SESSION['msg'] = "Cấp lại mật khẩu thành công!";
            } else {
                $_SESSION['err'] = "Lỗi cấp lại mật khẩu: " . json_encode($res['data'] ?? $res['error'] ?? 'Unknown');
            }
        }
        header("Location: users.php"); exit;
    }
}

// Lay danh sach tai khoan thi sinh
$usersRes = $supabaseAdmin->select('user_profiles', 'order=created_at.desc');
$users = ($usersRes['code'] == 200) ? $usersRes['data'] : [];

// Encode all users as JSON for JS detail modal
$usersJson = json_encode($users, JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/logo.png">
    <title>Quản lý Thí sinh - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/public.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/combo.css">
    <style>
        .detail-label { font-size: .75rem; text-transform: uppercase; font-weight: 700; color: #6b7280; letter-spacing: .5px; margin-bottom: 2px; }
        .detail-value { font-size: .9rem; color: #1e293b; min-height: 1.4em; }
        .detail-value.empty { color: #94a3b8; font-style: italic; }
        .section-title { font-size: .85rem; font-weight: 700; color: #fff; background: #1A3A6E; padding: 6px 14px; border-radius: 6px; margin: 16px 0 12px; }
        .section-title:first-of-type { margin-top: 0; }
        .btn-brand { background-color: var(--brand, #1A3A6E); color: white; border: none; }
        .btn-brand:hover { background-color: #15305a; color: white; }
        .btn-outline-brand { color: var(--brand, #1A3A6E); border-color: var(--brand, #1A3A6E); }
        .btn-outline-brand:hover { background-color: var(--brand, #1A3A6E); color: white; }
        .password-toggle { cursor: pointer; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="container-fluid p-0">
    <div class="row m-0">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold mb-0 text-brand">Quản lý Thông tin Thí sinh</h3>
            </div>

            <?php include __DIR__ . '/../includes/flash_messages.php'; ?>

            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle w-100" id="usersTable">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th>Họ và Tên</th>
                                    <th>Thông tin liên hệ</th>
                                    <th>CMND / CCCD</th>
                                    <th>Ngày sinh</th>
                                    <th>Giới tính</th>
                                    <th>Địa chỉ</th>
                                    <th>Ngày đăng ký</th>
                                    <th class="text-end">Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $idx => $u): ?>
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
                                        <span class="small"><?php echo htmlspecialchars($u['gender'] ?? 'N/A'); ?></span>
                                    </td>
                                    <td>
                                        <?php
                                            $addressParts = array_filter([
                                                $u['address_detail'] ?? '',
                                                $u['ward'] ?? '',
                                                $u['province'] ?? ''
                                            ]);
                                            $fullAddress = !empty($addressParts) ? implode(', ', $addressParts) : 'N/A';
                                        ?>
                                        <span class="small text-muted" style="max-width: 200px; display: inline-block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($fullAddress); ?>">
                                            <?php echo htmlspecialchars($fullAddress); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="d-block small text-muted"><?php echo date('d/m/Y H:i', strtotime($u['created_at'])); ?></span>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group shadow-sm">
                                            <button class="btn btn-sm btn-outline-brand" onclick="viewUser(<?php echo $idx; ?>)" title="Xem chi tiết"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-warning" onclick="editUser(<?php echo $idx; ?>)" title="Sửa hồ sơ"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary" onclick="resetPassword('<?php echo $u['id']; ?>', '<?php echo htmlspecialchars($u['full_name'] ?? '', ENT_QUOTES); ?>')" title="Cấp lại mật khẩu"><i class="bi bi-key"></i></button>
                                        </div>
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

<?php include __DIR__ . '/includes/user_modals.php'; ?>



<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="<?php echo BASE_URL; ?>/assets/js/address_combo.js"></script>
<script>
    const usersData = <?php echo $usersJson; ?>;
    let currentViewIdx = null;

    // Helper: display value or N/A
    function dv(val) {
        if (val === null || val === undefined || val === '') return '<span class="detail-value empty">Chưa cập nhật</span>';
        return '<span class="detail-value">' + escHtml(String(val)) + '</span>';
    }
    function escHtml(str) {
        const d = document.createElement('div'); d.textContent = str; return d.innerHTML;
    }
    function formatDate(d) {
        if (!d) return null;
        const parts = d.split('-');
        if (parts.length === 3) return parts[2] + '/' + parts[1] + '/' + parts[0];
        return d;
    }

    // VIEW modal
    function viewUser(idx) {
        currentViewIdx = idx;
        const u = usersData[idx];
        const addressParts = [u.address_detail, u.ward, u.province].filter(Boolean);
        const fullAddress = addressParts.length ? addressParts.join(', ') : null;
        const schoolAddress = [u.school_address_detail, u.school_ward, u.school_province].filter(Boolean).join(', ') || null;

        let html = '';

        // Section 1: Thông tin cá nhân
        html += '<div class="section-title"><i class="bi bi-person-vcard me-2"></i>Thông tin định danh</div>';
        html += '<div class="row g-3">';
        html += `<div class="col-md-4"><div class="detail-label">Họ và Tên</div>${dv(u.full_name)}</div>`;
        html += `<div class="col-md-4"><div class="detail-label">CMND/CCCD</div>${dv(u.identity_card)}</div>`;
        html += `<div class="col-md-4"><div class="detail-label">Ngày sinh</div>${dv(formatDate(u.date_of_birth))}</div>`;
        html += `<div class="col-md-4"><div class="detail-label">Giới tính</div>${dv(u.gender)}</div>`;
        html += `<div class="col-md-4"><div class="detail-label">Dân tộc</div>${dv(u.ethnicity)}</div>`;
        html += `<div class="col-md-4"><div class="detail-label">Username</div>${dv(u.username)}</div>`;
        html += '</div>';

        // Section 2: Liên hệ
        html += '<div class="section-title mt-3"><i class="bi bi-telephone me-2"></i>Thông tin liên hệ</div>';
        html += '<div class="row g-3">';
        html += `<div class="col-md-6"><div class="detail-label">Email liên hệ</div>${dv(u.contact_email)}</div>`;
        html += `<div class="col-md-6"><div class="detail-label">Số điện thoại</div>${dv(u.phone_number)}</div>`;
        html += '</div>';

        // Section 3: Địa chỉ
        html += '<div class="section-title mt-3"><i class="bi bi-house-door me-2"></i>Địa chỉ thường trú</div>';
        html += '<div class="row g-3">';
        html += `<div class="col-md-4"><div class="detail-label">Tỉnh / TP</div>${dv(u.province)}</div>`;
        html += `<div class="col-md-4"><div class="detail-label">Phường / Xã</div>${dv(u.ward)}</div>`;
        html += `<div class="col-md-4"><div class="detail-label">Địa chỉ chi tiết</div>${dv(u.address_detail)}</div>`;
        html += '</div>';

        // Section 4: Trường THPT
        html += '<div class="section-title mt-3"><i class="bi bi-mortarboard me-2"></i>Trường THPT</div>';
        html += '<div class="row g-3">';
        html += `<div class="col-md-4"><div class="detail-label">Tên trường</div>${dv(u.school_name)}</div>`;
        html += `<div class="col-md-4"><div class="detail-label">Tỉnh (Trường)</div>${dv(u.school_province)}</div>`;
        html += `<div class="col-md-4"><div class="detail-label">Phường/Xã (Trường)</div>${dv(u.school_ward)}</div>`;
        html += `<div class="col-md-4"><div class="detail-label">Địa chỉ (Trường)</div>${dv(u.school_address_detail)}</div>`;
        html += `<div class="col-md-4"><div class="detail-label">Năm TN THPT</div>${dv(u.graduation_year)}</div>`;
        html += `<div class="col-md-4"><div class="detail-label">Học lực lớp 12</div>${dv(u.academic_performance)}</div>`;
        html += `<div class="col-md-4"><div class="detail-label">Hạnh kiểm lớp 12</div>${dv(u.conduct)}</div>`;
        html += '</div>';

        // Section 5: Ưu tiên
        html += '<div class="section-title mt-3"><i class="bi bi-star me-2"></i>Ưu tiên</div>';
        html += '<div class="row g-3">';
        html += `<div class="col-md-6"><div class="detail-label">Khu vực ưu tiên</div>${dv(u.priority_area)}</div>`;
        html += `<div class="col-md-6"><div class="detail-label">Đối tượng ưu tiên</div>${dv(u.priority_object)}</div>`;
        html += '</div>';

        // Section 6: Đã TN
        html += '<div class="section-title mt-3"><i class="bi bi-award me-2"></i>Đã tốt nghiệp</div>';
        html += '<div class="row g-3">';
        html += `<div class="col-md-4"><div class="detail-label">Trình độ</div>${dv(u.prev_degree_level)}</div>`;
        html += `<div class="col-md-4"><div class="detail-label">Ngành</div>${dv(u.prev_major)}</div>`;
        html += `<div class="col-md-4"><div class="detail-label">Xếp loại TN</div>${dv(u.prev_graduation_rank)}</div>`;
        html += `<div class="col-md-4"><div class="detail-label">Ngày trúng tuyển</div>${dv(formatDate(u.prev_admission_date))}</div>`;
        html += `<div class="col-md-4"><div class="detail-label">Ngày tốt nghiệp</div>${dv(formatDate(u.prev_graduation_date))}</div>`;
        html += `<div class="col-md-4"><div class="detail-label">Bằng TN do trường cấp</div>${dv(u.prev_diploma_school)}</div>`;
        html += `<div class="col-md-4"><div class="detail-label">Cấp ngày</div>${dv(formatDate(u.prev_diploma_date))}</div>`;
        html += '</div>';

        // Section 7: Công tác
        html += '<div class="section-title mt-3"><i class="bi bi-briefcase me-2"></i>Công tác hiện tại</div>';
        html += '<div class="row g-3">';
        html += `<div class="col-md-6"><div class="detail-label">Chức vụ</div>${dv(u.current_position)}</div>`;
        html += `<div class="col-md-6"><div class="detail-label">Cơ quan</div>${dv(u.current_workplace)}</div>`;
        html += '</div>';

        // Metadata
        html += '<hr class="my-3">';
        html += '<div class="row g-3">';
        html += `<div class="col-md-4"><div class="detail-label">ID</div><span class="detail-value small text-muted">${escHtml(u.id)}</span></div>`;
        html += `<div class="col-md-4"><div class="detail-label">Ngày đăng ký</div>${dv(u.created_at ? new Date(u.created_at).toLocaleString('vi-VN') : null)}</div>`;
        html += `<div class="col-md-4"><div class="detail-label">Cập nhật lần cuối</div>${dv(u.updated_at ? new Date(u.updated_at).toLocaleString('vi-VN') : null)}</div>`;
        html += '</div>';

        document.getElementById('viewUserBody').innerHTML = html;
        new bootstrap.Modal(document.getElementById('viewUserModal')).show();
    }

    // View to Edit transition
    document.getElementById('btnViewToEdit').addEventListener('click', function() {
        bootstrap.Modal.getInstance(document.getElementById('viewUserModal')).hide();
        setTimeout(() => editUser(currentViewIdx), 300);
    });

    // EDIT modal
    function editUser(idx) {
        const u = usersData[idx];
        document.getElementById('edit_user_id').value = u.id;

        const fields = [
            'full_name', 'identity_card', 'date_of_birth', 'gender', 'ethnicity',
            'contact_email', 'phone_number',
            'address_detail',
            'school_name', 'school_address_detail',
            'graduation_year', 'academic_performance', 'conduct',
            'priority_area', 'priority_object',
            'prev_degree_level', 'prev_major', 'prev_graduation_rank',
            'prev_admission_date', 'prev_graduation_date',
            'prev_diploma_school', 'prev_diploma_date',
            'current_position', 'current_workplace'
        ];

        fields.forEach(f => {
            const el = document.getElementById('edit_' + f);
            if (el) el.value = u[f] ?? '';
        });

        // Restore combobox address values
        restoreAddressCombo('edit', u.province || '', u.ward || '');
        restoreAddressCombo('editSchool', u.school_province || '', u.school_ward || '');

        new bootstrap.Modal(document.getElementById('editUserModal')).show();
    }

    // RESET PASSWORD modal
    function resetPassword(userId, userName) {
        document.getElementById('reset_user_id').value = userId;
        document.getElementById('reset_user_name').textContent = userName;
        document.getElementById('new_password').value = '';
        new bootstrap.Modal(document.getElementById('resetPasswordModal')).show();
    }

    function togglePassword() {
        const input = document.getElementById('new_password');
        const icon = document.getElementById('togglePasswordIcon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('bi-eye', 'bi-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('bi-eye-slash', 'bi-eye');
        }
    }

    function generatePassword() {
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789@#$!';
        let pwd = '';
        for (let i = 0; i < 10; i++) pwd += chars.charAt(Math.floor(Math.random() * chars.length));
        const input = document.getElementById('new_password');
        input.value = pwd;
        input.type = 'text';
        document.getElementById('togglePasswordIcon').classList.replace('bi-eye', 'bi-eye-slash');
    }

    // Address Combos — sử dụng module AddressCombo dùng chung
    const API_BASE = '<?php echo BASE_URL; ?>/api/dia_danh.php';
    AddressCombo.init(API_BASE, {
        comboKey: 'editProvinceInput',
        provinceInputId: 'editProvinceInput', provinceDropdownId: 'editProvinceDropdown',
        provinceSearchId: 'editProvinceSearch', provinceListId: 'editProvinceList',
        provinceHiddenId: 'edit_province', provinceClearId: 'editProvinceClear',
        wardInputId: 'editWardInput', wardDropdownId: 'editWardDropdown',
        wardSearchId: 'editWardSearch', wardListId: 'editWardList',
        wardHiddenId: 'edit_ward', wardClearId: 'editWardClear'
    });
    AddressCombo.init(API_BASE, {
        comboKey: 'editSchoolProvinceInput',
        provinceInputId: 'editSchoolProvinceInput', provinceDropdownId: 'editSchoolProvinceDropdown',
        provinceSearchId: 'editSchoolProvinceSearch', provinceListId: 'editSchoolProvinceList',
        provinceHiddenId: 'edit_school_province', provinceClearId: 'editSchoolProvinceClear',
        wardInputId: 'editSchoolWardInput', wardDropdownId: 'editSchoolWardDropdown',
        wardSearchId: 'editSchoolWardSearch', wardListId: 'editSchoolWardList',
        wardHiddenId: 'edit_school_ward', wardClearId: 'editSchoolWardClear'
    });

    // Wrapper cho editUser restore
    window.restoreAddressCombo = function(prefix, savedProvince, savedWard) {
        const key = prefix === 'edit' ? 'editProvinceInput' : 'editSchoolProvinceInput';
        AddressCombo.restore(key, savedProvince, savedWard, API_BASE);
    };

    // DataTable
    $(document).ready(function() {
        $('#usersTable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json"
            },
            "order": [[6, "desc"]],
            "pageLength": 25,
            "drawCallback": function() {
                $('.dataTables_paginate > .pagination').addClass('pagination-sm mt-3');
            }
        });
    });
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
