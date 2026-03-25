<?php
// admin/api_export_sheets.php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    echo json_encode(['status' => 'error', 'message' => 'Thư viện Google API chưa được cài đặt (thiếu vendor/autoload.php).']);
    exit;
}

require_once $autoloadPath;
require_once __DIR__ . '/../lib/SupabaseClient.php';

try {
    // 1. Lấy dữ liệu từ Supabase
    $supabaseAdmin = new SupabaseClient('service');
    
    // Lấy Danh sách Applications
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

    // 2. Chuẩn bị dữ liệu cho Google Sheets mảng 2 chiều
    $values = [];
    // Dòng Tiêu đề (Headers)
    $values[] = [
        'STT', 
        'Họ tên', 
        'Ngày sinh',
        'Giới tính',
        'CMND/CCCD', 
        'Dân tộc',
        'Số điện thoại', 
        'Email liên lạc',
        'Địa chỉ',
        'Tỉnh/TP',
        'Quận/Huyện',
        'Trường THPT',
        'Năm tốt nghiệp',
        'Học lực',
        'Hạnh kiểm',
        'KV ưu tiên',
        'ĐT ưu tiên',
        'Ngày nộp hồ sơ', 
        'Kỳ tuyển sinh',
        'Hệ đào tạo',
        'Ngành đăng ký', 
        'Phương thức xét tuyển',
        'Nguyện vọng',
        'Lệ phí (VNĐ)',
        'Trạng thái hồ sơ',
        'Ghi chú admin',
        'Trạng thái thanh toán',
        'Link biên lai'
    ];

    $stt = 1;
    foreach ($applications as $app) {
        $user = $userProfilesMap[$app['user_id']] ?? [];
        $periodName = $app['admission_periods']['name'] ?? '';
        $majorName = $app['majors']['major_name'] ?? '';
        $levelName = $app['majors']['education_levels']['name'] ?? '';
        $methodName = $app['admission_methods']['method_name'] ?? '';
        $feeAmount = $app['admission_methods']['application_fee'] ?? $app['fee_amount'] ?? 0;
        
        $statusText = $app['status'] == 'APPROVED' ? 'Hợp lệ' : ($app['status'] == 'REJECTED' ? 'Từ chối' : 'Chờ duyệt');
        $paymentText = $app['payment_status'] == 'PAID' ? 'Đã thanh toán' : ($app['payment_status'] == 'REFUNDED' ? 'Đã hoàn tiền' : 'Chưa thanh toán');
        $dob = !empty($user['date_of_birth']) ? date('d/m/Y', strtotime($user['date_of_birth'])) : '';
        $genderText = ($user['gender'] ?? '') === 'male' ? 'Nam' : (($user['gender'] ?? '') === 'female' ? 'Nữ' : '');
        $addressFull = trim(($user['address_detail'] ?? ''));

        $values[] = [
            $stt++,
            $user['full_name'] ?? '',
            $dob,
            $genderText,
            $user['identity_card'] ?? '',
            $user['ethnicity'] ?? '',
            $user['phone_number'] ?? '',
            $user['contact_email'] ?? '',
            $addressFull,
            $user['province'] ?? '',
            $user['ward'] ?? '',
            $user['school_name'] ?? '',
            $user['graduation_year'] ?? '',
            $user['academic_performance'] ?? '',
            $user['conduct'] ?? '',
            $user['priority_area'] ?? '',
            $user['priority_object'] ?? '',
            date('d/m/Y H:i', strtotime($app['submitted_at'])),
            $periodName,
            $levelName,
            $majorName,
            $methodName,
            $app['priority'] ?? 1,
            $feeAmount,
            $statusText,
            $app['admin_notes'] ?? '',
            $paymentText,
            $app['receipt_url'] ?? ''
        ];
    }

    // 3. Kết nối Google Sheets API
    $keyFilePath = __DIR__ . '/../serious-app-415103-baf99d68d251.json';
    if (!file_exists($keyFilePath)) {
        throw new Exception("Không tìm thấy file credentials cấu hình Google API.");
    }

    $client = new \Google\Client();
    $client->setAuthConfig($keyFilePath);
    $client->addScope(\Google\Service\Sheets::SPREADSHEETS);
    
    $service = new \Google\Service\Sheets($client);
    $spreadsheetId = '1RxhTyQlKArR3wXsnHoJ96IbMJlsxO1TzSUOamly2OYo';

    // Get the first sheet's title dynamically
    $spreadsheetInfo = $service->spreadsheets->get($spreadsheetId);
    $sheetsList = $spreadsheetInfo->getSheets();
    $firstSheetTitle = $sheetsList[0]->getProperties()->getTitle();
    
    $range = $firstSheetTitle; // Overwrite the whole first sheet

    // Xóa dữ liệu cũ (Clear)
    $clearParams = new \Google\Service\Sheets\ClearValuesRequest();
    $service->spreadsheets_values->clear($spreadsheetId, $range, $clearParams);

    // Ghi dữ liệu mới (Update)
    $body = new \Google\Service\Sheets\ValueRange([
        'values' => $values
    ]);
    $params = [
        'valueInputOption' => 'USER_ENTERED'
    ];
    $result = $service->spreadsheets_values->update($spreadsheetId, $range, $body, $params);

    echo json_encode(['status' => 'success', 'message' => 'Đã xuất dữ liệu', 'updatedRows' => $result->getUpdatedCells()]);

} catch (Exception $e) {
    // Return explicit error to client for debugging
    error_log("Google Sheets Export Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
