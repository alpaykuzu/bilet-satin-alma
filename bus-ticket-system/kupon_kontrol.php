<?php
require_once __DIR__ . '/includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['code'])) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
    exit();
}

$code = strtoupper(trim($_POST['code']));
$response = ['success' => false, 'message' => 'Geçersiz veya süresi dolmuş kupon kodu.'];

try {
    $stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND expire_date >= date('now')");
    $stmt->execute([$code]);
    $coupon = $stmt->fetch();

    if ($coupon) {
        $stmt_usage = $pdo->prepare("SELECT COUNT(*) FROM user_coupons WHERE coupon_id = ?");
        $stmt_usage->execute([$coupon['id']]);
        $times_used = $stmt_usage->fetchColumn();

        if ($times_used >= $coupon['usage_limit']) {
            $response = ['success' => false, 'message' => 'Bu kupon kullanım limitine ulaşmıştır.'];
        } else {
            $response = [
                'success' => true,
                'message' => '%' . $coupon['discount'] . ' indirim uygulandı!',
                'discount' => $coupon['discount']
            ];
        }
    }
} catch (PDOException $e) {
    $response = ['success' => false, 'message' => 'Veritabanı hatası oluştu.'];
}

echo json_encode($response);
?>