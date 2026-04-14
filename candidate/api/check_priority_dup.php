<?php
// candidate/api/check_priority_dup.php — Kiểm tra trùng thứ tự nguyện vọng trong đợt
require_once __DIR__ . '/_guard.php';

$user_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$period_id = $input['period_id'] ?? '';
$priority = intval($input['priority'] ?? 0);
$exclude_app_id = $input['exclude_app_id'] ?? null;

if (!$period_id || $priority < 1) {
    echo json_encode(['duplicate' => false, 'error' => 'Thiếu tham số']);
    exit;
}

$supabase = new DatabaseClient('service');
$sql = "SELECT a.id, a.major_id, m.major_name as majors__major_name
        FROM applications a
        LEFT JOIN majors m ON a.major_id = m.id
        WHERE a.user_id = :u AND a.admission_period_id = :p AND a.priority = :pri";
$res = $supabase->rawQuery($sql, [':u' => $user_id, ':p' => $period_id, ':pri' => $priority]);

if ($res['code'] !== 200 || empty($res['data'])) {
    echo json_encode(['duplicate' => false]);
    exit;
}

// Lọc bỏ hồ sơ đang chỉnh sửa
$conflicts = array_filter($res['data'], fn($row) => (string)$row['id'] !== (string)$exclude_app_id);

if (empty($conflicts)) {
    echo json_encode(['duplicate' => false]);
    exit;
}

$conflict = array_values($conflicts)[0];
echo json_encode([
    'duplicate' => true,
    'taken_by' => $conflict['majors']['major_name'] ?? 'Ngành khác',
]);
