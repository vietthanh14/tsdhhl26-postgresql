<?php
// candidate/api/methods.php — Trả về phương thức xét tuyển theo đợt + ngành
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Chưa đăng nhập']);
    exit;
}

require_once __DIR__ . '/../../lib/SupabaseClient.php';

$period_id = trim($_GET['period_id'] ?? '');
$major_id  = trim($_GET['major_id']  ?? '');

if (!$period_id || !$major_id || !ctype_digit($period_id) || !ctype_digit($major_id)) {
    echo json_encode([]);
    exit;
}

$supabase = new SupabaseClient('anon');

// Lấy method_ids áp dụng cho đợt+ngành này
$pmmRes = $supabase->select(
    'admission_period_major_methods',
    "period_id=eq.{$period_id}&major_id=eq.{$major_id}&select=method_id"
);

if ($pmmRes['code'] !== 200 || empty($pmmRes['data'])) {
    echo json_encode([]);
    exit;
}

$methodIds = array_column($pmmRes['data'], 'method_id');
$idsStr    = implode(',', $methodIds);

$methodsRes = $supabase->select('admission_methods', "id=in.({$idsStr})&select=id,method_name&order=id.asc");

echo json_encode(($methodsRes['code'] === 200) ? $methodsRes['data'] : []);
