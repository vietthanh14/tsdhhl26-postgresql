<?php
// Redirect to new standalone pages
require_once __DIR__ . '/../config/supabase.php';
header('Location: ' . BASE_URL . '/admin/manage_periods.php');
exit;
