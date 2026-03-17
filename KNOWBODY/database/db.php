<?php

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "knowbody";

try {
    $mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
    $mysqli->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    error_log("DB connection error: " . $e->getMessage());
    http_response_code(500);
    die("Failed to connect to database. Check server logs.");
}
?>