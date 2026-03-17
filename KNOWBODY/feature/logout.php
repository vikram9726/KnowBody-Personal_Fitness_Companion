<?php
session_start();
include '../config.php';
session_destroy();
header('Location: ' . BASE_URL . '/index.php');
exit;
?>
