<?php
require_once __DIR__ . '/includes/config.php';

require_once __DIR__ . '/includes/lib/tcpdf/tcpdf.php';
require_once __DIR__ . '/includes/lib/phpqrcode/qrlib.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    die("Yetkisiz erişim.");
}

$user_id = $_SESSION['user_id'];
$ticket_id = $_GET['id'];

try {
    $sql = "
        SELECT 
            t.id AS ticket_id, t.pnr_code, t.total_price, t.created_at AS purchase_date,
            u.full_name AS passenger_name,
            tr.departure_city, tr.destination_city, tr.departure_time, tr.arrival_time,
            bc.name AS company_name,
            GROUP_CONCAT(bs.seat_number, ', ') AS seat_numbers
        FROM tickets t
        JOIN users u ON t.user_id = u.id
        JOIN trips tr ON t.trip_id = tr.id
        JOIN bus_companies bc ON tr.company_id = bc.id
        JOIN booked_seats bs ON bs.ticket_id = t.id
        WHERE t.id = ? AND t.user_id = ?
        GROUP BY t.id
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$ticket_id, $user_id]);
    $ticket_data = $stmt->fetch();
    if (!$ticket_data) { die("Bilet bulunamadı."); }
} catch (PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}

$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Bus Ticket System');
$pdf->SetAuthor('Bus Company');
$pdf->SetTitle('Elektronik Bilet');
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(false);
$pdf->AddPage();

$pdf->SetFont('dejavusans', 'B', 18);

// Başlık
$pdf->SetY(15);
$pdf->Cell(0, 15, 'ELEKTRONİK BİLET', 0, 1, 'C');

// Firma ve yolcu bilgisi kutusu
$pdf->SetFont('dejavusans', '', 12);
$pdf->SetY(40);
$pdf->SetX(15);
$pdf->MultiCell(90, 6, "Firma: " . $ticket_data['company_name'] . "\nYolcu: " . $ticket_data['passenger_name'] . "\nPNR: " . $ticket_data['pnr_code'], 0, 'L', false);

// Yolculuk detayları kutusu
$pdf->SetFont('dejavusans', 'B', 14);
$pdf->SetY(90);
$pdf->Cell(0, 8, 'Yolculuk Detayları', 0, 1, 'C');

$pdf->SetFont('dejavusans', '', 12);
$pdf->SetY(100);
$pdf->SetX(15);
$pdf->Cell(95, 6, 'Kalkış: ' . $ticket_data['departure_city'], 0, 0, 'L');
$pdf->Cell(95, 6, 'Varış: ' . $ticket_data['destination_city'], 0, 1, 'L');

$pdf->SetX(15);
$pdf->Cell(95, 6, 'Kalkış Tarih & Saat: ' . date('d.m.Y H:i', strtotime($ticket_data['departure_time'])), 0, 0, 'L');
$pdf->Cell(95, 6, 'Varış Tarih & Saat: ' . date('d.m.Y H:i', strtotime($ticket_data['arrival_time'])), 0, 1, 'L');

$pdf->SetX(15);
$pdf->Cell(95, 6, 'Koltuk Numaraları: ' . $ticket_data['seat_numbers'], 0, 0, 'L');
$pdf->Cell(95, 6, 'Toplam Tutar: ' . number_format($ticket_data['total_price'], 2) . ' ₺', 0, 1, 'L');

// QR Kod
$qr_file = 'temp_qr.png';
$qr_content = "PNR: {$ticket_data['pnr_code']}\nYolcu: {$ticket_data['passenger_name']}\nKalkış: {$ticket_data['departure_city']}";
QRcode::png($qr_content, $qr_file, QR_ECLEVEL_L, 3);
$pdf->Image($qr_file, 160, 40, 35, 35);
unlink($qr_file);

// Alt yazı
$pdf->SetY(260);
$pdf->SetFont('dejavusans', 'I', 10);
$pdf->Cell(0, 10, 'İyi yolculuklar dileriz!', 0, 1, 'C');

// PDF'i indir
$pdf->Output('Bilet_' . $ticket_data['pnr_code'] . '.pdf', 'D');
exit();
?>