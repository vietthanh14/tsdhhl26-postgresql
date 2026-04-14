<?php
// candidate/api/majors.php — Trả về danh sách ngành học theo đợt tuyển sinh
require_once __DIR__ . '/_guard.php';

$period_id = trim($_GET['period_id'] ?? '');
if (!$period_id || !ctype_digit($period_id)) {
    echo json_encode([]);
    exit;
}

$supabase = new DatabaseClient('service');

// Lấy các major_id thuộc đợt này
$pmRes = $supabase->select('admission_period_majors', "period_id=eq.{$period_id}&select=major_id");
if ($pmRes['code'] !== 200 || empty($pmRes['data'])) {
    echo json_encode([]);
    exit;
}

$majorIds = array_column($pmRes['data'], 'major_id');
$idsStr   = implode(',', $majorIds);

// Lấy thông tin ngành kèm tên hệ
$sql = "SELECT m.id, m.major_name, m.major_code, el.name as education_levels__name
        FROM majors m
        LEFT JOIN education_levels el ON m.education_level_id = el.id
        WHERE m.id IN ($idsStr)
        ORDER BY m.major_name ASC";
$majorsRes = $supabase->rawQuery($sql);

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
