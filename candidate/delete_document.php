<?php
// candidate/delete_document.php
session_start();
require_once __DIR__ . '/../lib/SupabaseClient.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Parse JSON body
    $input = json_decode(file_get_contents('php://input'), true);
    $doc_id = $input['id'] ?? null;

    if (!$doc_id) {
        echo json_encode(['success' => false, 'message' => 'Thiếu ID tài liệu cần xóa']);
        exit;
    }

    $supabaseAdmin = new SupabaseClient('service');
    
    // Xóa record (Sử dụng service role và filter matching ID + user_id)
    $res = $supabaseAdmin->delete('user_documents', 'id=eq.' . urlencode($doc_id) . '&user_id', $user_id);
    
    if (in_array($res['code'], [200, 204])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi dữ liệu: ' . json_encode($res['data'])]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
