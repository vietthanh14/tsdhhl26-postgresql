<?php
// candidate/update_priority.php
// AJAX endpoint: xắp xếp lại thứ tự nguyện vọng (insert-and-shift trong cùng đợt)
session_start();
require_once __DIR__ . '/../lib/SupabaseClient.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Không có phiên đăng nhập']);
    exit;
}

$user_id     = $_SESSION['user_id'];
$input       = json_decode(file_get_contents('php://input'), true);
$app_id      = $input['app_id'] ?? '';
$newPriority = intval($input['priority'] ?? 0);

if (!$app_id || $newPriority < 1) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    exit;
}

$supabase = new SupabaseClient('service');

// 1. Lấy thông tin đợt tuyển sinh của hồ sơ (đảm bảo hồ sơ thuộc user này)
$targetRes = $supabase->select(
    'applications',
    "id=eq.{$app_id}&user_id=eq.{$user_id}&select=id,admission_period_id,priority"
);
if (empty($targetRes['data'])) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy hồ sơ']);
    exit;
}
$targetApp = $targetRes['data'][0];
$period_id = $targetApp['admission_period_id'];

// 2. Lấy tất cả hồ sơ của user trong cùng đợt, sắp xếp theo priority hiện tại
$allRes = $supabase->select(
    'applications',
    "user_id=eq.{$user_id}&admission_period_id=eq.{$period_id}&select=id,priority&order=priority.asc"
);
$allApps = ($allRes['code'] === 200) ? $allRes['data'] : [];

if (empty($allApps)) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy hồ sơ trong đợt']);
    exit;
}

// 3. Insert-and-Shift: loại app cần cập nhật ra, chèn lại vào đúng vị trí
$others = array_values(array_filter($allApps, fn($a) => (string)$a['id'] !== (string)$app_id));

$maxPriority = count($allApps);
$newPriority = max(1, min($newPriority, $maxPriority));

// Chèn vào vị trí 0-indexed = $newPriority - 1
array_splice($others, $newPriority - 1, 0, [['id' => $app_id, 'priority' => (int)($targetApp['priority'])]]);

// 4. Bulk update chỉ những app có priority thay đổi
$priorityMap = [];
$errors = [];
foreach ($others as $idx => $app) {
    $assignedPriority = $idx + 1;
    $priorityMap[(string)$app['id']] = $assignedPriority;

    if ((int)($app['priority'] ?? 0) !== $assignedPriority) {
        // Signature đúng: update($table, $matchField, $matchValue, $data)
        $upd = $supabase->update('applications', 'id', $app['id'], ['priority' => $assignedPriority]);
        if (!in_array($upd['code'], [200, 204])) {
            $errors[] = $app['id'];
        }
    }
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => 'Lỗi cập nhật hồ sơ: ' . implode(', ', $errors)]);
} else {
    echo json_encode(['success' => true, 'priority_map' => $priorityMap]);
}

