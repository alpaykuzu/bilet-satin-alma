<?php
require_once __DIR__ . '/includes/config.php';

try {
    $kalkis_sehirleri_stmt = $pdo->query("SELECT DISTINCT departure_city FROM trips ORDER BY departure_city");
    $kalkis_sehirleri = $kalkis_sehirleri_stmt->fetchAll(PDO::FETCH_COLUMN);

    $varis_sehirleri_stmt = $pdo->query("SELECT DISTINCT destination_city FROM trips ORDER BY destination_city");
    $varis_sehirleri = $varis_sehirleri_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $page_error_message = $_SESSION['error_message'] ?? null;
    $page_success_message = $_SESSION['success_message'] ?? null;
    unset($_SESSION['error_message'], $_SESSION['success_message']);
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="alert alert-danger">Şehirler yüklenirken bir hata oluştu: ' . htmlspecialchars($e->getMessage()) . '</div>';
    require_once __DIR__ . '/includes/footer.php';
    exit(); 
}

$page_error_message = $_SESSION['error_message'] ?? null;
$page_success_message = $_SESSION['success_message'] ?? null;
unset($_SESSION['error_message'], $_SESSION['success_message']);

require_once __DIR__ . '/includes/header.php';
?>

<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">

<div class="p-5 mb-4 bg-light rounded-3 text-center">
    <div class="container-fluid py-5">
        <h1 class="display-5 fw-bold">Yolculuğunuz Burada Başlar</h1>
        <p class="fs-4">Türkiye'nin dört bir yanına en uygun fiyatlarla seyahat edin. Hemen biletinizi arayın!</p>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h4>Sefer Ara</h4>
            </div>
            <div class="card-body">
                <?php 
                    if ($page_success_message) { echo '<div class="alert alert-success">' . $page_success_message . '</div>'; }
                    if ($page_error_message) { echo '<div class="alert alert-danger">' . $page_error_message . '</div>'; }
                ?>
                <form action="<?php echo BASE_URL; ?>seferler.php" method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="kalkis" class="form-label">Kalkış Yeri</label>
                        <select id="kalkis" name="kalkis" required placeholder="Şehir arayın veya seçin...">
                            <option value="">Şehir arayın veya seçin...</option> {/* Placeholder Tom Select tarafından kullanılacak */}
                            <?php foreach ($kalkis_sehirleri as $sehir): ?>
                                <option value="<?php echo htmlspecialchars($sehir); ?>"><?php echo htmlspecialchars($sehir); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="varis" class="form-label">Varış Yeri</label>
                        <select id="varis" name="varis" required placeholder="Şehir arayın veya seçin...">
                             <option value="">Şehir arayın veya seçin...</option> {/* Placeholder */}
                            <?php foreach ($varis_sehirleri as $sehir): ?>
                                <option value="<?php echo htmlspecialchars($sehir); ?>"><?php echo htmlspecialchars($sehir); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="tarih" class="form-label">Tarih</label>
                        <input type="date" class="form-control" id="tarih" name="tarih" required 
                               min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="submit" class="btn btn-primary">Sefer Bul</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>

<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        new TomSelect('#kalkis',{
             create: false, 
             sortField: {
                 field: "text",
                 direction: "asc"
             }
        });
        new TomSelect('#varis',{
             create: false,
             sortField: {
                 field: "text",
                 direction: "asc"
             }
        });
    });
</script>