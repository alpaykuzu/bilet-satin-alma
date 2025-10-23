<?php
require_once __DIR__ . '/../includes/config.php';

require_role('firma_admin');
$company_id = $_SESSION['company_id'];

if (!isset($_GET['id'])) {
    $_SESSION['error_message'] = 'Geçersiz istek.';
    header('Location: ' . BASE_URL . 'firma_admin/kupon_yonetimi.php');
    exit();
}
$coupon_id = $_GET['id'];

try {
    $stmt_get = $pdo->prepare("SELECT status FROM coupons WHERE id = ? AND company_id = ?");
    $stmt_get->execute([$coupon_id, $company_id]);
    $current_status = $stmt_get->fetchColumn();

    if ($current_status === false) {
        throw new Exception("Firma kuponu bulunamadı veya yetkiniz yok.");
    }

    $new_status = ($current_status === 'active') ? 'inactive' : 'active';

    $stmt_update = $pdo->prepare("UPDATE coupons SET status = ? WHERE id = ? AND company_id = ?");
    $stmt_update->execute([$new_status, $coupon_id, $company_id]);

    $_SESSION['success_message'] = 'Kupon durumu başarıyla "' . ($new_status === 'active' ? 'Aktif' : 'Pasif') . '" olarak güncellendi.';

} catch (Exception $e) {
    $_SESSION['error_message'] = 'Durum güncelleme başarısız: ' . $e->getMessage();
}

header('Location: ' . BASE_URL . 'firma_admin/kupon_yonetimi.php');
exit();
?>