<?php
require_once __DIR__ . '/../includes/config.php';

require_role('admin');
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim($_POST['code']));
    $discount = trim($_POST['discount']);
    $usage_limit = trim($_POST['usage_limit']);
    $expire_date = trim($_POST['expire_date']);
    $action = $_POST['action'];
    $coupon_id = $_POST['coupon_id'] ?? null;

    if (empty($code) || empty($discount) || empty($usage_limit) || empty($expire_date)) { $errors[] = "Tüm alanlar zorunludur."; }
    if (!is_numeric($discount) || $discount < 1 || $discount > 99) { $errors[] = "İndirim oranı 1 ile 99 arasında olmalıdır."; }
    if (!ctype_digit($usage_limit) || $usage_limit < 1) { $errors[] = "Kullanım limiti pozitif bir tam sayı olmalıdır."; }

    if (empty($errors)) { 
        $sql_check_code = "SELECT id FROM coupons WHERE code = ?";
        $params_check_code = [$code];
        if ($action === 'edit' && $coupon_id) {
            $sql_check_code .= " AND id != ?";
            $params_check_code[] = $coupon_id;
        }
        $stmt_check_code = $pdo->prepare($sql_check_code);
        $stmt_check_code->execute($params_check_code);
        if ($stmt_check_code->fetch()) {
            $errors[] = "Bu kupon kodu zaten kullanılıyor (firma veya genel). Lütfen farklı bir kod girin.";
        }
    }

    if (empty($errors)) {
        try {
            if ($action === 'add') {
                $new_coupon_id = generate_uuid();
                $stmt = $pdo->prepare("INSERT INTO coupons (id, code, discount, company_id, usage_limit, expire_date, status, created_at) VALUES (?, ?, ?, NULL, ?, ?, 'active', ?)");
                $stmt->execute([$new_coupon_id, $code, $discount, $usage_limit, $expire_date, date('Y-m-d H:i:s')]);
                $_SESSION['success_message'] = 'Yeni genel kupon başarıyla oluşturuldu.';
            } elseif ($action === 'edit' && $coupon_id) {
                 $stmt_check_usage = $pdo->prepare("SELECT COUNT(*) FROM user_coupons WHERE coupon_id = ?");
                 $stmt_check_usage->execute([$coupon_id]);
                 if ($stmt_check_usage->fetchColumn() > 0) {
                      throw new Exception("Bu kupon daha önce kullanıldığı için düzenlenemez. Sadece pasif yapabilirsiniz.");
                 }
                $stmt = $pdo->prepare("UPDATE coupons SET code = ?, discount = ?, usage_limit = ?, expire_date = ? WHERE id = ? AND company_id IS NULL");
                $stmt->execute([$code, $discount, $usage_limit, $expire_date, $coupon_id]);
                $_SESSION['success_message'] = 'Genel kupon başarıyla güncellendi.';
            }
            header('Location: ' . BASE_URL . 'admin/kupon_yonetimi.php');
            exit();
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { $errors[] = "Bu kupon kodu zaten mevcut."; }
            else { $errors[] = 'Veritabanı hatası: ' . $e->getMessage(); }
        } catch (Exception $e) {
             $errors[] = $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';

$edit_mode = false;
$edit_coupon = null;
$edit_coupon_used = false;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    if (empty($errors)) {
        $edit_mode = true;
        $stmt = $pdo->prepare("SELECT c.*, (SELECT COUNT(*) FROM user_coupons uc WHERE uc.coupon_id = c.id) as usage_count FROM coupons c WHERE c.id = ? AND c.company_id IS NULL");
        $stmt->execute([$_GET['id']]);
        $edit_coupon = $stmt->fetch();
        if (!$edit_coupon) { $edit_mode = false; $_SESSION['error_message'] = 'Geçersiz düzenleme isteği.'; }
        else { $edit_coupon_used = ($edit_coupon['usage_count'] > 0); }
         if ($edit_coupon_used) {
             $_SESSION['error_message'] = 'Bu kupon kullanıldığı için düzenlenemez, sadece durumu değiştirilebilir.';
         }
    }
}

$coupons_stmt = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM user_coupons uc WHERE uc.coupon_id = c.id) as usage_count FROM coupons c WHERE c.company_id IS NULL ORDER BY c.created_at DESC");
$coupons_data = $coupons_stmt->fetchAll();

$page_error_message = $_SESSION['error_message'] ?? null;
$page_success_message = $_SESSION['success_message'] ?? null;
unset($_SESSION['error_message'], $_SESSION['success_message']);
?>

<div class="container">
    <div class="row">
        <div class="col-md-3">
             <div class="list-group">
                <a href="<?php echo BASE_URL; ?>admin/index.php" class="list-group-item list-group-item-action">Panel Ana Sayfa</a>
                <a href="<?php echo BASE_URL; ?>admin/firma_yonetimi.php" class="list-group-item list-group-item-action">Firma Yönetimi</a>
                <a href="<?php echo BASE_URL; ?>admin/firma_admin_yonetimi.php" class="list-group-item list-group-item-action">Firma Admin Yönetimi</a>
                <a href="<?php echo BASE_URL; ?>admin/kupon_yonetimi.php" class="list-group-item list-group-item-action active">Genel Kupon Yönetimi</a>
            </div>
        </div>

        <div class="col-md-9">
            <div class="card mb-4">
                <div class="card-header">
                    <h3><?php echo $edit_mode ? 'Genel Kuponu Düzenle' : 'Yeni Genel Kupon Oluştur'; ?></h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger"><?php foreach ($errors as $error) echo "<p class='mb-0'>$error</p>"; ?></div>
                    <?php endif; ?>
                     <?php 
                    if ($page_error_message && $edit_mode && !$edit_coupon) {
                        echo '<div class="alert alert-danger">' . $page_error_message . '</div>';
                    } elseif ($page_error_message && $edit_mode && $edit_coupon_used) {
                         echo '<div class="alert alert-warning">' . $page_error_message . '</div>';
                    }

                    if (!$page_error_message || ($edit_mode && $edit_coupon)) { ?>
                    <form action="<?php echo BASE_URL; ?>admin/kupon_yonetimi.php<?php echo $edit_mode ? '?action=edit&id='.$edit_coupon['id'] : ''; ?>" method="POST">
                        <input type="hidden" name="action" value="<?php echo $edit_mode ? 'edit' : 'add'; ?>">
                        <?php if ($edit_mode): ?>
                            <input type="hidden" name="coupon_id" value="<?php echo htmlspecialchars($edit_coupon['id']); ?>">
                        <?php endif; ?>
                        <div class="row">
                            <div class="col-md-6 mb-3"><label for="code" class="form-label">Kupon Kodu</label><input type="text" class="form-control" name="code" value="<?php echo htmlspecialchars($_POST['code'] ?? $edit_coupon['code'] ?? ''); ?>" <?php if($edit_mode && $edit_coupon_used) echo 'readonly'; ?> required></div>
                            <div class="col-md-6 mb-3"><label for="discount" class="form-label">İndirim Oranı (%)</label><input type="number" class="form-control" name="discount" value="<?php echo htmlspecialchars($_POST['discount'] ?? $edit_coupon['discount'] ?? ''); ?>" placeholder="Örn: 20" <?php if($edit_mode && $edit_coupon_used) echo 'readonly'; ?> required></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3"><label for="usage_limit" class="form-label">Kullanım Limiti</label><input type="number" class="form-control" name="usage_limit" value="<?php echo htmlspecialchars($_POST['usage_limit'] ?? $edit_coupon['usage_limit'] ?? ''); ?>" <?php if($edit_mode && $edit_coupon_used) echo 'readonly'; ?> required></div>
                            <div class="col-md-6 mb-3"><label for="expire_date" class="form-label">Son Kullanma Tarihi</label><input type="date" class="form-control" name="expire_date" value="<?php echo htmlspecialchars($_POST['expire_date'] ?? $edit_coupon['expire_date'] ?? ''); ?>" min="<?php echo date('Y-m-d'); ?>" <?php if($edit_mode && $edit_coupon_used) echo 'readonly'; ?> required></div>
                        </div>
                         <?php if(!($edit_mode && $edit_coupon_used)): ?>
                            <button type="submit" class="btn btn-primary"><?php echo $edit_mode ? 'Güncelle' : 'Oluştur'; ?></button>
                        <?php endif; ?>
                        <?php if ($edit_mode): ?><a href="<?php echo BASE_URL; ?>admin/kupon_yonetimi.php" class="btn btn-secondary">İptal</a><?php endif; ?>
                    </form>
                    <?php } ?>
                </div>
            </div>

            <h3>Genel Kuponlar</h3><hr>
            <?php if ($page_success_message) { echo '<div class="alert alert-success">' . $page_success_message . '</div>'; } ?>
            <?php if ($page_error_message && $_SERVER['REQUEST_METHOD'] !== 'POST' && !$edit_mode) { echo '<div class="alert alert-danger">' . $page_error_message . '</div>'; } ?>

            <table class="table table-striped table-bordered table-sm">
                <thead class="table-dark">
                    <tr>
                        <th>Kod</th>
                        <th>İndirim (%)</th>
                        <th>Limit / Kullanılan</th>
                        <th>Son Tarih</th>
                        <th>Durum</th>
                        <th class="text-center">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($coupons_data as $coupon): ?>
                    <?php $is_used = ($coupon['usage_count'] > 0); ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($coupon['code']); ?></strong></td>
                        <td><?php echo htmlspecialchars($coupon['discount']); ?>%</td>
                        <td><?php echo htmlspecialchars($coupon['usage_limit']); ?> / <?php echo $coupon['usage_count']; ?></td>
                        <td><?php echo date('d.m.Y', strtotime($coupon['expire_date'])); ?></td>
                        <td>
                            <?php if ($coupon['status'] === 'active'): ?>
                                <span class="badge bg-success">Aktif</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Pasif</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <a href="<?php echo BASE_URL; ?>admin/kupon_yonetimi.php?action=edit&id=<?php echo $coupon['id']; ?>" class="btn btn-sm btn-warning <?php if($is_used) echo 'disabled'; ?>">Düzenle</a>
                            
                            <?php ?>
                            <?php if ($coupon['status'] === 'active'): ?>
                                <a href="<?php echo BASE_URL; ?>admin/kupon_sil.php?id=<?php echo $coupon['id']; ?>" class="btn btn-sm btn-secondary" onclick="return confirm('Bu kuponu PASİF yapmak istediğinizden emin misiniz?');">Pasif Yap</a>
                            <?php else: ?>
                                <a href="<?php echo BASE_URL; ?>admin/kupon_sil.php?id=<?php echo $coupon['id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Bu kuponu AKTİF yapmak istediğinizden emin misiniz?');">Aktif Yap</a>
                            <?php endif; ?>
                            <?php ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($coupons_data)): ?><tr><td colspan="6" class="text-center">Henüz genel kupon oluşturulmamış.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>