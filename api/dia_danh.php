<?php
// api/dia_danh.php - API trả về dữ liệu địa danh hành chính 2025
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$jsonPath = __DIR__ . '/../datahanhchinh2025.json';

if (!file_exists($jsonPath)) {
    http_response_code(404);
    echo json_encode(['error' => 'Không tìm thấy file dữ liệu địa danh.']);
    exit;
}

$data = json_decode(file_get_contents($jsonPath), true);
$action = $_GET['action'] ?? 'provinces';

if ($action === 'provinces') {
    // Trả về danh sách tỉnh/thành phố
    $provinces = array_map(fn($p) => [
        'code' => $p['province_code'],
        'name' => $p['name'],
    ], $data);
    echo json_encode($provinces, JSON_UNESCAPED_UNICODE);

} elseif ($action === 'wards') {
    // Trả về danh sách phường/xã theo province_code
    $provinceCode = $_GET['province_code'] ?? '';
    if (!$provinceCode) {
        echo json_encode([]);
        exit;
    }

    $wards = [];
    foreach ($data as $province) {
        if ($province['province_code'] === $provinceCode) {
            $wards = array_map(fn($w) => [
                'code' => $w['ward_code'],
                'name' => $w['name'],
            ], $province['wards'] ?? []);
            break;
        }
    }
    echo json_encode($wards, JSON_UNESCAPED_UNICODE);

} else {
    http_response_code(400);
    echo json_encode(['error' => 'action không hợp lệ.']);
}
