<?php
// candidate/api/periods.php — Trả về danh sách đợt tuyển sinh theo hệ đào tạo
require_once __DIR__ . '/_guard.php';

$level_id = trim($_GET['level_id'] ?? '');
if (!$level_id || !ctype_digit($level_id)) {
    echo json_encode([]);
    exit;
}

$today = date('Y-m-d');
$supabase = new SupabaseClient('service');
$res = $supabase->select(
    'admission_periods',
    "is_active=eq.true&end_date=gte.{$today}&education_level_id=eq.{$level_id}&order=created_at.desc"
);

echo json_encode(($res['code'] === 200) ? $res['data'] : []);
