<?php
require_once __DIR__ . '/../config/database.php';

session_start();
session_destroy();
header('Location: ' . BASE_URL . '/admin/login.php');
exit;
?>
