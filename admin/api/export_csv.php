<?php
// admin/api/export_csv.php — Xuất CSV danh sách hồ sơ xét tuyển
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../../lib/RateLimiter.php';

// Kiểm tra Rate Limit: Cấm xuất file liên tục (Tối đa 5 lần mỗi 30 phút = 1800 giây)
if (!RateLimiter::checkSessionLimit('export_csv', 5, 1800)) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => 'Bạn đã xuất file quá 5 lần trong 30 phút. Vui lòng thử lại sau để bảo vệ máy chủ.']);
    exit;
}

try {
    $supabaseAdmin = new DatabaseClient('service');
    
    // 1. Lấy dữ liệu từ Supabase 
    $query = 'select=*,admission_periods(name),majors(major_name,education_levels(name)),admission_methods(method_name,application_fee)&order=submitted_at.desc';
    $appsRes = $supabaseAdmin->select('applications', $query);
    if ($appsRes['code'] != 200) {
        throw new Exception("Lỗi khi lấy dữ liệu applications từ Supabase: " . json_encode($appsRes['data'] ?? []));
    }
    $applications = $appsRes['data'];

    // Lấy Profile để map thông tin user
    $userProfilesMap = $supabaseAdmin->fetchUserProfilesMap(
        array_column($applications, 'user_id'),
        'id,full_name,identity_card,phone_number,contact_email,date_of_birth,gender,ethnicity,province,ward,address_detail,school_name,school_province,priority_area,priority_object,graduation_year,academic_performance,conduct'
    );
    
    $filename = 'Danh_sach_Hoso_' . date('Y_m_d_His') . '.csv';
    
    // Xoá bộ nhớ đệm (Clean output buffer)
    if (ob_get_level()) ob_end_clean();
    
    // Đặt Headers tải file CSV trực tiếp (Stream)
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    // Ghi thẳng dữ liệu tĩnh ra luồng mạng
    $output = fopen('php://output', 'w');
    fputs($output, "\xEF\xBB\xBF"); // BOM cho Excel


    fputcsv($output, [
        'STT', 'Trạng thái hồ sơ', 'Họ và tên', 'Ngày sinh', 'Giới tính',
        'CMND/CCCD', 'Dân tộc', 'Số điện thoại', 'Email', 'Địa chỉ',
        'Tỉnh/TP', 'Quận/Huyện', 'Trường THPT', 'Tỉnh trường', 'Năm TN',
        'Học lực', 'Hạnh kiểm', 'KV Ưu tiên', 'ĐT Ưu tiên', 'Hệ đào tạo',
        'Tên ngành', 'Mã ngành', 'Phương thức XT', 'Mã PTXT', 'Nguyện vọng',
        'Kỳ tuyển sinh', 'Lệ phí', 'Trạng thái thanh toán', 'Ghi chú admin',
        'Mã hồ sơ (Hệ thống)', 'Ngày nộp'
    ], ';');

    $stt = 1;
    foreach ($applications as $app) {
        $user = $userProfilesMap[$app['user_id']] ?? [];
        $majorName = $app['majors']['major_name'] ?? '';
        $levelName = $app['majors']['education_levels']['name'] ?? '';
        $methodName = $app['admission_methods']['method_name'] ?? '';
        $feeAmount = $app['admission_methods']['application_fee'] ?? $app['fee_amount'] ?? 0;
        $periodName = $app['admission_periods']['name'] ?? '';
        
        $dob = !empty($user['date_of_birth']) ? date('d/m/Y', strtotime($user['date_of_birth'])) : '';
        $genderText = ($user['gender'] ?? '') === 'male' ? 'Nam' : (($user['gender'] ?? '') === 'female' ? 'Nữ' : '');
        $cmnd = '="' . ($user['identity_card'] ?? '') . '"';
        $phone = '="' . ($user['phone_number'] ?? '') . '"';
        $appIdStr = '="' . ($app['id'] ?? '') . '"';
        $statusText = $app['status'] == 'APPROVED' ? 'Hợp lệ' : ($app['status'] == 'REJECTED' ? 'Từ chối' : 'Chờ duyệt');
        $paymentText = ($app['payment_status'] ?? '') == 'PAID' ? 'Đã TT' : 'Chưa TT';

        fputcsv($output, [
            $stt++, $statusText, $user['full_name'] ?? '', $dob, $genderText,
            $cmnd, $user['ethnicity'] ?? '', $phone,
            $user['contact_email'] ?? '', $user['address_detail'] ?? '',
            $user['province'] ?? '', $user['ward'] ?? '',
            $user['school_name'] ?? '', $user['school_province'] ?? '',
            $user['graduation_year'] ?? '', $user['academic_performance'] ?? '',
            $user['conduct'] ?? '', $user['priority_area'] ?? '',
            $user['priority_object'] ?? '', $levelName, $majorName,
            $app['major_id'] ?? '', $methodName, $app['admission_method_id'] ?? '',
            $app['priority'] ?? 1, $periodName, $feeAmount, $paymentText,
            $app['admin_notes'] ?? '', $appIdStr,
            date('d/m/Y H:i', strtotime($app['submitted_at']))
        ], ';');
    }

    fclose($output);
    exit;

} catch (Exception $e) {
    if (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}
