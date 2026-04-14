<?php
// candidate/api/check_duplicate.php — Kiểm tra trùng ngành + phương thức trong đợt
require_once __DIR__ . '/_guard.php';

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$period_id = $data['period_id'] ?? '';
$major_id = $data['major_id'] ?? '';
$method_id = $data['method_id'] ?? '';

if (!$period_id || !$major_id || !$method_id) {
    echo json_encode(['duplicate' => false, 'error' => 'Thiếu tham số']);
    exit;
}

$supabase = new DatabaseClient('service');
$query = "user_id=eq.{$user_id}&admission_period_id=eq.{$period_id}&major_id=eq.{$major_id}&admission_method_id=eq.{$method_id}";
$res = $supabase->select('applications', $query);

echo json_encode(['duplicate' => ($res['code'] == 200 && !empty($res['data']))]);
