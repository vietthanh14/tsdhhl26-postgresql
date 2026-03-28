<?php
// candidate/api/majors.php — Trả về danh sách ngành học theo đợt tuyển sinh
require_once __DIR__ . '/_guard.php';

$period_id = trim($_GET['period_id'] ?? '');
if (!$period_id || !ctype_digit($period_id)) {
    echo json_encode([]);
    exit;
}

$supabase = new SupabaseClient('service');

// Lấy các major_id thuộc đợt này
$pmRes = $supabase->select('admission_period_majors', "period_id=eq.{$period_id}&select=major_id");
if ($pmRes['code'] !== 200 || empty($pmRes['data'])) {
    echo json_encode([]);
    exit;
}

$majorIds = array_column($pmRes['data'], 'major_id');
$idsStr   = implode(',', $majorIds);

// Lấy thông tin ngành kèm tên hệ
$majorsRes = $supabase->select(
    'majors',
    "id=in.({$idsStr})&select=id,major_name,major_code,education_levels(name)&order=major_name.asc"
);

$majors = [];
if ($majorsRes['code'] === 200) {
    foreach ($majorsRes['data'] as $m) {
        $majors[] = [
            'id'   => $m['id'],
            'name' => '[' . ($m['education_levels']['name'] ?? 'N/A') . '] - ' . $m['major_name'] . ' (Mã: ' . ($m['major_code'] ?? 'N/A') . ')',
        ];
    }
}

echo json_encode($majors);
