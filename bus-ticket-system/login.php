<?php
require_once __DIR__ . '/includes/config.php';

$errors = [];
$email = '';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    if (empty($email) || empty($password)) {
        $errors[] = 'E-posta ve şifre alanları zorunludur.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['balance'] = $user['balance'];
                if ($user['role'] === 'firma_admin') { $_SESSION['company_id'] = $user['company_id']; }
                
                // Başarılı girişten sonra yönlendir ve script'i durdur.
                header('Location: ' . BASE_URL . 'index.php');
                exit();
            } else {
                $errors[] = 'Geçersiz e-posta veya şifre.';
            }
        } catch (PDOException $e) {
            $errors[] = "Veritabanı hatası: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-dark text-white"><h3 class="text-center">Giriş Yap</h3></div>
            <div class="card-body">
                <?php if (isset($_SESSION['success_message'])) { echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>'; unset($_SESSION['success_message']); } ?>
                <?php if (!empty($errors)): ?><div class="alert alert-danger"><?php foreach ($errors as $error): ?><p class="mb-0"><?php echo $error; ?></p><?php endforeach; ?></div><?php endif; ?>
                <form action="<?php echo BASE_URL; ?>login.php" method="POST">
                    <div class="mb-3"><label for="email" class="form-label">E-posta Adresi</label><input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required></div>
                    <div class="mb-3"><label for="password" class="form-label">Şifre</label><input type="password" class="form-control" id="password" name="password" required></div>
                    <div class="d-grid"><button type="submit" class="btn btn-primary">Giriş Yap</button></div>
                </form>
            </div>
            <div class="card-footer text-center">Hesabınız yok mu? <a href="<?php echo BASE_URL; ?>register.php">Kayıt Olun</a></div>
        </div>
        <div class="card mt-4">
            <div class="card-header bg-info text-dark"><strong>Test Kullanıcıları</strong></div>
            <div class="card-body">
                <p class="mb-1"><strong>Admin:</strong></p><ul class="list-unstyled"><li>E-posta: <code>admin@bilet.com</code></li><li>Şifre: <code>AdminSifre123!</code></li></ul><hr>
                <p class="mb-1"><strong>Firma Yetkilisi:</strong></p><ul class="list-unstyled"><li>E-posta: <code>firma@bilet.com</code></li><li>Şifre: <code>FirmaSifre123!</code></li></ul><hr>
                <p class="mb-1"><strong>Normal Kullanıcı:</strong></p><ul class="list-unstyled"><li>E-posta: <code>user@bilet.com</code></li><li>Şifre: <code>Kullanici123!</code></li></ul>
            </div>
        </div>
    </div>
</div>
<?php
require_once __DIR__ . '/includes/footer.php';
?>