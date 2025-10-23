<?php
require_once __DIR__ . '/includes/config.php';

if (empty($_GET['kalkis']) || empty($_GET['varis']) || empty($_GET['tarih'])) {
    header('Location: ' . BASE_URL . 'index.php');
    exit();
}

$kalkis_yeri = htmlspecialchars($_GET['kalkis']);
$varis_yeri = htmlspecialchars($_GET['varis']);
$tarih_str = htmlspecialchars($_GET['tarih']); 

$current_date_dt = null;
$prev_date_dt = null;
$next_date_dt = null;
$is_prev_day_disabled = true; 
try {
    $current_date_dt = new DateTime($tarih_str);
    
    $prev_date_dt = clone $current_date_dt;
    $prev_date_dt->sub(new DateInterval('P1D'));
    
    $next_date_dt = clone $current_date_dt;
    $next_date_dt->add(new DateInterval('P1D'));

    $today_dt = new DateTime('today');
    if ($prev_date_dt >= $today_dt) {
        $is_prev_day_disabled = false;
    }

} catch (Exception $e) {
    die("Geçersiz tarih formatı."); 
}

$seferler = [];
$db_error = null;
try {
    $sql = "SELECT 
                trips.*, 
                bus_companies.name AS firma_adi,
                (SELECT COUNT(bs.id) 
                 FROM tickets t 
                 JOIN booked_seats bs ON t.id = bs.ticket_id 
                 WHERE t.trip_id = trips.id AND t.status = 'active') AS booked_count 
            FROM trips 
            LEFT JOIN bus_companies ON trips.company_id = bus_companies.id
            WHERE departure_city = ? 
            AND destination_city = ? 
            AND date(departure_time) = ?
            ORDER BY departure_time ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$kalkis_yeri, $varis_yeri, $tarih_str]); 
    $seferler = $stmt->fetchAll();

} catch (PDOException $e) {
    $db_error = "Veritabanı hatası: " . $e->getMessage();
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="text-center mb-4"> 
        <h2>
            <span class="fw-bold"><?php echo $kalkis_yeri; ?></span> → 
            <span class="fw-bold"><?php echo $varis_yeri; ?></span> Seferleri
        </h2>
        
        <div class="d-flex justify-content-between align-items-center mt-3">
            <a href="?kalkis=<?php echo urlencode($kalkis_yeri); ?>&varis=<?php echo urlencode($varis_yeri); ?>&tarih=<?php echo $prev_date_dt->format('Y-m-d'); ?>" 
               class="btn btn-outline-secondary <?php if($is_prev_day_disabled) echo 'disabled'; ?>">
               ← Önceki Gün (<?php echo $prev_date_dt->format('d.m'); ?>)
            </a>
            <strong class="fs-5"><?php echo date('d.m.Y', $current_date_dt->getTimestamp()); ?></strong>
            <a href="?kalkis=<?php echo urlencode($kalkis_yeri); ?>&varis=<?php echo urlencode($varis_yeri); ?>&tarih=<?php echo $next_date_dt->format('Y-m-d'); ?>" 
               class="btn btn-outline-secondary">
               Sonraki Gün (<?php echo $next_date_dt->format('d.m'); ?>) →
            </a>
        </div>
    </div>


    <?php if ($db_error): ?>
        <div class="alert alert-danger"><?php echo $db_error; ?></div>
    <?php elseif (empty($seferler)): ?>
        <div class="alert alert-warning text-center">
            <h4>Bu tarihte aradığınız kriterlere uygun sefer bulunamadı.</h4>
            <p>Lütfen farklı bir tarih veya güzergah deneyin.</p>
            <a href="<?php echo BASE_URL; ?>index.php" class="btn btn-primary mt-2">Yeni Arama Yap</a>
        </div>
    <?php else: ?>
        <?php foreach ($seferler as $sefer): ?>
            <?php
                $departure_dt = new DateTime($sefer['departure_time']);
                $arrival_dt = new DateTime($sefer['arrival_time']);
                $interval = $departure_dt->diff($arrival_dt);
                $duration_str = '';
                if ($interval->h > 0) { $duration_str .= $interval->h . ' sa '; }
                if ($interval->i > 0) { $duration_str .= $interval->i . ' dk'; }
                $duration_str = trim($duration_str);
                $available_seats = $sefer['capacity'] - $sefer['booked_count'];
                $is_full = ($available_seats <= 0);
                $is_next_day_arrival = ($arrival_dt->format('Y-m-d') !== $departure_dt->format('Y-m-d'));
            ?>
            <div class="card mb-3 <?php if($is_full) echo 'bg-light text-muted'; ?>">
                <div class="card-body">
                    <div class="row align-items-center gy-3 gy-md-0"> 
                        <div class="col-md-3">
                            <h5 class="card-title mb-1"><?php echo htmlspecialchars($sefer['firma_adi']); ?></h5>
                            <span class="fs-4 fw-bold"><?php echo $departure_dt->format('H:i'); ?></span>
                            <small class="d-block text-muted"><?php echo htmlspecialchars($sefer['departure_city']); ?></small>
                        </div>
                        <div class="col-md-4 text-center">
                            <span class="badge bg-secondary mb-1"><?php echo $duration_str; ?></span>
                            <div><small class="text-muted"> → </small></div>
                            <small class="d-block text-muted">
                                <?php echo htmlspecialchars($sefer['destination_city']); ?>
                                <?php if ($is_next_day_arrival): ?>
                                    <span class="badge bg-warning text-dark ms-1">Ertesi Gün</span>
                                <?php endif; ?>
                            </small>
                            <small class="d-block text-muted">Varış: <?php echo $arrival_dt->format('H:i'); ?></small>
                        </div>
                        <div class="col-md-3 text-center text-md-end">
                            <span class="fs-4 fw-bold text-primary d-block mb-1"><?php echo number_format($sefer['price'], 2); ?> ₺</span>
                            <?php if (!$is_full): ?>
                                <span class="badge bg-success">Boş Koltuk: <?php echo $available_seats; ?></span>
                            <?php else: ?>
                                <span class="badge bg-danger">Doldu</span>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-2 d-grid">
                            <?php if ($is_logged_in && $user_role === 'user'): ?>
                                <?php if (!$is_full): ?>
                                    <a href="<?php echo BASE_URL; ?>bilet_al.php?trip_id=<?php echo $sefer['id']; ?>" class="btn btn-success">Bilet Al</a>
                                <?php else: ?>
                                     <button class="btn btn-secondary" disabled>Doldu</button>
                                <?php endif; ?>
                            <?php elseif (!$is_logged_in): ?>
                                <a href="<?php echo BASE_URL; ?>login.php" class="btn btn-outline-primary">Giriş Yap</a>
                            <?php else: ?>
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