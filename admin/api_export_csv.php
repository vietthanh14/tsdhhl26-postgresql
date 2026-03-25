<?php
// admin/api_export_csv.php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo "Unauthorized";
    exit;
}

require_once __DIR__ . '/../lib/SupabaseClient.php';

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
    $exportDir = __DIR__ . '/../uploads/exports';
    if (!is_dir($exportDir)) {
        mkdir($exportDir, 0777, true);
    }
    
    // Quét thư mục để xoá bớt file csv cũ, tránh rác server
    $files = glob($exportDir . '/*.csv');
    $now   = time();
    foreach ($files as $file) {
        if (is_file($file)) {
            // Xóa file cũ hơn 1 ngày
            if ($now - filemtime($file) >= 24 * 60 * 60) {
                unlink($file);
            }
        }
    }

    $filepath = $exportDir . '/' . $filename;
    
    // Đường dẫn tương đối dùng trên JS (nằm ngang hàng với thư mục admin)
    // Nếu ứng dụng chạy ở /tsdhhl26/ thì /tsdhhl26/uploads/exports/...
    $fileUrl = '../uploads/exports/' . $filename; 

    // Mở file vật lý để ghi
    $output = fopen($filepath, 'w');
    
    // Ghi BOM để hỗ trợ Unicode tiếng Việt
    fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Tiêu đề cột
    // Dùng dấu chấm phẩy (;) để Excel tiếng Việt tự động chia cột chuẩn xác
    fputcsv($output, [
        'STT', 
        'Trạng thái hồ sơ', 
        'Họ và tên', 
        'Ngày sinh', 
        'Giới tính',
        'CMND/CCCD', 
        'Dân tộc',
        'Số điện thoại',
        'Email',
        'Địa chỉ',
        'Tỉnh/TP',
        'Quận/Huyện',
        'Trường THPT',
        'Tỉnh trường',
        'Năm TN',
        'Học lực',
        'Hạnh kiểm',
        'KV Ưu tiên',
        'ĐT Ưu tiên', 
        'Hệ đào tạo',
        'Tên ngành',
        'Mã ngành',
        'Phương thức XT',
        'Mã PTXT',
        'Nguyện vọng',
        'Kỳ tuyển sinh',
        'Lệ phí',
        'Trạng thái thanh toán',
        'Ghi chú admin',
        'Mã hồ sơ (Hệ thống)',
        'Ngày nộp'
    ], ';');

    // Streaming dữ liệu
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
        
        // Thay vì nháy đơn, dùng dạng ="chuỗi" để Excel hiểu là Text mà không bị hiện nháy đơn và không mất số 0
        $cmnd = '="' . ($user['identity_card'] ?? '') . '"';
        $appIdStr = '="' . ($app['id'] ?? '') . '"';
        
        $statusText = $app['status'] == 'APPROVED' ? 'Hợp lệ' : ($app['status'] == 'REJECTED' ? 'Từ chối' : 'Chờ duyệt');
        $paymentText = ($app['payment_status'] ?? '') == 'PAID' ? 'Đã TT' : 'Chưa TT';

        fputcsv($output, [
            $stt++,                                  // STT
            $statusText,                             // Trạng thái
            $user['full_name'] ?? '',                // Họ tên
            $dob,                                    // Ngày sinh
            $genderText,                             // Giới tính
            $cmnd,                                   // CMND
            $user['ethnicity'] ?? '',                // Dân tộc
            $user['phone_number'] ?? '',             // SĐT
            $user['contact_email'] ?? '',            // Email
            $user['address_detail'] ?? '',           // Địa chỉ
            $user['province'] ?? '',                 // Tỉnh
            $user['ward'] ?? '',                     // Quận/Huyện
            $user['school_name'] ?? '',              // Trường THPT
            $user['school_province'] ?? '',          // Tỉnh trường
            $user['graduation_year'] ?? '',          // Năm TN
            $user['academic_performance'] ?? '',     // Học lực
            $user['conduct'] ?? '',                  // Hạnh kiểm
            $user['priority_area'] ?? '',            // KV ưu tiên
            $user['priority_object'] ?? '',          // ĐT ưu tiên
            $levelName,                              // Hệ đào tạo
            $majorName,                              // Tên ngành
            $app['major_id'] ?? '',                  // Mã ngành
            $methodName,                             // Phương thức XT
            $app['admission_method_id'] ?? '',       // Mã PTXT
            $app['priority'] ?? 1,                   // Nguyện vọng
            $periodName,                             // Kỳ tuyển sinh
            $feeAmount,                              // Lệ phí
            $paymentText,                            // Thanh toán
            $app['admin_notes'] ?? '',               // Ghi chú admin
            $appIdStr,                               // Mã hồ sơ
            date('d/m/Y H:i', strtotime($app['submitted_at'])) // Ngày nộp
        ], ';');
    }

    fclose($output);

    // Dọn dẹp buffer nếu có và trả về JSON
    if (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    
    echo json_encode([
        'status' => 'success',
        'filename' => $filename,
        'file_url' => $fileUrl
    ]);
    exit;

} catch (Exception $e) {
    if (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}
