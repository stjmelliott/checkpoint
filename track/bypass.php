<?php
session_start();
$_SESSION['company_id'] = 1;
$_SESSION['company_name'] = 'Exspeedite';
header("Location: /dashboard.php");
exit;
?>
