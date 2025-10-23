<?php
require_once __DIR__ . '/../includes/config.php';

require_role('admin');
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $action = $_POST['action'];
    $company_id = $_POST['company_id'] ?? null;

    if (empty($name)) {
        $errors[] = "Firma adı boş bırakılamaz.";
    } else {
        $stmt_check = $action === 'add'
            ? $pdo->prepare("SELECT id FROM bus_companies WHERE name = ?")
            : $pdo->prepare("SELECT id FROM bus_companies WHERE name = ? AND id != ?");
        $params = $action === 'add' ? [$name] : [$name, $company_id];
        $stmt_check->execute($params);
        if ($stmt_check->fetch()) {
            $errors[] = "Bu firma adı zaten mevcut.";
        }
    }

    if (empty($errors)) {
        try {
            if ($action === 'add') {
                $new_company_id = generate_uuid();
                $stmt = $pdo->prepare("INSERT INTO bus_companies (id, name, created_at) VALUES (?, ?, ?)");
                $stmt->execute([$new_company_id, $name, date('Y-m-d H:i:s')]);
                $_SESSION['success_message'] = 'Yeni firma başarıyla eklendi.';
            } elseif ($action === 'edit' && $company_id) {
                $stmt = $pdo->prepare("UPDATE bus_companies SET name = ? WHERE id = ?");
                $stmt->execute([$name, $company_id]);
                $_SESSION['success_message'] = 'Firma başarıyla güncellendi.';
            }
            header('Location: ' . BASE_URL . 'admin/firma_yonetimi.php');
            exit();
        } catch (PDOException $e) {
            $errors[] = "Veritabanı hatası: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';

$edit_mode = false;
$edit_company = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $edit_mode = true;
    $stmt = $pdo->prepare("SELECT * FROM bus_companies WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $edit_company = $stmt->fetch();
    if (!$edit_company) { $edit_mode = false; }
}

$companies = $pdo->query("SELECT * FROM bus_companies ORDER BY name")->fetchAll();
?>

<div class="container">
    <div class="row">
        <div class="col-md-3">
             <div class="list-group">
                <a href="<?php echo BASE_URL; ?>admin/index.php" class="list-group-item list-group-item-action">Panel Ana Sayfa</a>
                <a href="<?php echo BASE_URL; ?>admin/firma_yonetimi.php" class="list-group-item list-group-item-action active">Firma Yönetimi</a>
                <a href="<?php echo BASE_URL; ?>admin/firma_admin_yonetimi.php" class="list-group-item list-group-item-action">Firma Admin Yönetimi</a>
                <a href="<?php echo BASE_URL; ?>admin/kupon_yonetimi.php" class="list-group-item list-group-item-action">Genel Kupon Yönetimi</a>
            </div>
        </div>

        <div class="col-md-9">
            <div class="card mb-4">
                <div class="card-header">
                    <h3><?php echo $edit_mode ? 'Firmayı Düzenle' : 'Yeni Firma Ekle'; ?></h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger"><?php foreach ($errors as $error) echo "<p class='mb-0'>$error</p>"; ?></div>
                    <?php endif; ?>
                    <form action="<?php echo BASE_URL; ?>admin/firma_yonetimi.php" method="POST">
                        <input type="hidden" name="action" value="<?php echo $edit_mode ? 'edit' : 'add'; ?>">
                        <?php if ($edit_mode): ?>
                            <input type="hidden" name="company_id" value="<?php echo htmlspecialchars($edit_company['id']); ?>">
                        <?php endif; ?>
                        <div class="input-group">
                            <input type="text" class="form-control" name="name" placeholder="Firma Adı" value="<?php echo htmlspecialchars($edit_company['name'] ?? ''); ?>" required>
                            <button type="submit" class="btn btn-primary"><?php echo $edit_mode ? 'Güncelle' : 'Ekle'; ?></button>
                            <?php if ($edit_mode): ?>
                                <a href="<?php echo BASE_URL; ?>admin/firma_yonetimi.php" class="btn btn-secondary">İptal</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <h3>Mevcut Firmalar</h3>
            <hr>
            <?php 
                if (isset($_SESSION['success_message'])) { echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>'; unset($_SESSION['success_message']); }
                if (isset($_SESSION['error_message'])) { echo '<div class="alert alert-danger">' . $_SESSION['error_message'] . '</div>'; unset($_SESSION['error_message']); }
            ?>
            <table class="table table-striped table-bordered">
                <thead class="table-dark">
                    <tr><th>Firma Adı</th><th>İşlemler</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($companies as $company): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($company['name']); ?></td>
                        <td>
                            <a href="<?php echo BASE_URL; ?>admin/firma_yonetimi.php?action=edit&id=<?php echo $company['id']; ?>" class="btn btn-sm btn-warning">Düzenle</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>