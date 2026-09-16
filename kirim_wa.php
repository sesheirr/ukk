<?php

require_once __DIR__ . '/config/auth_guard.php';
require_once __DIR__ . '/config/data.php';

$user = $_SESSION['user'];

$id       = (int) ($_POST['id'] ?? 0);
$teleponMentah = trim($_POST['telepon'] ?? '');

$slip = $id > 0 ? getRiwayatById($id, (int) $user['id']) : null;

if (!$slip) {
    $_SESSION['dashboard_notif'] = ['ok' => false, 'pesan' => 'Slip gaji tidak ditemukan.'];
    header('Location: dashboard.php');
    exit;
}


$telepon = preg_replace('/[^0-9]/', '', $teleponMentah);
if ($telepon === '' ) {
    $_SESSION['dashboard_notif'] = ['ok' => false, 'pesan' => 'Nomor WhatsApp tujuan wajib diisi.'];
    header('Location: dashboard.php');
    exit;
}
if (substr($telepon, 0, 1) === '0') {
    $telepon = '62' . substr($telepon, 1);           
} elseif (substr($telepon, 0, 2) !== '62') {
    $telepon = '62' . $telepon;                       
}


$linkCetak = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://')
    . $_SERVER['HTTP_HOST']
    . dirname($_SERVER['SCRIPT_NAME'])
    . '/cetak_slip.php?id=' . $slip['id'] . '&print=1';

$pesan = "Slip Gaji Karyawan\n"
    . "Nama: {$slip['nama']}\n"
    . "NIK: {$slip['nik']}\n"
    . "Jabatan: {$slip['jabatan']}\n"
    . 'Periode: ' . date('d/m/Y', strtotime($slip['periode_awal'])) . ' - ' . date('d/m/Y', strtotime($slip['periode_akhir'])) . "\n"
    . 'Gaji Bersih: ' . rupiah((float) $slip['gaji_bersih']) . "\n\n"
    . "Slip gaji (PDF) bisa dilihat/dicetak lewat link berikut:\n"
    . $linkCetak;

$linkWa = 'https://wa.me/' . $telepon . '?text=' . rawurlencode($pesan);

header('Location: ' . $linkWa);
exit;
