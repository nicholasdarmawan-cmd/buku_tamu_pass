<?php
// Konfigurasi Koneksi Basis Data MySQL (RDBMS)
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'db_visitor_pass';

mysqli_report(MYSQLI_REPORT_OFF);
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Koneksi ke basis data gagal: " . htmlspecialchars($conn->connect_error));
}

$conn->set_charset("utf8mb4");
date_default_timezone_set('Asia/Jakarta');
?>
