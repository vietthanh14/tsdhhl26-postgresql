<?php
// candidate/api/delete_document.php — Xóa tài liệu (có kiểm tra quyền sở hữu)
require_once __DIR__ . '/_guard.php';

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$doc_id = $input['id'] ?? null;

if (!$doc_id) {
    echo json_encode(['success' => false, 'message' => 'Thiếu ID tài liệu cần xóa']);
    exit;
}

$supabase = new DatabaseClient('service');

// Bước 1: Kiểm tra tài liệu có thuộc user đang đăng nhập không
$checkRes = $supabase->select('user_documents', "id=eq.{$doc_id}&user_id=eq.{$user_id}&select=id");
if ($checkRes['code'] != 200 || empty($checkRes['data'])) {
    echo json_encode(['success' => false, 'message' => 'Tài liệu không tồn tại hoặc không thuộc quyền sở hữu của bạn.']);
    exit;
}

// Bước 2: Xóa record theo ID
$res = $supabase->delete('user_documents', 'id', $doc_id);

if (in_array($res['code'], [200, 204])) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi dữ liệu: ' . json_encode($res['data'])]);
}
