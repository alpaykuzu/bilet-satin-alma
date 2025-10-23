<?php
require_once __DIR__ . '/../includes/config.php';

require_role('admin');

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-md-3">
            <div class="list-group">
                <a href="<?php echo BASE_URL; ?>admin/index.php" class="list-group-item list-group-item-action active">Panel Ana Sayfa</a>
                <a href="<?php echo BASE_URL; ?>admin/firma_yonetimi.php" class="list-group-item list-group-item-action">Firma Yönetimi</a>
                <a href="<?php echo BASE_URL; ?>admin/firma_admin_yonetimi.php" class="list-group-item list-group-item-action">Firma Admin Yönetimi</a>
                <a href="<?php echo BASE_URL; ?>admin/kupon_yonetimi.php" class="list-group-item list-group-item-action">Genel Kupon Yönetimi</a>
            </div>
        </div>

        <div class="col-md-9">
            <h2>Sistem Yönetim Paneli</h2>
            <hr>
            <h4>Hoş Geldin, Süper Admin!</h4>
            <p>Bu panel üzerinden tüm otobüs firmalarını, firma yetkililerini ve sistem genelindeki indirim kuponlarını yönetebilirsiniz.</p>
            <p>Lütfen işlem yapmak için sol menüyü kullanın.</p>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>