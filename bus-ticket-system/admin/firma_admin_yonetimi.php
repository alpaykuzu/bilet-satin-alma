<?php
require_once __DIR__ . '/../includes/config.php';

require_role('admin');
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'add') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $company_id = $_POST['company_id'];

    if (empty($full_name) || empty($email) || empty($password) || empty($company_id)) {
        $errors[] = "Tüm alanlar zorunludur.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Geçersiz e-posta adresi.";
    } else {
        $stmt_check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt_check->execute([$email]);
        if ($stmt_check->fetch()) {
            $errors[] = "Bu e-posta adresi zaten kullanılıyor.";
        }
    }
    if (strlen($password) < 8) {
        $errors[] = "Şifre en az 8 karakter olmalıdır.";
    }

    if (empty($errors)) {
        try {
            $new_user_id = generate_uuid();
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare(
                "INSERT INTO users (id, full_name, email, password, role, company_id, created_at)
                 VALUES (?, ?, ?, ?, 'firma_admin', ?, ?)"
            );
            $stmt->execute([$new_user_id, $full_name, $email, $hashed_password, $company_id, date('Y-m-d H:i:s')]);
            $_SESSION['success_message'] = 'Yeni firma admini başarıyla oluşturuldu ve atandı.';
            header('Location: ' . BASE_URL . 'admin/firma_admin_yonetimi.php');
            exit();
        } catch (PDOException $e) {
            $errors[] = "Veritabanı hatası: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';

$companies = $pdo->query("SELECT id, name FROM bus_companies ORDER BY name")->fetchAll();

$firma_admins = $pdo->query(
    "SELECT u.id, u.full_name, u.email, bc.name as company_name 
     FROM users u 
     LEFT JOIN bus_companies bc ON u.company_id = bc.id
     WHERE u.role = 'firma_admin'
     ORDER BY u.full_name"
)->fetchAll();
?>

<div class="container">
    <div class="row">
        <div class="col-md-3">
             <div class="list-group">
                <a href="<?php echo BASE_URL; ?>admin/index.php" class="list-group-item list-group-item-action">Panel Ana Sayfa</a>
                <a href="<?php echo BASE_URL; ?>admin/firma_yonetimi.php" class="list-group-item list-group-item-action">Firma Yönetimi</a>
                <a href="<?php echo BASE_URL; ?>admin/firma_admin_yonetimi.php" class="list-group-item list-group-item-action active">Firma Admin Yönetimi</a>
                <a href="<?php echo BASE_URL; ?>admin/kupon_yonetimi.php" class="list-group-item list-group-item-action">Genel Kupon Yönetimi</a>
            </div>
        </div>

        <div class="col-md-9">
            <div class="card mb-4">
                <div class="card-header"><h3>Yeni Firma Admini Ekle</h3></div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger"><?php foreach ($errors as $error) echo "<p class='mb-0'>$error</p>"; ?></div>
                    <?php endif; ?>
                    <form action="<?php echo BASE_URL; ?>admin/firma_admin_yonetimi.php" method="POST">
                        <input type="hidden" name="action" value="add">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="full_name" class="form-label">Adı Soyadı</label>
                                <input type="text" class="form-control" name="full_name" required>
                            </div>
                             <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">E-posta Adresi</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                        </div>
                         <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Geçici Şifre</label>
                                <input type="password" class="form-control" name="password" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="company_id" class="form-label">Atanacak Firma</label>
                                <select class="form-select" name="company_id" required>
                                    <option value="" disabled selected>Firma Seçiniz...</option>
                                    <?php foreach ($companies as $company): ?>
                                        <option value="<?php echo $company['id']; ?>"><?php echo htmlspecialchars($company['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Oluştur ve Ata</button>
                    </form>
                </div>
            </div>

            <h3>Mevcut Firma Adminleri</h3>
            <hr>
            <?php 
                if (isset($_SESSION['success_message'])) { echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>'; unset($_SESSION['success_message']); }
            ?>
            <table class="table table-striped table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Adı Soyadı</th>
                        <th>E-posta</th>
                        <th>Atandığı Firma</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($firma_admins as $admin): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($admin['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($admin['email']); ?></td>
                        <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($admin['company_name'] ?? 'Atanmamış'); ?></span></td>
                        <td>
                            <a href="<?php echo BASE_URL; ?>admin/firma_admin_duzenle.php?id=<?php echo $admin['id']; ?>" class="btn btn-sm btn-warning">Düzenle</a>
                            <a href="<?php echo BASE_URL; ?>admin/firma_admin_sil.php?id=<?php echo $admin['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz?');">Sil</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                     <?php if (empty($firma_admins)): ?>
                        <tr><td colspan="4" class="text-center">Kayıtlı firma admini bulunmamaktadır.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>