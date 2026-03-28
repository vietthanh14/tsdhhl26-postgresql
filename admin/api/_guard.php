<?php
// admin/api/_guard.php — Shared auth guard cho admin endpoints
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    $isJson = (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'json') !== false)
           || (strpos($_SERVER['CONTENT_TYPE'] ?? '', 'json') !== false);
    if ($isJson) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    } else {
        echo "Unauthorized";
    }
    exit;
}

require_once __DIR__ . '/../../lib/SupabaseClient.php';
