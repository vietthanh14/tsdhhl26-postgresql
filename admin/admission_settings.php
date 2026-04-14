<?php
// Redirect to new standalone pages
require_once __DIR__ . '/../config/database.php';
header('Location: ' . BASE_URL . '/admin/manage_periods.php');
exit;
