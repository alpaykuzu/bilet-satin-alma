<?php
require_once __DIR__ . '/includes/config.php';

require_role('user');

$user_id = $_SESSION['user_id'];
$biletler = [];
$db_error = null;

try {
    $sql = "
        SELECT 
            t.id AS ticket_id, t.pnr_code, t.status, t.total_price, t.created_at AS purchase_date,
            tr.departure_city, tr.destination_city, tr.departure_time,
            bc.name AS company_name,
            GROUP_CONCAT(bs.seat_number, ', ') AS seat_numbers 
        FROM tickets t
        JOIN trips tr ON t.trip_id = tr.id
        JOIN bus_companies bc ON tr.company_id = bc.id
        JOIN booked_seats bs ON bs.ticket_id = t.id
        WHERE t.user_id = ?
        GROUP BY t.id
        ORDER BY tr.departure_time DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $biletler = $stmt->fetchAll();

} catch (PDOException $e) {
    $db_error = 'Biletler yüklenirken bir hata oluştu: ' . $e->getMessage();
}

$page_error_message = $_SESSION['error_message'] ?? $db_error;
$page_success_message = $_SESSION['success_message'] ?? null;
unset($_SESSION['error_message'], $_SESSION['success_message']);

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <h2 class="mb-4">Biletlerim</h2>

    <?php 
        if ($page_success_message) { echo '<div class="alert alert-success">' . $page_success_message . '</div>'; }
        if ($page_error_message) { echo '<div class="alert alert-danger">' . $page_error_message . '</div>'; }
    ?>

    <?php if (empty($biletler) && !$page_error_message): ?>
        <div class="alert alert-info text-center">
            <h4>Henüz satın alınmış biletiniz bulunmamaktadır.</h4>
            <a href="<?php echo BASE_URL; ?>index.php" class="btn btn-primary mt-2">Hemen Bilet Al</a>
        </div>
    <?php elseif (!empty($biletler)): ?>
        <?php foreach ($biletler as $bilet): ?>
            <?php
                $kart_rengi = '';
                $is_active = ($bilet['status'] === 'active');
                $is_cancelled = ($bilet['status'] === 'cancelled');
                
                if ($is_cancelled) {
                    $kart_rengi = 'bg-light text-muted opacity-75'; 
                }
            ?>
            <div class="card mb-3 <?php echo $kart_rengi; ?>">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0 d-inline me-3"><?php echo htmlspecialchars($bilet['company_name']); ?></h5>
                        <small class="text-muted">PNR: <strong><?php echo htmlspecialchars($bilet['pnr_code']); ?></strong></small>
                    </div>
                    <?php if ($is_active): ?>
                        <span class="badge bg-success">Aktif</span>
                    <?php elseif ($is_cancelled): ?>
                        <span class="badge bg-danger">İptal Edildi</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Geçmiş</span> 
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Güzergah:</strong> <?php echo htmlspecialchars($bilet['departure_city']); ?> → <?php echo htmlspecialchars($bilet['destination_city']); ?></p>
                            <p><strong>Kalkış Zamanı:</strong> <?php echo date('d.m.Y H:i', strtotime($bilet['departure_time'])); ?></p>
                            <p><strong>Koltuk No:</strong> <?php echo htmlspecialchars($bilet['seat_numbers']); ?></p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <p><strong>Toplam Fiyat:</strong> <?php echo number_format($bilet['total_price'], 2); ?> ₺</p>
                            <p><strong>Satın Alım Tarihi:</strong> <?php echo date('d.m.Y H:i', strtotime($bilet['purchase_date'])); ?></p>
                            <?php
                                $kalkis_zamani = new DateTime($bilet['departure_time']);
                                $simdiki_zaman = new DateTime();
                                $iptal_edilebilir = false;
                                if ($kalkis_zamani > $simdiki_zaman) {
                                    $fark_saniye = $kalkis_zamani->getTimestamp() - $simdiki_zaman->getTimestamp();
                                    $iptal_edilebilir = ($is_active && $fark_saniye >= 3600); 
                                }
                            ?>

                            <?php ?>
                            <?php ?>
                            <?php if ($is_active): ?>
                                <a href="<?php echo BASE_URL; ?>bilet_pdf.php?id=<?php echo $bilet['ticket_id']; ?>" class="btn btn-info btn-sm">PDF İndir</a>
                            <?php else: ?>
                                <button class="btn btn-info btn-sm disabled">PDF İndir</button>
                            <?php endif; ?>
                            <?php ?>

                            <?php if ($iptal_edilebilir): ?>
                                <a href="<?php echo BASE_URL; ?>bilet_iptal.php?id=<?php echo $bilet['ticket_id']; ?>" 
                                   class="btn btn-warning btn-sm" 
                                   onclick="return confirm('Bu bileti iptal etmek istediğinizden emin misiniz? Bilet ücreti hesabınıza iade edilecektir.');">
                                   İptal Et
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>