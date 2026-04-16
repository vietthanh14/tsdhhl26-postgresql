<?php
// api_search_sheets.php
header('Content-Type: application/json; charset=utf-8');

// Chỉ lấy CCCD từ query
$cccdTarget = $_GET['cccd'] ?? '';

if (empty($cccdTarget)) {
    echo json_encode(['status' => 'error', 'message' => 'Vui lòng cung cấp số CCCD.']);
    exit;
}

$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    echo json_encode(['status' => 'error', 'message' => 'Thiếu thư viện Google API.']);
    exit;
}

require_once $autoloadPath;

try {
    // 1. Khởi tạo Client
    $keyFilePath = __DIR__ . '/key.json';
    if (!file_exists($keyFilePath)) {
        throw new Exception("Không tìm thấy file credentials Google API.");
    }

    $client = new \Google\Client();
    $client->setAuthConfig($keyFilePath);
    $client->addScope(\Google\Service\Sheets::SPREADSHEETS_READONLY);
    
    $service = new \Google\Service\Sheets($client);
    
    // SpreadSheet ID của khách hàng
    $spreadsheetId = '1RxhTyQlKArR3wXsnHoJ96IbMJlsxO1TzSUOamly2OYo';
    
    // --- BẮT ĐẦU CACHE ĐỂ CHỐNG QUÁ TẢI (RATE LIMIT) ---
    // Google Sheets API cho phép tối đa 60 requests/phút/user.
    // Nếu có 1000 người vào tra cứu cùng 1 lúc thì sẽ bị block.
    // Giải pháp: Cache toàn bộ nội dung Sheet vào ổ cứng (File JSON).
    // Tuổi thọ của cache (VD: 15 phút = 900 giây, 5 phút = 300 giây).
    $cacheTime = 1800; 
    $cacheDir = __DIR__ . '/storage/cache';
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0777, true);
    }
    
    // Tạo tên file cache duy nhất cho Spreadsheet này
    $cacheFile = $cacheDir . '/sheet_data_' . $spreadsheetId . '.json';
    
    $values = [];
    $dsttSheetTitle = '';

    // Kiểm tra xem File Cache có tồn tại và còn "tươi" không?
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime)) {
        // Đọc dữ liệu từ Cache (Rất nhanh, không gọi lên Google)
        $cacheData = json_decode(file_get_contents($cacheFile), true);
        $values = $cacheData['values'] ?? [];
        $dsttSheetTitle = $cacheData['sheetTitle'] ?? 'Cache';
    } else {
        // Cache hết hạn hoặc chưa có -> Gọi lên Google Sheets API
        
        // 2. Tìm tên Sheet DSTT
        $spreadsheetInfo = $service->spreadsheets->get($spreadsheetId);
        $sheetsList = $spreadsheetInfo->getSheets();
        
        // Ưu tiên tìm đúng tên DSTT
        foreach($sheetsList as $sheet) {
            $title = $sheet->getProperties()->getTitle();
            if(stripos($title, 'DSTT') !== false) {
                $dsttSheetTitle = $title;
                break;
            }
        }
        
        // Nếu không tìm thấy sheet nào có chữ DSTT, lấy tạm sheet đầu tiên
        if ($dsttSheetTitle === '') {
            $dsttSheetTitle = $sheetsList[0]->getProperties()->getTitle();
        }

        // 3. Đọc dữ liệu từ Google Sheets
        // Ta lấy toàn bộ phạm vi từ cột A đến T (Khoảng 20 cột theo mô tả)
        $range = $dsttSheetTitle . '!A:T'; 
        $response = $service->spreadsheets_values->get($spreadsheetId, $range);
        $values = $response->getValues();

        if (!empty($values)) {
            // Lưu dữ liệu Gốc vào file Cache để dùng cho 5 phút tiếp theo
            $cacheContent = [
                'timestamp' => time(),
                'sheetTitle' => $dsttSheetTitle,
                'values' => $values
            ];
            // Atomically ghi file để tránh lỗi nếu 2 người truy cập cùng lúc ghi đè nhau
            $tempFile = $cacheFile . '.tmp';
            file_put_contents($tempFile, json_encode($cacheContent, JSON_UNESCAPED_UNICODE));
            rename($tempFile, $cacheFile);
        }
    }
    // --- KẾT THÚC CACHE ---

    if (empty($values)) {
        throw new Exception("Bảng dữ liệu hiện tại đang trống.");
    }

    // Lấy Row đầu tiên làm Headers
    $headers = array_shift($values);
    // Chuẩn hóa Headers: Xóa khoảng trắng thừa 2 đầu để map chuẩn xác
    $headers = array_map('trim', $headers);

    // Xác định index cột chứa CMND để tìm kiếm cho nhanh
    $cmndColIndex = -1;
    foreach ($headers as $index => $header) {
        // Tìm cột có tên chứa chữ CMND hoặc CCCD
        if (stripos($header, 'CMND') !== false || stripos($header, 'CCCD') !== false || stripos($header, 'Căn cước') !== false) {
            $cmndColIndex = $index;
            break;
        }
    }

    if ($cmndColIndex === -1) {
        throw new Exception("Không tìm thấy cột CMND/CCCD trong file dữ liệu Google Sheets để đối chiếu.");
    }

    // 4. Tìm kiếm trong mảng Values
    $foundRows = [];
    foreach ($values as $row) {
        $rowCmnd = isset($row[$cmndColIndex]) ? trim($row[$cmndColIndex]) : '';
        $rowCmnd = str_replace(['=', '"', "'"], '', $rowCmnd);
        
        if ($rowCmnd === $cccdTarget) {
            $foundRows[] = $row;
        }
    }

    if (!empty($foundRows)) {
        // Map mảng dữ liệu với Headers thành dạng Key => Value
        $resultDataArray = [];
        foreach ($foundRows as $foundRow) {
            $resultData = [];
            foreach ($headers as $index => $headerName) {
                // Fix trường hợp Dòng dữ liệu thiếu cột ở cuối sẽ không tồn tại index
                $resultData[$headerName] = isset($foundRow[$index]) ? trim($foundRow[$index]) : '';
            }
            $resultDataArray[] = $resultData;
        }
        
        echo json_encode(['status' => 'success', 'data' => $resultDataArray]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy thông tin thí sinh với Số CMND/CCCD này.']);
    }

} catch (Exception $e) {
    error_log("Google Sheets Lookup Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Đã có lỗi xảy ra khi kết nối máy chủ dữ liệu nội bộ.']);
}
