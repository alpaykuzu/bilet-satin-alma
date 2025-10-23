<?php
require_once __DIR__ . '/../includes/config.php';

require_role('firma_admin');

$company_id = $_SESSION['company_id'];
$firma_adi = '';
try {
    $stmt = $pdo->prepare("SELECT name FROM bus_companies WHERE id = ?");
    $stmt->execute([$company_id]);
    $firma_adi = $stmt->fetchColumn();
} catch (PDOException $e) {
   
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-md-3">
            <div class="list-group">
                <a href="<?php echo BASE_URL; ?>firma_admin/index.php" class="list-group-item list-group-item-action active">Panel Ana Sayfa</a>
                <a href="<?php echo BASE_URL; ?>firma_admin/sefer_yonetimi.php" class="list-group-item list-group-item-action">Sefer Yönetimi</a>
                <a href="<?php echo BASE_URL; ?>firma_admin/kupon_yonetimi.php" class="list-group-item list-group-item-action">Kupon Yönetimi</a>
            </div>
        </div>
        <div class="col-md-9">
            <h2>Firma Yönetim Paneli</h2>
            <hr>
            <h4>Hoş Geldin, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h4>
            <p><strong><?php echo htmlspecialchars($firma_adi); ?></strong> firması için yönetim panelindesiniz.</p>
            <p>Sol menüden seferlerinizi ve kuponlarınızı yönetebilirsiniz.</p>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>