<?php
function generate_uuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

function generate_pnr(PDO $pdo) {
    $karakterler = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $pnr_uzunluk = 8;
    do {
        $pnr = '';
        for ($i = 0; $i < $pnr_uzunluk; $i++) {
            $pnr .= $karakterler[rand(0, strlen($karakterler) - 1)];
        }
        $stmt = $pdo->prepare("SELECT id FROM tickets WHERE pnr_code = ?");
        $stmt->execute([$pnr]);
        $exists = $stmt->fetchColumn();
    } while ($exists);
    return $pnr;
}

function require_role($required_role) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $required_role) {
        $_SESSION['error_message'] = "Bu sayfaya erişim yetkiniz yok.";
        header('Location: ' . BASE_URL . 'index.php');
        exit();
    }
}
?>