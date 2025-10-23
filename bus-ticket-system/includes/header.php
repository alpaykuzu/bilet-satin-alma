<?php
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Otobüs Bileti Satış Platformu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>index.php">🚌 Bilet Sistemi</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>index.php">Ana Sayfa</a></li>
                    <?php if ($is_logged_in): ?>

                        <?php ?>
                        <?php if ($user_role === 'user'): ?>
                            <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>biletlerim.php">Biletlerim</a></li>
                        <?php endif; ?>
                            
                        <?php if ($user_role === 'admin'): ?>
                            <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>admin/index.php">Admin Paneli</a></li>
                        <?php elseif ($user_role === 'firma_admin'): ?>
                             <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>firma_admin/index.php">Firma Paneli</a></li>
                        <?php endif; ?>
                        
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                Hoş Geldin, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Kullanıcı'); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-dark">
                                <?php ?>
                                <?php ?>
                                <?php if ($user_role === 'user'): ?>
                                    <li><a class="dropdown-item" href="#">Bakiye: <?php echo number_format($_SESSION['balance'] ?? 0, 2); ?> ₺</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                <?php endif; ?>
                                <?php ?>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>logout.php">Çıkış Yap</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>login.php">Giriş Yap</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>register.php">Kayıt Ol</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <main class="container my-4">