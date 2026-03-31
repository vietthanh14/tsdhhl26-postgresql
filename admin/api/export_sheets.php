<?php
// admin/api/export_sheets.php — Xuất dữ liệu lên Google Sheets
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../../lib/RateLimiter.php';

header('Content-Type: application/json; charset=utf-8');

// Kiểm tra Rate Limit: Cấm xuất Google Sheets liên tục (Tối đa 5 lần mỗi 30 phút = 1800 giây)
if (!RateLimiter::checkSessionLimit('export_sheets', 5, 1800)) {
    echo json_encode(['status' => 'error', 'message' => 'Bạn đã xuất file quá 5 lần trong 30 phút. Vui lòng thử lại sau để bảo vệ máy chủ.']);
    exit;
}

$autoloadPath = __DIR__ . '/../../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    echo json_encode(['status' => 'error', 'message' => 'Thư viện Google API chưa được cài đặt (thiếu vendor/autoload.php).']);
    exit;
}
require_once $autoloadPath;

try {
    $supabaseAdmin = new SupabaseClient('service');
    
    $query = 'select=*,admission_periods(name),majors(major_name,education_levels(name)),admission_methods(method_name,application_fee)&order=submitted_at.desc';
    $appsRes = $supabaseAdmin->select('applications', $query);
    if ($appsRes['code'] != 200) {
        throw new Exception("Lỗi khi lấy dữ liệu applications từ Supabase: " . json_encode($appsRes['data'] ?? []));
    }
    $applications = $appsRes['data'];

    $userProfilesMap = $supabaseAdmin->fetchUserProfilesMap(
        array_column($applications, 'user_id'),
        'id,full_name,identity_card,phone_number,contact_email,date_of_birth,gender,ethnicity,province,ward,address_detail,school_name,school_province,priority_area,priority_object,graduation_year,academic_performance,conduct'
    );

    // Headers
    $values = [];
    $values[] = [
        'STT', 'Họ tên', 'Ngày sinh', 'Giới tính', 'CMND/CCCD', 'Dân tộc',
        'Số điện thoại', 'Email liên lạc', 'Địa chỉ', 'Tỉnh/TP', 'Quận/Huyện',
        'Trường THPT', 'Năm tốt nghiệp', 'Học lực', 'Hạnh kiểm', 'KV ưu tiên',
        'ĐT ưu tiên', 'Ngày nộp hồ sơ', 'Kỳ tuyển sinh', 'Hệ đào tạo',
        'Ngành đăng ký', 'Phương thức xét tuyển', 'Nguyện vọng', 'Lệ phí (VNĐ)',
        'Trạng thái hồ sơ', 'Ghi chú admin', 'Trạng thái thanh toán', 'Link biên lai'
    ];

    $stt = 1;
    foreach ($applications as $app) {
        $user = $userProfilesMap[$app['user_id']] ?? [];
        $dob = !empty($user['date_of_birth']) ? date('d/m/Y', strtotime($user['date_of_birth'])) : '';
        $genderText = ($user['gender'] ?? '') === 'male' ? 'Nam' : (($user['gender'] ?? '') === 'female' ? 'Nữ' : '');
        $statusText = $app['status'] == 'APPROVED' ? 'Hợp lệ' : ($app['status'] == 'REJECTED' ? 'Từ chối' : 'Chờ duyệt');
        $paymentText = $app['payment_status'] == 'PAID' ? 'Đã thanh toán' : ($app['payment_status'] == 'REFUNDED' ? 'Đã hoàn tiền' : 'Chưa thanh toán');
        $cmndStr = isset($user['identity_card']) && $user['identity_card'] !== '' ? "'" . $user['identity_card'] : '';
        $phoneStr = isset($user['phone_number']) && $user['phone_number'] !== '' ? "'" . $user['phone_number'] : '';

        $values[] = [
            $stt++,
            $user['full_name'] ?? '', $dob, $genderText,
            $cmndStr, $user['ethnicity'] ?? '',
            $phoneStr, $user['contact_email'] ?? '',
            trim($user['address_detail'] ?? ''),
            $user['province'] ?? '', $user['ward'] ?? '',
            $user['school_name'] ?? '', $user['graduation_year'] ?? '',
            $user['academic_performance'] ?? '', $user['conduct'] ?? '',
            $user['priority_area'] ?? '', $user['priority_object'] ?? '',
            date('d/m/Y H:i', strtotime($app['submitted_at'])),
            $app['admission_periods']['name'] ?? '',
            $app['majors']['education_levels']['name'] ?? '',
            $app['majors']['major_name'] ?? '',
            $app['admission_methods']['method_name'] ?? '',
            $app['priority'] ?? 1,
            $app['admission_methods']['application_fee'] ?? $app['fee_amount'] ?? 0,
            $statusText, $app['admin_notes'] ?? '', $paymentText,
            $app['receipt_url'] ?? ''
        ];
    }

    // Google Sheets API
    $keyFilePath = __DIR__ . '/../../serious-app-415103-baf99d68d251.json';
    if (!file_exists($keyFilePath)) {
        throw new Exception("Không tìm thấy file credentials cấu hình Google API.");
    }

    $client = new \Google\Client();
    $client->setAuthConfig($keyFilePath);
    $client->addScope(\Google\Service\Sheets::SPREADSHEETS);
    
    $service = new \Google\Service\Sheets($client);
    $spreadsheetId = '1RxhTyQlKArR3wXsnHoJ96IbMJlsxO1TzSUOamly2OYo';

    $spreadsheetInfo = $service->spreadsheets->get($spreadsheetId);
    $firstSheetTitle = $spreadsheetInfo->getSheets()[0]->getProperties()->getTitle();

    // Clear + Update
    $service->spreadsheets_values->clear($spreadsheetId, $firstSheetTitle, new \Google\Service\Sheets\ClearValuesRequest());
    $result = $service->spreadsheets_values->update(
        $spreadsheetId, $firstSheetTitle,
        new \Google\Service\Sheets\ValueRange(['values' => $values]),
        ['valueInputOption' => 'USER_ENTERED']
    );

    echo json_encode(['status' => 'success', 'message' => 'Đã xuất dữ liệu', 'updatedRows' => $result->getUpdatedCells()]);

} catch (Exception $e) {
    error_log("Google Sheets Export Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
