<?php
require_once __DIR__ . '/includes/config.php';

$errors = [];
$full_name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    if (empty($full_name)) { $errors[] = "Tam ad alanı zorunludur."; }
    if (empty($email)) { $errors[] = "E-posta alanı zorunludur."; }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = "Lütfen geçerli bir e-posta adresi girin."; }
    else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) { $errors[] = "Bu e-posta adresi zaten kullanılıyor."; }
    }
    if (empty($password)) { $errors[] = "Şifre alanı zorunludur."; }
    elseif (strlen($password) < 8) { $errors[] = "Şifre en az 8 karakter uzunluğunda olmalıdır."; }
    elseif (!preg_match('/[A-Z]/', $password)) { $errors[] = "Şifre en az bir büyük harf içermelidir."; }
    elseif (!preg_match('/[a-z]/', $password)) { $errors[] = "Şifre en az bir küçük harf içermelidir."; }
    elseif (!preg_match('/[0-9]/', $password)) { $errors[] = "Şifre en az bir rakam içermelidir."; }
    elseif (!preg_match('/[\W_]/', $password)) { $errors[] = "Şifre en az bir özel karakter içermelidir."; }
    elseif ($password !== $password_confirm) { $errors[] = "Şifreler uyuşmuyor."; }

    if (empty($errors)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $user_id = generate_uuid();
            $stmt = $pdo->prepare("INSERT INTO users (id, full_name, email, role, password, created_at) VALUES (?, ?, ?, 'user', ?, ?)");
            $stmt->execute([$user_id, $full_name, $email, $hashed_password, date('Y-m-d H:i:s')]);
            
            $_SESSION['success_message'] = "Kayıt başarılı! Şimdi giriş yapabilirsiniz.";
            header("Location: " . BASE_URL . "login.php");
            exit();
        } catch (PDOException $e) {
            $errors[] = "Bir veritabanı hatası oluştu.";
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-dark text-white"><h3 class="text-center">Kayıt Ol</h3></div>
            <div class="card-body">
                <?php if (!empty($errors)): ?><div class="alert alert-danger"><?php foreach ($errors as $error): ?><p class="mb-0"><?php echo $error; ?></p><?php endforeach; ?></div><?php endif; ?>
                <form action="<?php echo BASE_URL; ?>register.php" method="POST" novalidate>
                    <div class="mb-3"><label for="full_name" class="form-label">Tam Adınız</label><input type="text" class="form-control" id="full_name" name="full_name" required value="<?php echo htmlspecialchars($full_name); ?>"></div>
                    <div class="mb-3"><label for="email" class="form-label">E-posta Adresiniz</label><input type="email" class="form-control" id="email" name="email" required value="<?php echo htmlspecialchars($email); ?>"></div>
                    <div class="mb-3"><label for="password" class="form-label">Şifre</label><input type="password" class="form-control" id="password" name="password" required><div class="form-text">Şifreniz en az 8 karakter olmalı; büyük harf, küçük harf, rakam ve özel karakter içermelidir.</div></div>
                    <div class="mb-3"><label for="password_confirm" class="form-label">Şifre (Tekrar)</label><input type="password" class="form-control" id="password_confirm" name="password_confirm" required></div>
                    <div class="d-grid"><button type="submit" class="btn btn-primary">Kayıt Ol</button></div>
                </form>
            </div>
            <div class="card-footer text-center">Zaten bir hesabınız var mı? <a href="<?php echo BASE_URL; ?>login.php">Giriş Yapın</a></div>
        </div>
    </div>
</div>
<?php
require_once __DIR__ . '/includes/footer.php';
?>