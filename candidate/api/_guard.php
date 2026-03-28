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
