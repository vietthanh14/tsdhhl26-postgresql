<?php
// candidate/check_priority_dup.php
// AJAX: kiểm tra thứ tự nguyện vọng có bị trùng trong đợt không
session_start();
require_once __DIR__ . '/../lib/SupabaseClient.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['duplicate' => false, 'error' => 'Chưa đăng nhập']);
    exit;
}

$user_id = $_SESSION['user_id'];
$input   = json_decode(file_get_contents('php://input'), true);

$period_id = $input['period_id'] ?? '';
$priority  = intval($input['priority'] ?? 0);

// Nếu đang cập nhật hồ sơ hiện có thì loại hồ sơ đó khỏi kết quả
$exclude_app_id = $input['exclude_app_id'] ?? null;

if (!$period_id || $priority < 1) {
    echo json_encode(['duplicate' => false, 'error' => 'Thiếu tham số']);
    exit;
}

$supabase = new SupabaseClient('service');
$query = "user_id=eq.{$user_id}&admission_period_id=eq.{$period_id}&priority=eq.{$priority}&select=id,major_id,majors(major_name)";
$res = $supabase->select('applications', $query);

if ($res['code'] !== 200 || empty($res['data'])) {
    echo json_encode(['duplicate' => false]);
    exit;
}

// Lọc bỏ hồ sơ đang chỉnh sửa (phòng trường hợp cập nhật thứ tự)
$conflicts = array_filter($res['data'], function($row) use ($exclude_app_id) {
    return (string)$row['id'] !== (string)$exclude_app_id;
});

if (empty($conflicts)) {
    echo json_encode(['duplicate' => false]);
    exit;
}

$conflict = array_values($conflicts)[0];
$majorName = $conflict['majors']['major_name'] ?? 'Ngành khác';

echo json_encode([
    'duplicate' => true,
    'taken_by'  => $majorName,
]);
