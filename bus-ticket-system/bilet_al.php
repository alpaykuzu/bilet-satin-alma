<?php
require_once __DIR__ . '/includes/config.php';

require_role('user');

if (!$is_logged_in) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}
if (!isset($_GET['trip_id']) || empty($_GET['trip_id'])) {
    header('Location: ' . BASE_URL . 'index.php');
    exit();
}

$trip_id = $_GET['trip_id'];
$trip = null;
$booked_seats = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_seats = $_POST['seats'] ?? [];
    $coupon_code = strtoupper(trim($_POST['applied_coupon_code'] ?? ''));
    $coupon = null;

    $stmt = $pdo->prepare("SELECT * FROM trips WHERE id = ?");
    $stmt->execute([$trip_id]);
    $trip = $stmt->fetch();

    if (empty($selected_seats)) {
        $errors[] = "Lütfen en az bir koltuk seçin.";
    } else {
        $original_price = count($selected_seats) * $trip['price'];
        $final_price = $original_price;

        if (!empty($coupon_code)) {
            $stmt_coupon = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND expire_date >= date('now')");
            $stmt_coupon->execute([$coupon_code]);
            $coupon = $stmt_coupon->fetch();

            if (!$coupon) {
                $errors[] = "Geçersiz veya süresi dolmuş kupon kodu.";
            } else {
                $stmt_usage = $pdo->prepare("SELECT COUNT(*) FROM user_coupons WHERE coupon_id = ?");
                $stmt_usage->execute([$coupon['id']]);
                if ($stmt_usage->fetchColumn() >= $coupon['usage_limit']) {
                    $errors[] = "Gönderdiğiniz kupon kullanım limitine ulaştı.";
                } else {
                    $final_price = $original_price - ($original_price * ($coupon['discount'] / 100));
                }
            }
        }

        if (empty($errors)) {
            $stmt_balance = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
            $stmt_balance->execute([$_SESSION['user_id']]);
            $current_balance = $stmt_balance->fetchColumn();

            if ($current_balance < $final_price) {
                $errors[] = "Yetersiz bakiye! Gerekli tutar: " . number_format($final_price, 2) . " ₺";
            } else {
                $pdo->beginTransaction();
                try {
                    $now = date('Y-m-d H:i:s');
                    $ticket_id = generate_uuid();
                    $pnr_code = generate_pnr($pdo);

                    $stmt_ticket = $pdo->prepare("INSERT INTO tickets (id, pnr_code, trip_id, user_id, coupon_id, status, total_price, created_at) VALUES (?, ?, ?, ?, ?, 'active', ?, ?)");
                    $stmt_ticket->execute([$ticket_id, $pnr_code, $trip_id, $_SESSION['user_id'], $coupon['id'] ?? null, $final_price, $now]);
                    
                    if ($coupon) {
                        $uc_id = generate_uuid();
                        $stmt_uc = $pdo->prepare("INSERT INTO user_coupons (id, coupon_id, user_id, created_at) VALUES (?, ?, ?, ?)");
                        $stmt_uc->execute([$uc_id, $coupon['id'], $_SESSION['user_id'], $now]);
                    }
                    
                    $seat_stmt = $pdo->prepare("INSERT INTO booked_seats (id, ticket_id, seat_number, created_at) VALUES (?, ?, ?, ?)");
                    foreach ($selected_seats as $seat) {
                        $seat_id = generate_uuid();
                        $seat_stmt->execute([$seat_id, $ticket_id, $seat, $now]);
                    }

                    $new_balance = $current_balance - $final_price;
                    $update_stmt = $pdo->prepare("UPDATE users SET balance = ? WHERE id = ?");
                    $update_stmt->execute([$new_balance, $_SESSION['user_id']]);

                    $pdo->commit();
                    
                    $_SESSION['balance'] = $new_balance;
                    $_SESSION['success_message'] = "Biletiniz başarıyla oluşturuldu! PNR Kodunuz: " . $pnr_code;
                    header('Location: ' . BASE_URL . 'biletlerim.php');
                    exit();
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $errors[] = "İşlem sırasında beklenmedik bir hata oluştu: " . $e->getMessage();
                }
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $stmt_trip = $pdo->prepare("SELECT trips.*, bus_companies.name AS firma_adi FROM trips LEFT JOIN bus_companies ON trips.company_id = bus_companies.id WHERE trips.id = ?");
        $stmt_trip->execute([$trip_id]);
        $trip = $stmt_trip->fetch();
    }

    if (!$trip) { throw new Exception("Sefer bulunamadı."); }

    $stmt_seats = $pdo->prepare("SELECT bs.seat_number FROM booked_seats bs JOIN tickets t ON bs.ticket_id = t.id WHERE t.trip_id = ? AND t.status = 'active'");
    $stmt_seats->execute([$trip_id]);
    $booked_seats = $stmt_seats->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Hata: ' . $e->getMessage() . '</div>';
    require_once 'includes/footer.php';
    exit();
}
?>

<div class="card">
    <div class="card-header bg-dark text-white"><h3>Bilet Satın Alma</h3></div>
    <div class="card-body">
        <div class="row">
            <div class="col-lg-4 border-end">
                <h4>Sefer Bilgileri</h4>
                <p><strong>Firma:</strong> <?php echo htmlspecialchars($trip['firma_adi']); ?></p>
                <p><strong>Güzergah:</strong> <?php echo htmlspecialchars($trip['departure_city']); ?> → <?php echo htmlspecialchars($trip['destination_city']); ?></p>
                <p><strong>Kalkış:</strong> <?php echo date('d.m.Y H:i', strtotime($trip['departure_time'])); ?></p>
                <p><strong>Bilet Fiyatı:</strong> <span id="bilet-fiyati"><?php echo number_format($trip['price'], 2); ?></span> ₺</p>
                <hr>
                <h4>Hesap Bilgileri</h4>
                <p><strong>Mevcut Bakiyeniz:</strong> <?php echo number_format($_SESSION['balance'], 2); ?> ₺</p>
            </div>
            <div class="col-lg-8">
                <h4>Koltuk Seçimi</h4>
                <p class="text-muted">Lütfen yolculuk yapmak istediğiniz koltukları seçiniz.</p>
                <?php if (!empty($errors)) { echo '<div class="alert alert-danger">'; foreach ($errors as $error) echo "<p class='mb-0'>$error</p>"; echo '</div>'; } ?>
                <form action="<?php echo BASE_URL; ?>bilet_al.php?trip_id=<?php echo $trip_id; ?>" method="POST" id="bilet-formu">
                    <div class="bg-light p-3 rounded text-center mx-auto" style="max-width: 350px;">
                        <div class="d-flex justify-content-end mb-3"><div class="badge bg-secondary">Şoför</div></div>
                        <?php
                        $koltuk_no = 1;
                        while ($koltuk_no <= $trip['capacity']) {
                            echo '<div class="d-flex justify-content-center align-items-center mb-2">';
                            for ($i = 0; $i < 2; $i++) {
                                if ($koltuk_no <= $trip['capacity']) {
                                    $is_booked = in_array($koltuk_no, $booked_seats);
                                    echo '<div class="form-check form-check-inline mx-1"><input class="form-check-input koltuk-sec" type="checkbox" id="koltuk'.$koltuk_no.'" name="seats[]" value="'.$koltuk_no.'" '.($is_booked ? "disabled" : "").'><label class="btn '.($is_booked ? "btn-danger" : "btn-outline-success").' btn-sm" for="koltuk'.$koltuk_no.'">'.$koltuk_no.'</label></div>';
                                    $koltuk_no++;
                                }
                            }
                            echo '<div class="mx-4"></div>';
                            if ($koltuk_no <= $trip['capacity']) {
                                $is_booked = in_array($koltuk_no, $booked_seats);
                                echo '<div class="form-check form-check-inline mx-1"><input class="form-check-input koltuk-sec" type="checkbox" id="koltuk'.$koltuk_no.'" name="seats[]" value="'.$koltuk_no.'" '.($is_booked ? "disabled" : "").'><label class="btn '.($is_booked ? "btn-danger" : "btn-outline-success").' btn-sm" for="koltuk'.$koltuk_no.'">'.$koltuk_no.'</label></div>';
                                $koltuk_no++;
                            }
                            echo '</div>';
                        }
                        ?>
                    </div>
                    <hr>
                    <div class="row align-items-end">
                        <div class="col-md-6">
                            <label for="coupon_code" class="form-label">İndirim Kuponu</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="coupon_code" placeholder="Kupon Kodunuz Varsa Girin">
                                <button class="btn btn-outline-secondary" type="button" id="apply-coupon-btn">Uygula</button>
                            </div>
                            <div id="coupon-message" class="mt-2"></div>
                            <input type="hidden" name="applied_coupon_code" id="applied_coupon_code">
                        </div>
                        <div class="col-md-6 text-md-end">
                            <h5>Seçilen Koltuklar: <span id="secilen-koltuklar" class="fw-normal">Yok</span></h5>
                            <h3 class="mb-0">Toplam Tutar: <span id="toplam-tutar" class="text-success">0.00 ₺</span></h3>
                            <small id="indirim-bilgisi" class="text-danger"></small>
                        </div>
                    </div>
                    <hr>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">Satın Al</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    const BASE_URL = '<?php echo BASE_URL; ?>';
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const koltukSecimAlanlari = document.querySelectorAll('.koltuk-sec');
    const biletFiyati = parseFloat(document.getElementById('bilet-fiyati').textContent.replace(',', '.'));
    const secilenKoltuklarSpan = document.getElementById('secilen-koltuklar');
    const toplamTutarSpan = document.getElementById('toplam-tutar');
    const applyCouponBtn = document.getElementById('apply-coupon-btn');
    const couponCodeInput = document.getElementById('coupon_code');
    const couponMessageDiv = document.getElementById('coupon-message');
    const appliedCouponInput = document.getElementById('applied_coupon_code');
    const indirimBilgisiSpan = document.getElementById('indirim-bilgisi');
    let originalTotalPrice = 0;
    let currentDiscount = 0;
    function updatePrice() {
        const seciliKoltuklar = Array.from(koltukSecimAlanlari).filter(i => i.checked).map(i => i.value);
        secilenKoltuklarSpan.textContent = seciliKoltuklar.length > 0 ? seciliKoltuklar.join(', ') : 'Yok';
        originalTotalPrice = seciliKoltuklar.length * biletFiyati;
        let finalPrice = originalTotalPrice - (originalTotalPrice * currentDiscount);
        toplamTutarSpan.textContent = finalPrice.toFixed(2) + ' ₺';
        if (currentDiscount > 0) {
            indirimBilgisiSpan.innerHTML = `(<s>${originalTotalPrice.toFixed(2)} ₺</s>) - İndirimli!`;
        } else {
            indirimBilgisiSpan.innerHTML = '';
        }
    }
    function resetCoupon() {
        currentDiscount = 0;
        appliedCouponInput.value = '';
        couponMessageDiv.innerHTML = '';
        indirimBilgisiSpan.innerHTML = '';
        updatePrice();
    }
    koltukSecimAlanlari.forEach(checkbox => {
        checkbox.addEventListener('change', () => { resetCoupon(); });
    });
    applyCouponBtn.addEventListener('click', function() {
        const code = couponCodeInput.value.trim();
        if (code === '' || originalTotalPrice === 0) {
            couponMessageDiv.innerHTML = '<div class="alert alert-warning p-2 small">Lütfen önce koltuk seçin ve bir kod girin.</div>';
            return;
        }
        fetch(BASE_URL + 'kupon_kontrol.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `code=${code}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                couponMessageDiv.innerHTML = `<div class="alert alert-success p-2 small">${data.message}</div>`;
                currentDiscount = parseFloat(data.discount) / 100;
                appliedCouponInput.value = code;
                updatePrice();
            } else {
                couponMessageDiv.innerHTML = `<div class="alert alert-danger p-2 small">${data.message}</div>`;
                resetCoupon();
            }
        });
    });
    updatePrice();
});
</script>
<?php
require_once __DIR__ . '/includes/footer.php';
?>