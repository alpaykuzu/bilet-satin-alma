<?php
require_once __DIR__ . '/../includes/config.php';

require_role('firma_admin');

$company_id = $_SESSION['company_id'];
$errors = [];
$now = new DateTime();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $departure_city = trim($_POST['departure_city']);
    $destination_city = trim($_POST['destination_city']);
    $departure_time_str = trim($_POST['departure_time']);
    $arrival_time_str = trim($_POST['arrival_time']);
    $price = trim($_POST['price']);
    $capacity = trim($_POST['capacity']);
    $action = $_POST['action'];
    $trip_id = $_POST['trip_id'] ?? null;
    $repeat_daily = isset($_POST['repeat_daily']);
    $repeat_weekly = isset($_POST['repeat_weekly']);

    if (empty($departure_city) || empty($destination_city) || empty($departure_time_str) || empty($arrival_time_str) || empty($price) || empty($capacity)) { $errors[] = "Tüm alanlar zorunludur."; }
    if (!is_numeric($price) || $price <= 0) { $errors[] = "Fiyat geçerli bir pozitif sayı olmalıdır."; }
    if (!ctype_digit($capacity) || $capacity <= 0) { $errors[] = "Kapasite pozitif bir tam sayı olmalıdır."; }

    if (empty($errors)) {
        try {
            $departure_dt = new DateTime($departure_time_str);
            $arrival_dt = new DateTime($arrival_time_str);

            if ($departure_dt <= $now) { $errors[] = "Kalkış zamanı geçmiş bir tarih veya saat olamaz."; }
            if ($arrival_dt <= $departure_dt) { $errors[] = "Varış zamanı, kalkış zamanından sonra olmalıdır."; }
            
             $interval = $departure_dt->diff($arrival_dt);
        } catch (Exception $e) { $errors[] = "Geçersiz tarih/saat formatı girdiniz."; }
    }

    if ($action === 'edit' && ($repeat_daily || $repeat_weekly)) { $errors[] = "Sefer güncellerken tekrarlama yapılamaz."; }
    if ($repeat_daily && $repeat_weekly) { $errors[] = "Sadece bir tekrarlama seçeneği seçebilirsiniz."; }

    if (empty($errors)) {
        try {
            $stmt_insert = $pdo->prepare("INSERT INTO trips (id, company_id, departure_city, destination_city, departure_time, arrival_time, price, capacity, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

            if ($action === 'add') {
                $trips_added_count = 0;
                if ($repeat_daily) {
                    $current_departure_dt = clone $departure_dt;
                    for ($i = 0; $i < 30; $i++) {
                        $new_trip_id = generate_uuid(); $current_arrival_dt = clone $current_departure_dt; $current_arrival_dt->add($interval);
                        $stmt_insert->execute([$new_trip_id, $company_id, $departure_city, $destination_city, $current_departure_dt->format('Y-m-d H:i:s'), $current_arrival_dt->format('Y-m-d H:i:s'), $price, $capacity, date('Y-m-d H:i:s')]);
                        $trips_added_count++; $current_departure_dt->add(new DateInterval('P1D'));
                    }
                    $_SESSION['success_message'] = $trips_added_count . ' sefer (günlük tekrarla) eklendi.';
                } elseif ($repeat_weekly) {
                    $current_departure_dt = clone $departure_dt;
                    for ($i = 0; $i < 4; $i++) {
                         $new_trip_id = generate_uuid(); $current_arrival_dt = clone $current_departure_dt; $current_arrival_dt->add($interval);
                         $stmt_insert->execute([$new_trip_id, $company_id, $departure_city, $destination_city, $current_departure_dt->format('Y-m-d H:i:s'), $current_arrival_dt->format('Y-m-d H:i:s'), $price, $capacity, date('Y-m-d H:i:s')]);
                         $trips_added_count++; $current_departure_dt->add(new DateInterval('P1W'));
                    }
                     $_SESSION['success_message'] = $trips_added_count . ' sefer (haftalık tekrarla) eklendi.';
                } else {
                    $new_trip_id = generate_uuid();
                    $stmt_insert->execute([$new_trip_id, $company_id, $departure_city, $destination_city, $departure_dt->format('Y-m-d H:i:s'), $arrival_dt->format('Y-m-d H:i:s'), $price, $capacity, date('Y-m-d H:i:s')]);
                    $_SESSION['success_message'] = 'Yeni sefer başarıyla eklendi.';
                }
            } elseif ($action === 'edit' && $trip_id) {
                $stmt_check_past = $pdo->prepare("SELECT departure_time FROM trips WHERE id = ? AND company_id = ?");
                $stmt_check_past->execute([$trip_id, $company_id]);
                $existing_departure_time = $stmt_check_past->fetchColumn();
                if ($existing_departure_time && new DateTime($existing_departure_time) <= $now) { throw new Exception("Geçmiş bir sefer düzenlenemez."); }
                $stmt_update = $pdo->prepare("UPDATE trips SET departure_city = ?, destination_city = ?, departure_time = ?, arrival_time = ?, price = ?, capacity = ? WHERE id = ? AND company_id = ?");
                $stmt_update->execute([$departure_city, $destination_city, $departure_dt->format('Y-m-d H:i:s'), $arrival_dt->format('Y-m-d H:i:s'), $price, $capacity, $trip_id, $company_id]);
                $_SESSION['success_message'] = 'Sefer başarıyla güncellendi.';
            }
            header('Location: ' . BASE_URL . 'firma_admin/sefer_yonetimi.php');
            exit();
        } catch (PDOException $e) { $errors[] = 'Veritabanı hatası: ' . $e->getMessage();
        } catch (Exception $e) { $errors[] = 'Bir hata oluştu: ' . $e->getMessage(); }
    }
}

$items_per_page = 15;
$current_page = isset($_GET['page']) && ctype_digit($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;
$show_past_trips = isset($_GET['show_past']) && $_GET['show_past'] == 1;
$filter_date = $_GET['filter_date'] ?? '';
$filter_departure = $_GET['filter_departure'] ?? '';
$filter_destination = $_GET['filter_destination'] ?? '';
$sort_column = $_GET['sort'] ?? 'departure_time';
$sort_direction = isset($_GET['dir']) && strtolower($_GET['dir']) === 'desc' ? 'DESC' : 'ASC';
$allowed_sort_columns = ['departure_city', 'destination_city', 'departure_time', 'price', 'capacity'];
if (!in_array($sort_column, $allowed_sort_columns)) { $sort_column = 'departure_time'; }

$sql_base = "FROM trips WHERE company_id = :company_id";
$params = [':company_id' => $company_id];
if (!$show_past_trips) { $sql_base .= " AND departure_time >= :now"; $params[':now'] = $now->format('Y-m-d H:i:s'); }
if (!empty($filter_date)) { $sql_base .= " AND date(departure_time) = :filter_date"; $params[':filter_date'] = $filter_date; }
if (!empty($filter_departure)) { $sql_base .= " AND departure_city LIKE :filter_departure"; $params[':filter_departure'] = '%' . $filter_departure . '%'; }
if (!empty($filter_destination)) { $sql_base .= " AND destination_city LIKE :filter_destination"; $params[':filter_destination'] = '%' . $filter_destination . '%'; }

$sql_count = "SELECT COUNT(*) " . $sql_base;
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute($params);
$total_items = $stmt_count->fetchColumn();
$total_pages = ceil($total_items / $items_per_page);

$sql_select = "SELECT * " . $sql_base . " ORDER BY " . $sort_column . " " . $sort_direction . " LIMIT :limit OFFSET :offset";
$stmt_seferler = $pdo->prepare($sql_select);
$stmt_seferler->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
$stmt_seferler->bindValue(':offset', $offset, PDO::PARAM_INT);
foreach ($params as $key => $value) { $stmt_seferler->bindValue($key, $value); }
$stmt_seferler->execute();
$seferler = $stmt_seferler->fetchAll();

$departure_cities = $pdo->query("SELECT DISTINCT departure_city FROM trips WHERE company_id = '$company_id' ORDER BY departure_city")->fetchAll(PDO::FETCH_COLUMN);
$destination_cities = $pdo->query("SELECT DISTINCT destination_city FROM trips WHERE company_id = '$company_id' ORDER BY destination_city")->fetchAll(PDO::FETCH_COLUMN);

function sort_link($column, $text) {
    global $sort_column, $sort_direction, $show_past_trips, $filter_date, $filter_departure, $filter_destination;
    $direction = ($sort_column === $column && $sort_direction === 'ASC') ? 'desc' : 'asc';
    $icon = ($sort_column === $column) ? ($sort_direction === 'ASC' ? ' ▲' : ' ▼') : '';
    $query_params = http_build_query(array_filter(['sort' => $column, 'dir' => $direction, 'show_past' => $show_past_trips ? 1 : null, 'filter_date' => $filter_date, 'filter_departure' => $filter_departure, 'filter_destination' => $filter_destination]));
    return '<a href="' . BASE_URL . 'firma_admin/sefer_yonetimi.php?' . $query_params . '">' . htmlspecialchars($text) . $icon . '</a>';
}

$page_error_message = $_SESSION['error_message'] ?? null;
$page_success_message = $_SESSION['success_message'] ?? null;
unset($_SESSION['error_message'], $_SESSION['success_message']);

require_once __DIR__ . '/../includes/header.php';

$edit_mode = false;
$edit_sefer = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    if (empty($errors)) { 
        $edit_mode = true;
        $stmt_get = $pdo->prepare("SELECT * FROM trips WHERE id = ? AND company_id = ?");
        $stmt_get->execute([$_GET['id'], $company_id]);
        $edit_sefer = $stmt_get->fetch();
        if (!$edit_sefer) { $edit_mode = false; $page_error_message = 'Geçersiz düzenleme isteği.'; } 
        elseif (new DateTime($edit_sefer['departure_time']) <= $now) {
             $edit_mode = false; $page_error_message = 'Geçmiş bir sefer düzenlenemez.';
             $edit_sefer = null;
        }
    }
}
?>

<div class="container">
    <div class="row">
        <div class="col-md-3">
            <div class="list-group">
                <a href="<?php echo BASE_URL; ?>firma_admin/index.php" class="list-group-item list-group-item-action">Panel Ana Sayfa</a>
                <a href="<?php echo BASE_URL; ?>firma_admin/sefer_yonetimi.php" class="list-group-item list-group-item-action active">Sefer Yönetimi</a>
                <a href="<?php echo BASE_URL; ?>firma_admin/kupon_yonetimi.php" class="list-group-item list-group-item-action">Kupon Yönetimi</a>
            </div>
        </div>
        <div class="col-md-9">
            <div class="card mb-4">
                <div class="card-header"><h3><?php echo $edit_mode ? 'Seferi Düzenle' : 'Yeni Sefer Ekle'; ?></h3></div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?><div class="alert alert-danger"><?php foreach ($errors as $error) echo "<p class='mb-0'>$error</p>"; ?></div><?php endif; ?>
                    <?php if ($page_error_message && !$edit_mode): ?>
                        <div class="alert alert-danger"><?php echo $page_error_message; ?></div>
                    <?php endif; ?>

                    <?php if (!$edit_mode || $edit_sefer):  ?>
                    <form action="<?php echo BASE_URL; ?>firma_admin/sefer_yonetimi.php<?php echo $edit_mode ? '?action=edit&id='.$edit_sefer['id'] : ''; ?>" method="POST">
                         <input type="hidden" name="action" value="<?php echo $edit_mode ? 'edit' : 'add'; ?>">
                        <?php if ($edit_mode): ?><input type="hidden" name="trip_id" value="<?php echo htmlspecialchars($edit_sefer['id']); ?>"><?php endif; ?>
                        <div class="row">
                            <div class="col-md-6 mb-3"><label for="departure_city" class="form-label">Kalkış Şehri</label><input type="text" class="form-control" name="departure_city" value="<?php echo htmlspecialchars($_POST['departure_city'] ?? $edit_sefer['departure_city'] ?? ''); ?>" required></div>
                            <div class="col-md-6 mb-3"><label for="destination_city" class="form-label">Varış Şehri</label><input type="text" class="form-control" name="destination_city" value="<?php echo htmlspecialchars($_POST['destination_city'] ?? $edit_sefer['destination_city'] ?? ''); ?>" required></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3"><label for="departure_time" class="form-label">Kalkış Zamanı</label><input type="datetime-local" class="form-control" name="departure_time" value="<?php echo htmlspecialchars($_POST['departure_time'] ?? (!empty($edit_sefer['departure_time']) ? date('Y-m-d\TH:i', strtotime($edit_sefer['departure_time'])) : '')); ?>" required></div>
                            <div class="col-md-6 mb-3"><label for="arrival_time" class="form-label">Varış Zamanı</label><input type="datetime-local" class="form-control" name="arrival_time" value="<?php echo htmlspecialchars($_POST['arrival_time'] ?? (!empty($edit_sefer['arrival_time']) ? date('Y-m-d\TH:i', strtotime($edit_sefer['arrival_time'])) : '')); ?>" required></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3"><label for="price" class="form-label">Fiyat (₺)</label><input type="number" step="0.01" class="form-control" name="price" value="<?php echo htmlspecialchars($_POST['price'] ?? $edit_sefer['price'] ?? ''); ?>" required></div>
                            <div class="col-md-6 mb-3"><label for="capacity" class="form-label">Kapasite</label><input type="number" class="form-control" name="capacity" value="<?php echo htmlspecialchars($_POST['capacity'] ?? $edit_sefer['capacity'] ?? ''); ?>" required></div>
                        </div>
                        <?php if (!$edit_mode): ?>
                        <div class="mb-3">
                            <label class="form-label">Tekrarlama Seçenekleri (Opsiyonel)</label>
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="repeat_daily" id="repeat_daily" <?php if (isset($_POST['repeat_daily'])) echo 'checked'; ?>><label class="form-check-label" for="repeat_daily">Sonraki 1 ay **her gün** tekrarla.</label></div>
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="repeat_weekly" id="repeat_weekly" <?php if (isset($_POST['repeat_weekly'])) echo 'checked'; ?>><label class="form-check-label" for="repeat_weekly">Sonraki 1 ay **her hafta aynı gün** tekrarla.</label></div>
                            <small class="form-text text-muted">Sadece birini seçebilirsiniz.</small>
                        </div>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-primary"><?php echo $edit_mode ? 'Güncelle' : 'Ekle'; ?></button>
                        <?php if ($edit_mode): ?><a href="<?php echo BASE_URL; ?>firma_admin/sefer_yonetimi.php" class="btn btn-secondary">İptal</a><?php endif; ?>
                    </form>
                    <?php endif; ?>
                </div>
            </div>

            <h3>Mevcut Seferler</h3><hr>
            <form action="<?php echo BASE_URL; ?>firma_admin/sefer_yonetimi.php" method="GET" class="mb-4 row g-3 align-items-end bg-light p-3 rounded border">
                 <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort_column); ?>">
                 <input type="hidden" name="dir" value="<?php echo htmlspecialchars($sort_direction); ?>">
                 <div class="col-md-3">
                    <label for="filter_date" class="form-label">Tarih</label>
                    <input type="date" class="form-control form-control-sm" id="filter_date" name="filter_date" value="<?php echo htmlspecialchars($filter_date); ?>">
                </div>
                <div class="col-md-3">
                    <label for="filter_departure" class="form-label">Kalkış</label>
                     <select id="filter_departure" name="filter_departure" class="form-select form-select-sm">
                        <option value="">Tümü</option>
                        <?php foreach ($departure_cities as $city): ?><option value="<?php echo htmlspecialchars($city); ?>" <?php if ($filter_departure === $city) echo 'selected'; ?>><?php echo htmlspecialchars($city); ?></option><?php endforeach; ?>
                    </select>
                </div>
                 <div class="col-md-3">
                    <label for="filter_destination" class="form-label">Varış</label>
                    <select id="filter_destination" name="filter_destination" class="form-select form-select-sm">
                        <option value="">Tümü</option>
                        <?php foreach ($destination_cities as $city): ?><option value="<?php echo htmlspecialchars($city); ?>" <?php if ($filter_destination === $city) echo 'selected'; ?>><?php echo htmlspecialchars($city); ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-secondary btn-sm w-100 mb-1">Filtrele</button>
                    <?php
                        $filter_active = !empty($filter_date) || !empty($filter_departure) || !empty($filter_destination) || $show_past_trips || $sort_column !== 'departure_time' || $sort_direction !== 'ASC';
                        if ($filter_active): ?>
                       <a href="<?php echo BASE_URL; ?>firma_admin/sefer_yonetimi.php" class="btn btn-outline-secondary btn-sm w-100">Temizle</a>
                    <?php endif; ?>
                </div>
                 <div class="col-12">
                     <div class="form-check form-switch">
                       <input class="form-check-input" type="checkbox" role="switch" id="show_past" name="show_past" value="1" <?php if($show_past_trips) echo 'checked'; ?> onchange="this.form.submit()">
                       <label class="form-check-label" for="show_past">Geçmiş Seferleri Göster</label>
                     </div>
                 </div>
            </form>

            <?php 
                if ($page_success_message) { echo '<div class="alert alert-success">' . $page_success_message . '</div>'; }
                if ($page_error_message && $_SERVER['REQUEST_METHOD'] !== 'POST') { echo '<div class="alert alert-danger">' . $page_error_message . '</div>'; }
            ?>

            <?php if (empty($seferler)): ?>
                <div class="alert alert-info">Filtre kriterlerinize uygun veya mevcut sefer bulunmamaktadır.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover table-sm"> 
                        <thead class="table-dark">
                            <tr>
                                <th><?php echo sort_link('departure_city', 'Kalkış'); ?></th>
                                <th><?php echo sort_link('destination_city', 'Varış'); ?></th>
                                <th><?php echo sort_link('departure_time', 'Kalkış Zamanı'); ?></th>
                                <th class="text-end"><?php echo sort_link('price', 'Fiyat'); ?></th>
                                <th class="text-center"><?php echo sort_link('capacity', 'Kapasite'); ?></th>
                                <th class="text-center">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($seferler as $sefer): ?>
                                <?php $is_past_trip = (new DateTime($sefer['departure_time']) <= $now); ?>
                                <tr class="<?php if($is_past_trip) echo 'opacity-75'; ?>">
                                    <td><?php echo htmlspecialchars($sefer['departure_city']); ?></td>
                                    <td><?php echo htmlspecialchars($sefer['destination_city']); ?></td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($sefer['departure_time'])); ?></td>
                                    <td class="text-end"><?php echo number_format($sefer['price'], 2); ?> ₺</td>
                                    <td class="text-center"><?php echo htmlspecialchars($sefer['capacity']); ?></td>
                                    <td class="text-center">
                                        <a href="<?php echo BASE_URL; ?>firma_admin/sefer_yonetimi.php?action=edit&id=<?php echo $sefer['id']; ?>" class="btn btn-sm btn-warning <?php if($is_past_trip) echo 'disabled'; ?>">Düzenle</a>
                                        <a href="<?php echo BASE_URL; ?>firma_admin/sefer_sil.php?id=<?php echo $sefer['id']; ?>" class="btn btn-sm btn-danger <?php if($is_past_trip) echo 'disabled'; ?>" onclick="return confirm('Bu seferi silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.');">Sil</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Sayfalama">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php if ($current_page <= 1) echo 'disabled'; ?>">
                                <a class="page-link" href="?page=<?php echo $current_page - 1; ?>&<?php echo http_build_query(array_filter(['sort'=>$sort_column, 'dir'=>$sort_direction, 'show_past'=>$show_past_trips ? 1 : null, 'filter_date'=>$filter_date, 'filter_departure'=>$filter_departure, 'filter_destination'=>$filter_destination])); ?>">Önceki</a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php if ($i === $current_page) echo 'active'; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_filter(['sort'=>$sort_column, 'dir'=>$sort_direction, 'show_past'=>$show_past_trips ? 1 : null, 'filter_date'=>$filter_date, 'filter_departure'=>$filter_departure, 'filter_destination'=>$filter_destination])); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php if ($current_page >= $total_pages) echo 'disabled'; ?>">
                                <a class="page-link" href="?page=<?php echo $current_page + 1; ?>&<?php echo http_build_query(array_filter(['sort'=>$sort_column, 'dir'=>$sort_direction, 'show_past'=>$show_past_trips ? 1 : null, 'filter_date'=>$filter_date, 'filter_departure'=>$filter_departure, 'filter_destination'=>$filter_destination])); ?>">Sonraki</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php
require_once __DIR__ . '/../includes/footer.php';
?>