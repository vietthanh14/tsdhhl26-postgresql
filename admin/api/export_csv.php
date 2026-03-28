<?php
// admin/api/export_csv.php — Xuất CSV danh sách hồ sơ xét tuyển
require_once __DIR__ . '/_guard.php';

try {
    $supabaseAdmin = new SupabaseClient('service');
    
    // 1. Lấy dữ liệu từ Supabase 
    $query = 'select=*,admission_periods(name),majors(major_name,education_levels(name)),admission_methods(method_name,application_fee)&order=submitted_at.desc';
    $appsRes = $supabaseAdmin->select('applications', $query);
    if ($appsRes['code'] != 200) {
        throw new Exception("Lỗi khi lấy dữ liệu applications từ Supabase: " . json_encode($appsRes['data'] ?? []));
    }
    $applications = $appsRes['data'];

    // Lấy Profile để map thông tin user
    $usersRes = $supabaseAdmin->select('user_profiles', 'select=id,full_name,identity_card,phone_number,contact_email,date_of_birth,gender,ethnicity,province,ward,address_detail,school_name,school_province,priority_area,priority_object,graduation_year,academic_performance,conduct');
    $userProfilesMap = [];
    if ($usersRes['code'] == 200 && is_array($usersRes['data'])) {
        foreach ($usersRes['data'] as $u) {
            $userProfilesMap[$u['id']] = $u;
        }
    }
    
    $filename = 'Danh_sach_Hoso_' . date('Y_m_d_His') . '.csv';
    $exportDir = __DIR__ . '/../../uploads/exports';
    if (!is_dir($exportDir)) {
        mkdir($exportDir, 0777, true);
    }
    
    // Xoá file csv cũ hơn 1 ngày
    foreach (glob($exportDir . '/*.csv') as $file) {
        if (is_file($file) && time() - filemtime($file) >= 86400) {
            unlink($file);
        }
    }

    $filepath = $exportDir . '/' . $filename;
    $fileUrl = '../../uploads/exports/' . $filename; 

    $output = fopen($filepath, 'w');
    fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM

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
        $appIdStr = '="' . ($app['id'] ?? '') . '"';
        $statusText = $app['status'] == 'APPROVED' ? 'Hợp lệ' : ($app['status'] == 'REJECTED' ? 'Từ chối' : 'Chờ duyệt');
        $paymentText = ($app['payment_status'] ?? '') == 'PAID' ? 'Đã TT' : 'Chưa TT';

        fputcsv($output, [
            $stt++, $statusText, $user['full_name'] ?? '', $dob, $genderText,
            $cmnd, $user['ethnicity'] ?? '', $user['phone_number'] ?? '',
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

    if (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'success', 'filename' => $filename, 'file_url' => $fileUrl]);
    exit;

} catch (Exception $e) {
    if (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}
