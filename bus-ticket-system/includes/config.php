<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', 1);
error_reporting(E_ALL);


if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
    $dir_name = str_replace('\\', '/', __DIR__);
    $project_path_full = str_replace($doc_root, '', $dir_name);
    $project_path = dirname($project_path_full);
    if ($project_path === '/' || $project_path === '\\') { $project_path = ''; }
    define('BASE_URL', $protocol . '://' . $host . $project_path . '/');
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

$is_logged_in = isset($_SESSION['user_id']);
$user_role = $is_logged_in ? $_SESSION['role'] : null;
?>