<?php
session_start();
session_destroy();
header('Location: /tsdhhl26/admin/login.php');
exit;
?>
