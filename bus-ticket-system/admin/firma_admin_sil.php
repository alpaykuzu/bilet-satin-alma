<?php
require_once __DIR__ . '/../includes/config.php';

require_role('admin');

if (!isset($_GET['id'])) {
    $_SESSION['error_message'] = 'Geçersiz istek.';
    header('Location: ' . BASE_URL . 'admin/firma_admin_yonetimi.php');
    exit();
}

$user_id_to_delete = $_GET['id'];

try {
    $stmt_find = $pdo->prepare("SELECT company_id FROM users WHERE id = ?");
    $stmt_find->execute([$user_id_to_delete]);
    $company_id = $stmt_find->fetchColumn();

    if ($company_id) {
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE company_id = ? AND id != ?");
        $stmt_check->execute([$company_id, $user_id_to_delete]);
        $other_admins_count = $stmt_check->fetchColumn();

        if ($other_admins_count < 1) {
            throw new Exception("Bu kullanıcı, bir firmanın son yetkilisi olduğu için silinemez. Lütfen önce firmaya yeni bir yetkili atayın.");
        }
    }
    
    $stmt_delete = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'firma_admin'");
    $stmt_delete->execute([$user_id_to_delete]);

    if ($stmt_delete->rowCount() > 0) {
        $_SESSION['success_message'] = 'Firma admini başarıyla silindi.';
    } else {
        throw new Exception("Kullanıcı bulunamadı veya silme yetkiniz yok.");
    }

} catch (Exception $e) {
    $_SESSION['error_message'] = 'Silme işlemi başarısız: ' . $e->getMessage();
}

header('Location: ' . BASE_URL . 'admin/firma_admin_yonetimi.php');
exit();
?>