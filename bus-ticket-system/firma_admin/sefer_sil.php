<?php
require_once __DIR__ . '/../includes/config.php';

require_role('firma_admin');

if (!isset($_GET['id'])) {
    $_SESSION['error_message'] = 'Geçersiz istek.';
    header('Location: ' . BASE_URL . 'firma_admin/sefer_yonetimi.php');
    exit();
}

$trip_id = $_GET['id'];
$company_id = $_SESSION['company_id'];

try {
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE trip_id = ?");
    $stmt_check->execute([$trip_id]);
    if ($stmt_check->fetchColumn() > 0) {
        throw new Exception("Bu sefere ait biletler satıldığı için sefer silinemez.");
    }

    $stmt = $pdo->prepare("DELETE FROM trips WHERE id = ? AND company_id = ?");
    $stmt->execute([$trip_id, $company_id]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['success_message'] = 'Sefer başarıyla silindi.';
    } else {
        throw new Exception("Sefer bulunamadı veya silme yetkiniz yok.");
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Silme işlemi başarısız: ' . $e->getMessage();
}

header('Location: ' . BASE_URL . 'firma_admin/sefer_yonetimi.php');
exit();
?>