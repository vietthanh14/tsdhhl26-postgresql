<?php
// candidate/api/methods.php — Trả về phương thức xét tuyển theo đợt + ngành
require_once __DIR__ . '/_guard.php';

$period_id = trim($_GET['period_id'] ?? '');
$major_id  = trim($_GET['major_id']  ?? '');

if (!$period_id || !$major_id || !ctype_digit($period_id) || !ctype_digit($major_id)) {
    echo json_encode([]);
    exit;
}

$supabase = new DatabaseClient('service');

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

$methodsRes = $supabase->select('admission_methods', "id=in.({$idsStr})&select=id,method_name,application_fee&order=id.asc");

echo json_encode(($methodsRes['code'] === 200) ? $methodsRes['data'] : []);
