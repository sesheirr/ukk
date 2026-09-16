<?php
/**
 * slip_pdf.php
 * ---------------------------------------------------------
 * Menghasilkan file PDF ASLI (bukan halaman HTML) dari satu
 * slip gaji, supaya link yang dikirim lewat WhatsApp/Email
 * benar-benar mengarah ke file .pdf, bukan ke halaman web.
 * ---------------------------------------------------------
 */
require_once __DIR__ . '/config/auth_guard.php';
require_once __DIR__ . '/config/data.php';
require_once __DIR__ . '/libs/simple_pdf.php';

$user = $_SESSION['user'];
$id   = (int) ($_GET['id'] ?? 0);
$slip = $id > 0 ? getRiwayatById($id, (int) $user['id']) : null;

if (!$slip) {
    header('Location: dashboard.php');
    exit;
}

$pdfBytes = buildSlipPdf($slip);
$namaFile = 'slip-gaji-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $slip['nama']) . '.pdf';

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $namaFile . '"');
header('Content-Length: ' . strlen($pdfBytes));
echo $pdfBytes;
exit;
