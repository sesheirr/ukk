<?php
/**
 * tambah_periode.php
 * ---------------------------------------------------------
 * Menerima submit dari modal "+ Periode" di dashboard.php.
 * Bisa dipakai untuk 2 mode:
 *  - Tambah periode baru   : periode_id kosong/0
 *  - Update periode lama   : periode_id diisi id periode
 *
 * Karyawan yang dicentang dikirim sebagai NIK (karyawan_nik[]),
 * lalu dicocokkan lagi ke getDaftarKaryawan() di server supaya
 * data yang tersimpan (nama/jabatan/gaji_pokok) selalu berasal
 * dari data karyawan yang sah milik user ini -- bukan sekadar
 * dipercaya mentah-mentah dari form.
 * ---------------------------------------------------------
 */
require_once __DIR__ . '/config/auth_guard.php';
require_once __DIR__ . '/config/data.php';

$user   = $_SESSION['user'];
$userId = (int) $user['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$periodeId    = (int) ($_POST['periode_id'] ?? 0);
$periodeAwal  = trim($_POST['periode_awal'] ?? '');
$periodeAkhir = trim($_POST['periode_akhir'] ?? '');
$nikTerpilih  = array_filter(array_map('trim', $_POST['karyawan_nik'] ?? []));

if ($periodeAwal === '' || $periodeAkhir === '') {
    $_SESSION['dashboard_notif'] = ['ok' => false, 'pesan' => 'Periode Awal dan Periode Akhir wajib diisi.'];
    $_SESSION['open_modal_periode'] = true;
    header('Location: dashboard.php');
    exit;
}
if (strtotime($periodeAkhir) < strtotime($periodeAwal)) {
    $_SESSION['dashboard_notif'] = ['ok' => false, 'pesan' => 'Periode Akhir tidak boleh sebelum Periode Awal.'];
    $_SESSION['open_modal_periode'] = true;
    header('Location: dashboard.php');
    exit;
}
if (empty($nikTerpilih)) {
    $_SESSION['dashboard_notif'] = ['ok' => false, 'pesan' => 'Pilih minimal satu karyawan untuk periode ini.'];
    $_SESSION['open_modal_periode'] = true;
    header('Location: dashboard.php');
    exit;
}

// Cocokkan NIK yang dicentang dengan data karyawan yang benar-benar
// ada di riwayat slip gaji milik user ini (mencegah data palsu).
$daftarKaryawan   = getDaftarKaryawan($userId);
$karyawanTerpilih = array_values(array_filter(
    $daftarKaryawan,
    fn($k) => in_array($k['nik'], $nikTerpilih, true)
));

if (empty($karyawanTerpilih)) {
    $_SESSION['dashboard_notif'] = ['ok' => false, 'pesan' => 'Data karyawan yang dipilih tidak valid.'];
    $_SESSION['open_modal_periode'] = true;
    header('Location: dashboard.php');
    exit;
}

if ($periodeId > 0) {
    $berhasil = updatePeriode($periodeId, $userId, $periodeAwal, $periodeAkhir, $karyawanTerpilih);
    $pesan    = $berhasil
        ? 'Periode berhasil diperbarui.'
        : 'Periode tidak ditemukan atau bukan milik Anda.';
} else {
    createPeriode($userId, $periodeAwal, $periodeAkhir, $karyawanTerpilih);
    $berhasil = true;
    $pesan    = 'Periode baru berhasil dibuat.';
}

$_SESSION['dashboard_notif'] = ['ok' => $berhasil, 'pesan' => $pesan];
header('Location: dashboard.php');
exit;
