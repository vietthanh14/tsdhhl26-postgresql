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
    $query = 'select=*,admission_periods(name),majors(major_name),admission_methods(method_name)&order=submitted_at.desc';
    $appsRes = $supabaseAdmin->select('applications', $query);
    if ($appsRes['code'] != 200) {
        throw new Exception("Lỗi khi lấy dữ liệu applications từ Supabase: " . json_encode($appsRes['data'] ?? []));
    }
    $applications = $appsRes['data'];

    // Lấy Profile để map thông tin user
    $usersRes = $supabaseAdmin->select('user_profiles', 'select=id,full_name,identity_card,phone_number,contact_email');
    $userProfilesMap = [];
    if ($usersRes['code'] == 200 && is_array($usersRes['data'])) {
        foreach ($usersRes['data'] as $u) {
            $userProfilesMap[$u['id']] = $u;
        }
    }

    // 2. Thiết lập header để ép trình duyệt tải xuống file CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="Danh_sach_tuyen_sinh_' . date('Y-m-d_H-i-s') . '.csv"');
    
    // Mở luồng Output trực tiếp (Không lưu vào mảng trung gian hay RAM)
    $output = fopen('php://output', 'w');

    // Thêm BOM (Byte Order Mark) để MS Excel hiển thị đúng Tiếng Việt UTF-8
    fputs($output, $bom =(chr(0xEF) . chr(0xBB) . chr(0xBF)));

    // Xuất dòng Tiêu đề
    fputcsv($output, [
        'STT', 
        'Trạng thái hồ sơ trên cổng bộ GD&ĐT', 
        'Họ và tên', 
        'Ngày sinh', 
        'Giới tính',
        'CMND', 
        'KV ƯT',
        'ĐT ƯT', 
        'Tên tỉnh',
        'Mã PTXT',
        'Mã THM',
        'Điểm ưu tiên giảm dần',
        'Tổng điểm chưa có ƯT (Thang 30)',
        'Điểm xét tuyển',
        'Mã ngành',
        'Mã hồ sơ',
        'Tên ngành',
        'Trình độ',
        'Thời gian nhập học',
        'Link Giấy Báo Nhập Học'
    ]);

    // 3. Streaming dữ liệu: Đưa vào File từng dòng một (Cực kỳ tiết kiệm RAM)
    $stt = 1;
    foreach ($applications as $app) {
        $user = $userProfilesMap[$app['user_id']] ?? [];
        $majorName = $app['majors']['major_name'] ?? '';
        $educationLevelStr = ''; // Sẽ cần join bảng education_levels nếu muốn chính xác (tạm để trống hoặc tuỳ chỉnh sau)
        // Hiện tại DB thiếu giới tính, Tên tỉnh, Mã THM, điểm thi. Ta sẽ điền trống cho các trường chưa thu thập.
        
        $dob = !empty($user['date_of_birth']) ? date('d/m/Y', strtotime($user['date_of_birth'])) : '';
        $cmnd = '="' . ($user['identity_card'] ?? '') . '"'; // Giữ số 0
        $appIdStr = '="' . ($app['id'] ?? '') . '"'; // Tránh Excel format UUID thành lỗi

        fputcsv($output, [
            $stt++,                                  // STT
            $app['status'] == 'APPROVED' ? 'Hợp lệ' : ($app['status'] == 'REJECTED' ? 'Từ chối' : 'Chờ duyệt'), // Trạng thái
            $user['full_name'] ?? '',                // Họ và tên
            $dob,                                    // Ngày sinh
            '',                                      // Giới tính (Chưa có trong DB)
            $cmnd,                                   // CMND
            '',                                      // KV ƯT
            '',                                      // ĐT ƯT
            '',                                      // Tên tỉnh (Chưa có trong DB)
            $app['admission_method_id'] ?? '',       // Mã PTXT
            '',                                      // Mã THM
            '',                                      // Điểm ưu tiên giảm dần
            '',                                      // Tổng điểm chưa có ƯT
            '',                                      // Điểm xét tuyển
            $app['major_id'] ?? '',                  // Mã ngành
            $appIdStr,                               // Mã hồ sơ (Mã đơn UUID)
            $majorName,                              // Tên ngành
            $educationLevelStr,                      // Trình độ
            '',                                      // Thời gian nhập học
            ''                                       // Link Giấy báo nhập học
        ]);
    }

    // Đóng luồng
    fclose($output);
    exit;

} catch (Exception $e) {
    // Nếu có lỗi, trả ra màn hình thay vì tải file
    header('Content-Type: text/html; charset=utf-8');
    echo "<h1>Lỗi xuất dữ liệu</h1>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    error_log("CSV Export Error: " . $e->getMessage());
    exit;
}
