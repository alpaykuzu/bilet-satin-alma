<?php
require_once __DIR__ . '/includes/config.php';

require_role('user');

if (!isset($_GET['id'])) {
    header('Location: ' . BASE_URL . 'biletlerim.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$ticket_id = $_GET['id'];

$pdo->beginTransaction();

try {
    $sql = "SELECT t.*, tr.departure_time FROM tickets t JOIN trips tr ON t.trip_id = tr.id WHERE t.id = ? AND t.user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$ticket_id, $user_id]);
    $ticket = $stmt->fetch();

    if (!$ticket) { throw new Exception("Geçersiz bilet veya yetkiniz yok."); }
    if ($ticket['status'] !== 'active') { throw new Exception("Bu bilet zaten iptal edilmiş."); }

    $kalkis_zamani = new DateTime($ticket['departure_time']);
    $simdiki_zaman = new DateTime();
    $fark_saat = ($kalkis_zamani->getTimestamp() - $simdiki_zaman->getTimestamp()) / 3600;
    if ($kalkis_zamani < $simdiki_zaman || $fark_saat < 1) { throw new Exception("Sefere 1 saatten az kaldığı için bilet iptal edilemez."); }

    $update_ticket_stmt = $pdo->prepare("UPDATE tickets SET status = 'cancelled' WHERE id = ?");
    $update_ticket_stmt->execute([$ticket_id]);

    $iade_tutari = $ticket['total_price'];
    $update_balance_stmt = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
    $update_balance_stmt->execute([$iade_tutari, $user_id]);

    if (!empty($ticket['coupon_id'])) {
        $stmt_uc = $pdo->prepare("DELETE FROM user_coupons WHERE id = (SELECT id FROM user_coupons WHERE coupon_id = ? AND user_id = ? ORDER BY created_at DESC LIMIT 1)");
        $stmt_uc->execute([$ticket['coupon_id'], $user_id]);
    }

    $pdo->commit();
    $_SESSION['balance'] += $iade_tutari;
    $_SESSION['success_message'] = "Biletiniz iptal edildi. " . number_format($iade_tutari, 2) . " ₺ iade edildi.";

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error_message'] = "İptal başarısız: " . $e->getMessage();
}

header('Location: ' . BASE_URL . 'biletlerim.php');
exit();
?>