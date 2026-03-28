<?php
// Shared admin page header - include after config/auth/supabase setup
// Expects: $pageTitle (string), $message (string), $error (string)
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/logo.png">
    <title><?php echo $pageTitle ?? 'Admin HALOU'; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/public.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin.css">
    <style>
        .major-checkboxes { max-height: 250px; overflow-y: auto; background: #f8fafc; padding: 10px; border: 1px solid #dee2e6; border-radius: 4px; }
        .btn-brand { background-color: var(--brand, #1A3A6E); color: white; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<div class="container-fluid p-0">
    <div class="row m-0">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <div class="col-md-10 main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold mb-0 text-brand"><?php echo $pageTitle ?? ''; ?></h3>
            </div>
            <?php include __DIR__ . '/../../includes/flash_messages.php'; ?>
