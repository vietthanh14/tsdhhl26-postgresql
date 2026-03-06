<?php
// candidate/check_duplicate.php - AJAX endpoint kiểm tra trùng ngành+phương thức
session_start();
require_once __DIR__ . '/../lib/SupabaseClient.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['duplicate' => false, 'error' => 'Không có phiên đăng nhập']);
    exit;
}

$user_id = $_SESSION['user_id'];
$token = $_SESSION['access_token'] ?? null;

$data = json_decode(file_get_contents('php://input'), true);
$period_id = $data['period_id'] ?? '';
$major_id = $data['major_id'] ?? '';
$method_id = $data['method_id'] ?? '';

if (!$period_id || !$major_id || !$method_id) {
    echo json_encode(['duplicate' => false, 'error' => 'Thiếu tham số']);
    exit;
}

$supabaseAdmin = new SupabaseClient('service');
$query = "user_id=eq.{$user_id}&admission_period_id=eq.{$period_id}&major_id=eq.{$major_id}&admission_method_id=eq.{$method_id}";
$res = $supabaseAdmin->select('applications', $query);

$isDuplicate = ($res['code'] == 200 && !empty($res['data']));
echo json_encode(['duplicate' => $isDuplicate]);
