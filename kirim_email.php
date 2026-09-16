<?php

require_once __DIR__ . '/config/auth_guard.php';
require_once __DIR__ . '/config/data.php';

$user = $_SESSION['user'];

$id          = (int) ($_POST['id'] ?? 0);
$emailTujuan = trim($_POST['email'] ?? '');


$slip = $id > 0 ? getRiwayatById($id, (int) $user['id']) : null;

if (!$slip) {
    $_SESSION['dashboard_notif'] = ['ok' => false, 'pesan' => 'Slip gaji tidak ditemukan.'];
    header('Location: dashboard.php');
    exit;
}

if ($emailTujuan === '' || !filter_var($emailTujuan, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['dashboard_notif'] = ['ok' => false, 'pesan' => 'Alamat email tujuan tidak valid.'];
    header('Location: dashboard.php');
    exit;
}


$linkCetak = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://')
    . $_SERVER['HTTP_HOST']
    . dirname($_SERVER['SCRIPT_NAME'])
    . '/cetak_slip.php?id=' . $slip['id'] . '&print=1';

$periode = date('d/m/Y', strtotime($slip['periode_awal'])) . ' - ' . date('d/m/Y', strtotime($slip['periode_akhir']));

$subjek = 'Slip Gaji - ' . $slip['nama'] . ' (Periode ' . $periode . ')';

$isi = "Slip Gaji Karyawan\n"
    . "Nama: {$slip['nama']}\n"
    . "NIK: {$slip['nik']}\n"
    . "Jabatan: {$slip['jabatan']}\n"
    . "Periode: {$periode}\n\n"
    . 'Total Penghasilan: ' . rupiah((float) $slip['total_penghasilan']) . "\n"
    . 'Total Potongan: ' . rupiah((float) $slip['total_potongan']) . "\n"
    . 'Gaji Bersih: ' . rupiah((float) $slip['gaji_bersih']) . "\n\n"
    . "Slip gaji (PDF) bisa dilihat/dicetak lewat link berikut:\n"
    . $linkCetak;


$linkGmail = 'https://mail.google.com/mail/?view=cm&fs=1'
    . '&to=' . rawurlencode($emailTujuan)
    . '&su=' . rawurlencode($subjek)
    . '&body=' . rawurlencode($isi);

header('Location: ' . $linkGmail);
exit;
