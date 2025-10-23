<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function generate_uuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

$db_file = 'veritabani.sqlite';

try {
    if (file_exists($db_file)) {
        unlink($db_file);
    }

    $pdo = new PDO('sqlite:' . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA foreign_keys = ON;');

    echo "Veritabanı dosyası '{$db_file}' başarıyla oluşturuldu.<br>";

    $commands = [
        'CREATE TABLE bus_companies (
            id TEXT PRIMARY KEY,
            name TEXT NOT NULL UNIQUE,
            logo_path TEXT,
            created_at TEXT NOT NULL
        )',
        'CREATE TABLE users (
            id TEXT PRIMARY KEY,
            full_name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            role TEXT NOT NULL CHECK(role IN ("user", "firma_admin", "admin")),
            password TEXT NOT NULL,
            company_id TEXT,
            balance REAL NOT NULL DEFAULT 800.0,
            created_at TEXT NOT NULL,
            FOREIGN KEY (company_id) REFERENCES bus_companies(id)
        )',
        'CREATE TABLE trips (
            id TEXT PRIMARY KEY,
            company_id TEXT NOT NULL,
            departure_city TEXT NOT NULL,
            destination_city TEXT NOT NULL,
            departure_time TEXT NOT NULL,
            arrival_time TEXT NOT NULL,
            price REAL NOT NULL,
            capacity INTEGER NOT NULL,
            created_at TEXT NOT NULL,
            FOREIGN KEY (company_id) REFERENCES bus_companies(id)
        )',
        'CREATE TABLE tickets (
            id TEXT PRIMARY KEY,
            pnr_code TEXT NOT NULL UNIQUE, 
            trip_id TEXT NOT NULL,
            user_id TEXT NOT NULL,
            coupon_id TEXT,
            status TEXT NOT NULL DEFAULT "active" CHECK(status IN ("active", "cancelled", "expired")),
            total_price REAL NOT NULL,
            created_at TEXT NOT NULL,
            FOREIGN KEY (trip_id) REFERENCES trips(id),
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (coupon_id) REFERENCES coupons(id)
        )',
        'CREATE TABLE booked_seats (
            id TEXT PRIMARY KEY,
            ticket_id TEXT NOT NULL,
            seat_number INTEGER NOT NULL,
            created_at TEXT NOT NULL,
            FOREIGN KEY (ticket_id) REFERENCES tickets(id)
        )',
        'CREATE TABLE coupons (
            id TEXT PRIMARY KEY,
            code TEXT NOT NULL UNIQUE,
            discount TEXT NOT NULL,
            company_id TEXT,
            usage_limit INTEGER NOT NULL,
            expire_date TEXT NOT NULL,
            status TEXT NOT NULL DEFAULT "active" CHECK(status IN ("active", "inactive")), 
            created_at TEXT NOT NULL,
            FOREIGN KEY (company_id) REFERENCES bus_companies(id)
        )',
        'CREATE TABLE user_coupons (
            id TEXT PRIMARY KEY,
            coupon_id TEXT NOT NULL,
            user_id TEXT NOT NULL,
            created_at TEXT NOT NULL,
            FOREIGN KEY (coupon_id) REFERENCES coupons(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )'
    ];

    foreach ($commands as $command) {
        $pdo->exec($command);
    }

    echo "Tüm tablolar senin şemana göre başarıyla oluşturuldu.<br>";

    // Örnek Veriler Ekleme
    echo "Örnek veriler ekleniyor...<br>";
    $now = date('Y-m-d H:i:s');

    // 1. Örnek firma
    $company_id = generate_uuid();
    $stmt = $pdo->prepare("INSERT INTO bus_companies (id, name, created_at) VALUES (?, ?, ?)");
    $stmt->execute([$company_id, 'Fethiye Seyahat', $now]);
    echo "Örnek firma eklendi: Fethiye Seyahat<br>";

    // 2. Örnek kullanıcılar
    $admin_id = generate_uuid();
    $admin_sifre = password_hash('AdminSifre123!', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (id, full_name, email, role, password, created_at) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$admin_id, 'Admin User', 'admin@bilet.com', 'admin', $admin_sifre, $now]);
    echo "Süper Admin kullanıcısı eklendi. (E-posta: admin@bilet.com, Şifre: AdminSifre123!)<br>";

    $firma_admin_id = generate_uuid();
    $firma_admin_sifre = password_hash('FirmaSifre123!', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (id, full_name, email, role, password, company_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$firma_admin_id, 'Firma Yetkilisi', 'firma@bilet.com', 'firma_admin', $firma_admin_sifre, $company_id, $now]);
    echo "Firma Admin kullanıcısı eklendi ve 'Fethiye Seyahat' firmasına atandı.<br>";
    
    $user_id = generate_uuid();
    $user_sifre = password_hash('Kullanici123!', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (id, full_name, email, role, password, created_at) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, 'Ali Veli', 'user@bilet.com', 'user', $user_sifre, $now]);
    echo "Normal kullanıcı eklendi. (E-posta: user@bilet.com, Şifre: Kullanici123!)<br>";

    // 3. Örnek seferler
    echo "<strong>Örnek seferler ekleniyor...</strong><br>";
    $trip_stmt = $pdo->prepare(
        "INSERT INTO trips (id, company_id, departure_city, destination_city, departure_time, arrival_time, price, capacity, created_at) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    // Sefer 1: Fethiye -> İstanbul (Yarın)
    $trip_id_1 = generate_uuid();
    $departure_1 = date('Y-m-d H:i:s', strtotime('+1 day 20:00'));
    $arrival_1 = date('Y-m-d H:i:s', strtotime('+2 days 08:30'));
    $trip_stmt->execute([$trip_id_1, $company_id, 'Fethiye', 'İstanbul', $departure_1, $arrival_1, 850.00, 42, $now]);
    echo "Örnek sefer eklendi: Fethiye -> İstanbul<br>";

    // Sefer 2: İstanbul -> Ankara (2 Gün Sonra)
    $trip_id_2 = generate_uuid();
    $departure_2 = date('Y-m-d H:i:s', strtotime('+2 days 23:30'));
    $arrival_2 = date('Y-m-d H:i:s', strtotime('+3 days 06:00'));
    $trip_stmt->execute([$trip_id_2, $company_id, 'İstanbul', 'Ankara', $departure_2, $arrival_2, 650.00, 40, $now]);
    echo "Örnek sefer eklendi: İstanbul -> Ankara<br>";
    
    // Sefer 3: Fethiye -> İzmir (Yarın)
    $trip_id_3 = generate_uuid();
    $departure_3 = date('Y-m-d H:i:s', strtotime('+1 day 10:00'));
    $arrival_3 = date('Y-m-d H:i:s', strtotime('+1 day 14:30'));
    $trip_stmt->execute([$trip_id_3, $company_id, 'Fethiye', 'İzmir', $departure_3, $arrival_3, 400.00, 45, $now]);
    echo "Örnek sefer eklendi: Fethiye -> İzmir<br>";

    echo "Kurulum tamamlandı! Güvenlik için bu dosyayı şimdi silebilirsin.";

} catch (PDOException $e) {
    echo "Hata: " . $e->getMessage();
}
?>