<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'user') {
    header('Location: login_form.php');
    exit;
}

echo'Profile Page';
?>
