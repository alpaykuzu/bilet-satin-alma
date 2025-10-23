<?php
define('ROOT_PATH', dirname(__DIR__));
$db_path = ROOT_PATH . '/veritabani.sqlite';

try {
    $pdo = new PDO('sqlite:' . $db_path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON;');
} catch (PDOException $e) {
    die("Veritabanı bağlantısı başarısız oldu: " . $e->getMessage());
}
?>