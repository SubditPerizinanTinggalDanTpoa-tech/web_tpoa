<?php
// Konfigurasi koneksi lokal XAMPP. Sesuaikan jika MySQL memakai password.
$dbHost = '127.0.0.1';
$dbName = 'web_tpoa';
$dbUser = 'root';
$dbPass = '';
$dbCharset = 'utf8mb4';
$dbDsn = "mysql:host={$dbHost};dbname={$dbName};charset={$dbCharset}";
$dbOptions = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false];
$pdo = new PDO($dbDsn, $dbUser, $dbPass, $dbOptions);
