<?php
require_once __DIR__ . '/../includes/config.php';

require_role('admin');
$errors = [];

if (!isset($_GET['id'])) {
    header('Location: ' . BASE_URL . 'admin/firma_admin_yonetimi.php');
    exit();
}
$user_id_to_edit = $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $company_id = $_POST['company_id'];

    if (empty($full_name) || empty($email) || empty($company_id)) { $errors[] = "Ad Soyad, E-posta ve Firma alanları zorunludur."; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = "Geçersiz e-posta adresi."; }
    else {
        $stmt_check = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt_check->execute([$email, $user_id_to_edit]);
        if ($stmt_check->fetch()) { $errors[] = "Bu e-posta adresi başka bir kullanıcı tarafından kullanılıyor."; }
    }
    if (!empty($password) && strlen($password) < 8) { $errors[] = "Yeni şifre en az 8 karakter olmalıdır."; }
    
    if (empty($errors)) {
        try {
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, password = ?, company_id = ? WHERE id = ?");
                $stmt->execute([$full_name, $email, $hashed_password, $company_id, $user_id_to_edit]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, company_id = ? WHERE id = ?");
                $stmt->execute([$full_name, $email, $company_id, $user_id_to_edit]);
            }
            $_SESSION['success_message'] = 'Firma admini başarıyla güncellendi.';
            header('Location: ' . BASE_URL . 'admin/firma_admin_yonetimi.php');
            exit();
        } catch (PDOException $e) {
            $errors[] = "Veritabanı hatası: " . $e->getMessage();
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'firma_admin'");
$stmt->execute([$user_id_to_edit]);
$admin_to_edit = $stmt->fetch();

if (!$admin_to_edit) {
    $_SESSION['error_message'] = "Düzenlenecek kullanıcı bulunamadı.";
    header('Location: ' . BASE_URL . 'admin/firma_admin_yonetimi.php');
    exit();
}
$companies = $pdo->query("SELECT id, name FROM bus_companies ORDER BY name")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
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
            <div class="card">
                <div class="card-header"><h3>Firma Adminini Düzenle</h3></div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error) { echo "<p class='mb-0'>$error</p>"; } ?>
                        </div>
                    <?php endif; ?>
                    <form action="<?php echo BASE_URL; ?>admin/firma_admin_duzenle.php?id=<?php echo $user_id_to_edit; ?>" method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="full_name" class="form-label">Adı Soyadı</label>
                                <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($admin_to_edit['full_name']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">E-posta Adresi</label>
                                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($admin_to_edit['email']); ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Yeni Şifre</label>
                                <input type="password" class="form-control" name="password">
                                <div class="form-text">Değiştirmek istemiyorsanız bu alanı boş bırakın.</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="company_id" class="form-label">Atanacak Firma</label>
                                <select class="form-select" name="company_id" required>
                                    <?php foreach ($companies as $company): ?>
                                        <option value="<?php echo $company['id']; ?>" <?php if ($company['id'] === $admin_to_edit['company_id']) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($company['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Güncelle</button>
                        <a href="<?php echo BASE_URL; ?>admin/firma_admin_yonetimi.php" class="btn btn-secondary">İptal</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>