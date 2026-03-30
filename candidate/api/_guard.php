<?php
// candidate/api/_guard.php — Shared API auth guard
// Replaces 10 duplicated lines across all candidate API endpoints
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Chưa đăng nhập']);
    exit;
}
require_once __DIR__ . '/../../lib/SupabaseClient.php';
require_once __DIR__ . '/../../lib/CSRF.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $headers = apache_request_headers();
    $tokenFromHeader = $headers['X-CSRF-Token'] ?? $headers['X-Csrf-Token'] ?? $headers['x-csrf-token'] ?? '';
    $input = json_decode(file_get_contents('php://input'), true);
    $tokenFromBody = $input['csrf_token'] ?? '';
    
    $receivedToken = $tokenFromHeader ?: $tokenFromBody;
    if (empty($receivedToken) || !CSRF::validateToken($receivedToken)) {
        http_response_code(403);
        echo json_encode(['error' => 'Yêu cầu bị từ chối do thiếu hoặc sai CSRF Token.']);
        exit;
    }
}
