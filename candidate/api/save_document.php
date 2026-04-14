<?php
// candidate/api/save_document.php — Lưu URL tài liệu vào Supabase
require_once __DIR__ . '/_guard.php';

$user_id = $_SESSION['user_id'];
$doc_type_id = $_POST['doc_type_id'] ?? null;
$file_url = $_POST['file_url'] ?? null;

if (!$doc_type_id || !$file_url) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

$supabase = new DatabaseClient('service');

$data = [
    'user_id' => $user_id,
    'document_type_id' => (int)$doc_type_id,
    'drive_file_url' => $file_url,
    'uploaded_at' => date('Y-m-d H:i:sP')
];

$res = $supabase->insert('user_documents', $data);

if (in_array($res['code'], [201, 200, 204])) {
    echo json_encode(['success' => true]);
} else {
    if ($res['code'] == 409 || (isset($res['data']['code']) && $res['data']['code'] == '23505')) {
        echo json_encode(['success' => false, 'message' => 'Cơ sở dữ liệu vẫn còn cài đặt Cấm trùng lặp. Vui lòng chạy lệnh SQL Drop Constraint.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi CSDL: ' . json_encode($res['data'])]);
    }
}
