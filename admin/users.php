<?php
require_once __DIR__ . '/../config/supabase.php';

// admin/users.php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ' . BASE_URL . '/admin/login.php');
    exit;
}
require_once __DIR__ . '/../lib/SupabaseClient.php';
$supabaseAdmin = new SupabaseClient('service');

$message = $_SESSION['msg'] ?? '';
$error = $_SESSION['err'] ?? '';
unset($_SESSION['msg'], $_SESSION['err']);

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
    <title>Quản lý Thí sinh - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/public.css">
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
        /* Combobox styles */
        .combo-wrapper { position: relative; }
        .combo-wrapper .combo-input {
            border-radius: 6px; border: 1px solid #cbd5e1; min-height: 38px; width: 100%;
            padding: .375rem .75rem; font-size: .875rem;
            transition: border-color .15s ease, box-shadow .15s ease;
            background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 16 16'%3E%3Cpath fill='%2364748b' d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E") no-repeat right .75rem center;
            padding-right: 2rem; cursor: pointer;
        }
        .combo-wrapper .combo-input:focus { outline: none; border-color: var(--brand, #1A3A6E); box-shadow: 0 0 0 2px rgba(26, 58, 110, .15); }
        .combo-wrapper .combo-input:disabled { background-color: #f8fafc; cursor: not-allowed; color: #94a3b8; }
        .combo-dropdown {
            display: none; position: absolute; top: calc(100% + 4px); left: 0; right: 0;
            background: #fff; border: 1px solid #cbd5e1; border-radius: 8px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, .1); max-height: 230px; overflow-y: auto; z-index: 1060;
        }
        .combo-dropdown.open { display: block; }
        .combo-dropdown .combo-search { position: sticky; top: 0; padding: 8px; background: #fff; border-bottom: 1px solid #e2e8f0; }
        .combo-dropdown .combo-search input { width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 10px; font-size: .8rem; outline: none; }
        .combo-dropdown .combo-search input:focus { border-color: var(--brand, #1A3A6E); }
        .combo-option { padding: 8px 14px; cursor: pointer; font-size: .85rem; transition: background .1s; }
        .combo-option:hover, .combo-option.active { background: #eff6ff; color: var(--brand, #1A3A6E); }
        .combo-option.no-result { color: #94a3b8; cursor: default; font-style: italic; }
        .combo-clear {
            position: absolute; right: 28px; top: 50%; transform: translateY(-50%);
            cursor: pointer; color: #94a3b8; font-size: .85rem; display: none; line-height: 1; z-index: 1;
        }
        .combo-clear:hover { color: #ef4444; }
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

            <?php if($message): ?><div class="alert alert-success alert-dismissible fade show border-0 shadow-sm"><?php echo $message; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
            <?php if($error): ?><div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm"><?php echo $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

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
                                        <button class="btn btn-sm btn-outline-brand" onclick="viewUser(<?php echo $idx; ?>)"><i class="bi bi-eye"></i> Xem</button>
                                        <button class="btn btn-sm btn-outline-warning" onclick="editUser(<?php echo $idx; ?>)">Sửa</button>
                                        <button class="btn btn-sm btn-outline-secondary" onclick="resetPassword('<?php echo $u['id']; ?>', '<?php echo htmlspecialchars($u['full_name'] ?? '', ENT_QUOTES); ?>')"><i class="bi bi-key"></i> Cấp MK</button>
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

<!-- Modal: View User Detail -->
<div class="modal fade" id="viewUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-brand text-white border-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-lines-fill me-2"></i>Chi tiết Thí sinh</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="viewUserBody">
                <!-- JS will populate -->
            </div>
            <div class="modal-footer border-0 p-3">
                <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-brand px-4" id="btnViewToEdit"><i class="bi bi-pencil-square me-1"></i>Chỉnh sửa</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Edit User -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form method="POST" class="modal-content border-0 shadow">
            <div class="modal-header bg-brand text-white border-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Chỉnh sửa Thông tin Thí sinh</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="action" value="update_user">
                <input type="hidden" name="user_id" id="edit_user_id">

                <!-- Section 1: Thông tin định danh -->
                <div class="section-title"><i class="bi bi-person-vcard me-2"></i>Thông tin định danh</div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold small text-muted">Họ và Tên <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold small text-muted">CMND/CCCD</label>
                        <input type="text" name="identity_card" id="edit_identity_card" class="form-control">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold small text-muted">Ngày sinh</label>
                        <input type="date" name="date_of_birth" id="edit_date_of_birth" class="form-control">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold small text-muted">Giới tính</label>
                        <select name="gender" id="edit_gender" class="form-select">
                            <option value="">-- Chọn --</option>
                            <option value="Nam">Nam</option>
                            <option value="Nữ">Nữ</option>
                            <option value="Khác">Khác</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold small text-muted">Dân tộc</label>
                        <input type="text" name="ethnicity" id="edit_ethnicity" class="form-control">
                    </div>
                </div>

                <!-- Section 2: Liên hệ -->
                <div class="section-title"><i class="bi bi-telephone me-2"></i>Thông tin liên hệ</div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold small text-muted">Email liên hệ</label>
                        <input type="email" name="contact_email" id="edit_contact_email" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold small text-muted">Số điện thoại</label>
                        <input type="text" name="phone_number" id="edit_phone_number" class="form-control">
                    </div>
                </div>

                <!-- Section 3: Địa chỉ thường trú -->
                <div class="section-title"><i class="bi bi-house-door me-2"></i>Địa chỉ thường trú</div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold small text-muted">Tỉnh / Thành phố</label>
                        <div class="combo-wrapper" id="editProvinceWrapper">
                            <span class="combo-clear" id="editProvinceClear" title="Xóa">&times;</span>
                            <input type="text" class="combo-input" id="editProvinceInput" placeholder="-- Chọn Tỉnh/TP --" readonly>
                            <div class="combo-dropdown" id="editProvinceDropdown">
                                <div class="combo-search"><input type="text" id="editProvinceSearch" placeholder="🔍 Tìm tỉnh/thành..." autocomplete="off"></div>
                                <div id="editProvinceList"></div>
                            </div>
                        </div>
                        <input type="hidden" name="province" id="edit_province">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold small text-muted">Phường / Xã</label>
                        <div class="combo-wrapper" id="editWardWrapper">
                            <span class="combo-clear" id="editWardClear" title="Xóa">&times;</span>
                            <input type="text" class="combo-input" id="editWardInput" placeholder="-- Chọn Phường/Xã --" readonly disabled>
                            <div class="combo-dropdown" id="editWardDropdown">
                                <div class="combo-search"><input type="text" id="editWardSearch" placeholder="🔍 Tìm phường/xã..." autocomplete="off"></div>
                                <div id="editWardList"></div>
                            </div>
                        </div>
                        <input type="hidden" name="ward" id="edit_ward">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold small text-muted">Địa chỉ chi tiết</label>
                        <input type="text" name="address_detail" id="edit_address_detail" class="form-control">
                    </div>
                </div>

                <!-- Section 4: Trường THPT -->
                <div class="section-title"><i class="bi bi-mortarboard me-2"></i>Trường THPT</div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold small text-muted">Tên trường THPT</label>
                        <input type="text" name="school_name" id="edit_school_name" class="form-control">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold small text-muted">Tỉnh (Trường)</label>
                        <div class="combo-wrapper" id="editSchoolProvinceWrapper">
                            <span class="combo-clear" id="editSchoolProvinceClear" title="Xóa">&times;</span>
                            <input type="text" class="combo-input" id="editSchoolProvinceInput" placeholder="-- Chọn Tỉnh/TP --" readonly>
                            <div class="combo-dropdown" id="editSchoolProvinceDropdown">
                                <div class="combo-search"><input type="text" id="editSchoolProvinceSearch" placeholder="🔍 Tìm tỉnh/thành..." autocomplete="off"></div>
                                <div id="editSchoolProvinceList"></div>
                            </div>
                        </div>
                        <input type="hidden" name="school_province" id="edit_school_province">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold small text-muted">Phường/Xã (Trường)</label>
                        <div class="combo-wrapper" id="editSchoolWardWrapper">
                            <span class="combo-clear" id="editSchoolWardClear" title="Xóa">&times;</span>
                            <input type="text" class="combo-input" id="editSchoolWardInput" placeholder="-- Chọn Phường/Xã --" readonly disabled>
                            <div class="combo-dropdown" id="editSchoolWardDropdown">
                                <div class="combo-search"><input type="text" id="editSchoolWardSearch" placeholder="🔍 Tìm phường/xã..." autocomplete="off"></div>
                                <div id="editSchoolWardList"></div>
                            </div>
                        </div>
                        <input type="hidden" name="school_ward" id="edit_school_ward">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold small text-muted">Địa chỉ chi tiết (Trường)</label>
                        <input type="text" name="school_address_detail" id="edit_school_address_detail" class="form-control">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold small text-muted">Năm tốt nghiệp</label>
                        <input type="number" name="graduation_year" id="edit_graduation_year" class="form-control">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold small text-muted">Học lực lớp 12</label>
                        <select name="academic_performance" id="edit_academic_performance" class="form-select">
                            <option value="">-- Chọn --</option>
                            <option value="Giỏi">Giỏi</option>
                            <option value="Khá">Khá</option>
                            <option value="Trung bình">Trung bình</option>
                            <option value="Yếu">Yếu</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold small text-muted">Hạnh kiểm lớp 12</label>
                        <select name="conduct" id="edit_conduct" class="form-select">
                            <option value="">-- Chọn --</option>
                            <option value="Tốt">Tốt</option>
                            <option value="Khá">Khá</option>
                            <option value="Trung bình">Trung bình</option>
                            <option value="Yếu">Yếu</option>
                        </select>
                    </div>
                </div>

                <!-- Section 5: Ưu tiên -->
                <div class="section-title"><i class="bi bi-star me-2"></i>Ưu tiên</div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold small text-muted">Khu vực ưu tiên</label>
                        <select name="priority_area" id="edit_priority_area" class="form-select">
                            <option value="">-- Chọn --</option>
                            <option value="KV1">KV1</option>
                            <option value="KV2">KV2</option>
                            <option value="KV2-NT">KV2-NT</option>
                            <option value="KV3">KV3</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold small text-muted">Đối tượng ưu tiên</label>
                        <select name="priority_object" id="edit_priority_object" class="form-select">
                            <option value="">-- Không có --</option>
                            <option value="01">01 - Dân tộc thiểu số (KV1)</option>
                            <option value="02">02 - CN sản xuất ưu tú</option>
                            <option value="03">03 - Thương binh, Quân/CA</option>
                            <option value="04">04 - Con liệt sĩ, Con TB/BB (≥81%)</option>
                            <option value="05">05 - TNXP, Quân/CA xuất ngũ</option>
                            <option value="06">06 - DTTS ngoài KV1</option>
                            <option value="07">07 - Người KT nặng, LĐ/Nhà giáo/YT XS</option>
                        </select>
                    </div>
                </div>

                <!-- Section 6: Đã tốt nghiệp -->
                <div class="section-title"><i class="bi bi-award me-2"></i>Đã tốt nghiệp (nếu có)</div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold small text-muted">Trình độ đã TN</label>
                        <select name="prev_degree_level" id="edit_prev_degree_level" class="form-select">
                            <option value="">-- Chưa có --</option>
                            <option value="Trung cấp">Trung cấp</option>
                            <option value="Cao đẳng">Cao đẳng</option>
                            <option value="Đại học">Đại học</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold small text-muted">Ngành đã TN</label>
                        <input type="text" name="prev_major" id="edit_prev_major" class="form-control">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold small text-muted">Xếp loại TN</label>
                        <select name="prev_graduation_rank" id="edit_prev_graduation_rank" class="form-select">
                            <option value="">-- Chọn --</option>
                            <option value="Xuất sắc">Xuất sắc</option>
                            <option value="Giỏi">Giỏi</option>
                            <option value="Khá">Khá</option>
                            <option value="Trung bình khá">Trung bình khá</option>
                            <option value="Trung bình">Trung bình</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold small text-muted">Ngày trúng tuyển</label>
                        <input type="date" name="prev_admission_date" id="edit_prev_admission_date" class="form-control">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold small text-muted">Ngày tốt nghiệp</label>
                        <input type="date" name="prev_graduation_date" id="edit_prev_graduation_date" class="form-control">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold small text-muted">Bằng TN do trường cấp</label>
                        <input type="text" name="prev_diploma_school" id="edit_prev_diploma_school" class="form-control">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold small text-muted">Cấp ngày</label>
                        <input type="date" name="prev_diploma_date" id="edit_prev_diploma_date" class="form-control">
                    </div>
                </div>

                <!-- Section 7: Công tác hiện tại -->
                <div class="section-title"><i class="bi bi-briefcase me-2"></i>Công tác hiện tại</div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold small text-muted">Chức vụ</label>
                        <input type="text" name="current_position" id="edit_current_position" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold small text-muted">Cơ quan công tác</label>
                        <input type="text" name="current_workplace" id="edit_current_workplace" class="form-control">
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-brand px-4"><i class="bi bi-check-lg me-1"></i>Lưu thay đổi</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Reset Password -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow">
            <div class="modal-header bg-warning border-0">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-key me-2"></i>Cấp lại Mật khẩu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="user_id" id="reset_user_id">

                <div class="alert alert-info border-0 small">
                    <i class="bi bi-info-circle me-1"></i>
                    Đặt mật khẩu mới cho thí sinh: <strong id="reset_user_name"></strong>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted">Mật khẩu mới <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" name="new_password" id="new_password" class="form-control" required minlength="6" placeholder="Tối thiểu 6 ký tự">
                        <button class="btn btn-outline-secondary password-toggle" type="button" onclick="togglePassword()">
                            <i class="bi bi-eye" id="togglePasswordIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="generatePassword()">
                        <i class="bi bi-shuffle me-1"></i>Tạo mật khẩu ngẫu nhiên
                    </button>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-warning px-4 fw-bold"><i class="bi bi-check-lg me-1"></i>Xác nhận đổi mật khẩu</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
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

    // ============================================================
    // Combobox Address System (reused from profile.php pattern)
    // ============================================================
    (function() {
        const API_BASE = '<?php echo BASE_URL; ?>/api/dia_danh.php';
        let provincesCache = null;

        function closeAllCombos() {
            document.querySelectorAll('.combo-dropdown.open').forEach(d => d.classList.remove('open'));
        }
        document.addEventListener('click', e => {
            if (!e.target.closest('.combo-wrapper')) closeAllCombos();
        });

        function makeCombo({ triggerEl, dropdown, searchEl, listEl, clearEl, onClear }) {
            triggerEl.addEventListener('click', () => {
                if (triggerEl.disabled) return;
                const isOpen = dropdown.classList.contains('open');
                closeAllCombos();
                if (!isOpen) {
                    dropdown.classList.add('open');
                    searchEl.value = '';
                    searchEl.dispatchEvent(new Event('input'));
                    setTimeout(() => searchEl.focus(), 50);
                }
            });
            searchEl.addEventListener('input', () => {
                const q = searchEl.value.toLowerCase().trim();
                listEl.querySelectorAll('.combo-option').forEach(opt => {
                    opt.style.display = opt.textContent.toLowerCase().includes(q) ? '' : 'none';
                });
                const visible = [...listEl.querySelectorAll('.combo-option')].filter(o => o.style.display !== 'none');
                let noRes = listEl.querySelector('.no-result');
                if (!visible.length) {
                    if (!noRes) { noRes = document.createElement('div'); noRes.className = 'combo-option no-result'; noRes.textContent = 'Không tìm thấy kết quả'; listEl.appendChild(noRes); }
                } else { if (noRes) noRes.remove(); }
            });
            if (clearEl && onClear) clearEl.addEventListener('click', onClear);
        }

        function renderOptions(listEl, items, onSelect) {
            listEl.innerHTML = '';
            items.forEach(item => {
                const div = document.createElement('div');
                div.className = 'combo-option';
                div.textContent = item.name;
                div.dataset.code = item.code;
                div.addEventListener('click', () => { onSelect(item); closeAllCombos(); });
                listEl.appendChild(div);
            });
        }

        function initAddressCombo(prefix) {
            const pInput = document.getElementById(prefix + 'ProvinceInput');
            const pDropdown = document.getElementById(prefix + 'ProvinceDropdown');
            const pSearch = document.getElementById(prefix + 'ProvinceSearch');
            const pList = document.getElementById(prefix + 'ProvinceList');
            const pHidden = document.getElementById(prefix === 'edit' ? 'edit_province' : 'edit_school_province');
            const pClear = document.getElementById(prefix + 'ProvinceClear');

            const wInput = document.getElementById(prefix + 'WardInput');
            const wDropdown = document.getElementById(prefix + 'WardDropdown');
            const wSearch = document.getElementById(prefix + 'WardSearch');
            const wList = document.getElementById(prefix + 'WardList');
            const wHidden = document.getElementById(prefix === 'edit' ? 'edit_ward' : 'edit_school_ward');
            const wClear = document.getElementById(prefix + 'WardClear');

            function clearWard() { wInput.value = ''; wHidden.value = ''; wClear.style.display = 'none'; }
            function clearProvince() { pInput.value = ''; pHidden.value = ''; pClear.style.display = 'none'; clearWard(); wInput.disabled = true; }

            function selectProvince(p, skipWardReset) {
                pInput.value = p.name; pHidden.value = p.name; pClear.style.display = 'block';
                if (!skipWardReset) { clearWard(); wInput.disabled = false; }
                fetch(`${API_BASE}?action=wards&province_code=${encodeURIComponent(p.code)}`)
                    .then(r => r.json())
                    .then(wards => renderOptions(wList, wards, selectWard));
            }
            function selectWard(w) { wInput.value = w.name; wHidden.value = w.name; wClear.style.display = 'block'; }

            makeCombo({ triggerEl: pInput, dropdown: pDropdown, searchEl: pSearch, listEl: pList, clearEl: pClear, onClear: clearProvince });
            makeCombo({ triggerEl: wInput, dropdown: wDropdown, searchEl: wSearch, listEl: wList, clearEl: wClear, onClear: clearWard });

            // Load provinces
            const loadProvinces = () => {
                if (provincesCache) {
                    renderOptions(pList, provincesCache, p => selectProvince(p));
                    return Promise.resolve(provincesCache);
                }
                return fetch(API_BASE + '?action=provinces')
                    .then(r => r.json())
                    .then(provinces => {
                        provincesCache = provinces;
                        renderOptions(pList, provinces, p => selectProvince(p));
                        return provinces;
                    });
            };

            // Store references for restoring
            window['_combo_' + prefix] = { selectProvince, selectWard, loadProvinces, clearProvince };
            loadProvinces();
        }

        // Restore saved values in combos
        window.restoreAddressCombo = function(prefix, savedProvince, savedWard) {
            const combo = window['_combo_' + prefix];
            if (!combo) return;
            combo.clearProvince();
            if (!savedProvince) return;

            combo.loadProvinces().then(provinces => {
                const match = provinces.find(p => p.name === savedProvince);
                if (match) {
                    combo.selectProvince(match, true);
                    if (savedWard) {
                        fetch(`${API_BASE}?action=wards&province_code=${encodeURIComponent(match.code)}`)
                            .then(r => r.json())
                            .then(wards => {
                                const wList = document.getElementById(prefix + 'WardList');
                                const renderOpts = (list, items, onSel) => {
                                    list.innerHTML = '';
                                    items.forEach(item => {
                                        const div = document.createElement('div');
                                        div.className = 'combo-option';
                                        div.textContent = item.name;
                                        div.dataset.code = item.code;
                                        div.addEventListener('click', () => { onSel(item); closeAllCombos(); });
                                        list.appendChild(div);
                                    });
                                };
                                renderOpts(wList, wards, combo.selectWard);
                                document.getElementById(prefix + 'WardInput').disabled = false;
                                const mw = wards.find(w => w.name === savedWard);
                                if (mw) combo.selectWard(mw);
                            });
                    }
                }
            });
        };

        // Initialize both address combos
        initAddressCombo('edit');
        initAddressCombo('editSchool');
    })();

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
