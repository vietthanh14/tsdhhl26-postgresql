<?php
// candidate/save_document.php
session_start();
require_once __DIR__ . '/../lib/SupabaseClient.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$token = $_SESSION['access_token'] ?? null;
$doc_type_id = $_POST['doc_type_id'] ?? null;
$file_url = $_POST['file_url'] ?? null;

if (!$doc_type_id || !$file_url) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

$supabase = new SupabaseClient('service'); // Dùng service để có thể UPSERT (hoặc dùng token nếu RLS cho phép)

// Kiểm tra xem đã tồn tại chưa để thực hiện ghi đè (upsert)
// user_documents có UNIQUE(user_id, document_type_id) nên dùng upsert của Supabase
// endpoint UPSERT: POST /rest/v1/table với header Prefer: resolution=merge-duplicates

$data = [
    'user_id' => $user_id,
    'document_type_id' => (int)$doc_type_id,
    'drive_file_url' => $file_url,
    'uploaded_at' => date('Y-m-d H:i:sP')
];

// Supabase REST API Upsert via request method directly since update() is PATCH
// We can use insert() but Supabase default is error on unique violation.
// Let's check existing first or use a custom query.
// To keep it simple for now, we try to insert. If it fails due to unique, we update.

$res = $supabase->insert('user_documents', $data);

if (in_array($res['code'], [201, 200, 204])) {
    echo json_encode(['success' => true]);
} else {
    // Nếu db báo lỗi unique do constraint cũ chưa xóa, thì nhắc user
    if ($res['code'] == 409 || (isset($res['data']['code']) && $res['data']['code'] == '23505')) {
        echo json_encode(['success' => false, 'message' => 'Cơ sở dữ liệu vẫn còn cài đặt Cấm trùng lặp. Vui lòng chạy lệnh SQL Drop Constraint.']);
    } else {
         echo json_encode(['success' => false, 'message' => 'Lỗi CSDL: ' . json_encode($res['data'])]);
    }
}
