<?php
/**
 * cetak_slip.php
 * ---------------------------------------------------------
 * Menerima data dari form_gaji.php (method POST), lalu:
 * 1. Memvalidasi captcha (dicocokkan dengan session, BUKAN
 *    dipercaya dari client) -> mencegah manipulasi via DevTools.
 * 2. Menghitung ULANG Total Penghasilan, Total Potongan, dan
 *    Gaji Bersih di sisi SERVER (server-side calculation),
 *    supaya hasil akhir tidak bisa dicurangi dari browser.
 * 3. Menampilkan slip gaji yang siap dicetak (tombol "Cetak
 *    PDF" memanfaatkan fitur print bawaan browser).
 * ---------------------------------------------------------
 */
require_once __DIR__ . '/config/auth_guard.php';
require_once __DIR__ . '/config/data.php';

$user     = $_SESSION['user'];
$autoPrint = isset($_GET['print']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    // Validasi captcha
    $captchaInput  = trim($_POST['captcha'] ?? '');
    $captchaAnswer = $_SESSION['captcha_answer'] ?? null;

    if ($captchaAnswer === null || $captchaInput === '' || (int) $captchaInput !== (int) $captchaAnswer) {
        $_SESSION['form_error'] = 'Jawaban captcha salah. Silakan coba lagi.';
        header('Location: form_gaji.php');
        exit;
    }
    unset($_SESSION['captcha_answer']); // captcha hanya berlaku sekali pakai

    // Validasi & ambil input
    $nama    = trim($_POST['nama'] ?? '');
    $nik     = trim($_POST['nik'] ?? '');
    $jabatan = trim($_POST['jabatan'] ?? '');

    if ($nama === '' || $nik === '' || $jabatan === '') {
        $_SESSION['form_error'] = 'Nama, NIK, dan Jabatan wajib diisi.';
        header('Location: form_gaji.php');
        exit;
    }

    $gajiPokok = parseAngka($_POST['gaji_pokok'] ?? '0');
    $lembur    = parseAngka($_POST['lembur'] ?? '0');
    $pinjaman  = parseAngka($_POST['pinjaman'] ?? '0');

    // Perhitungan gaji (sesuai rumus pada soal), dihitung ULANG di server
    $totalPenghasilan = $gajiPokok + $lembur;               // Gaji Pokok + Lembur
    $totalPotongan    = $pinjaman;                           // Pinjaman Karyawan
    $gajiBersih       = $totalPenghasilan - $totalPotongan;  // Penghasilan - Potongan

    // Periode Awal & Akhir dikirim dari form_gaji.php
    $periodeAwal  = trim($_POST['periode_awal'] ?? '');
    $periodeAkhir = trim($_POST['periode_akhir'] ?? '');

    if ($periodeAwal === '' || $periodeAkhir === '') {
        $periodeAwal  = '2026-11-25';
        $periodeAkhir = '2026-12-25';
    }

    // Simpan slip ke tabel riwayat_gaji (histori) di database.
    // Id yang dikembalikan dipakai supaya halaman ini & aksi
    // (cetak/kirim email/kirim WA) selalu mengacu ke baris yang
    // sama di database, bukan ke session yang mudah hilang.
    $slipId = saveRiwayat([
        'user_id'          => $user['id'],
        'nama'             => $nama,
        'nik'              => $nik,
        'jabatan'          => $jabatan,
        'gajiPokok'        => $gajiPokok,
        'lembur'           => $lembur,
        'pinjaman'         => $pinjaman,
        'totalPenghasilan' => $totalPenghasilan,
        'totalPotongan'    => $totalPotongan,
        'gajiBersih'       => $gajiBersih,
        'periodeAwal'      => $periodeAwal,
        'periodeAkhir'     => $periodeAkhir,
    ]);

} else {
    // ---------- Kondisi 2: diakses via GET, misal dari tombol "Cetak PDF"
    // di dashboard.php (cetak_slip.php?id=..) ----------
    $id  = (int) ($_GET['id'] ?? 0);
    $row = $id > 0 ? getRiwayatById($id, (int) $user['id']) : null;

    if (!$row) {
        header('Location: dashboard.php');
        exit;
    }

    $slipId           = (int) $row['id'];
    $nama             = $row['nama'];
    $nik              = $row['nik'];
    $jabatan          = $row['jabatan'];
    $gajiPokok        = (float) $row['gaji_pokok'];
    $lembur           = (float) $row['lembur'];
    $pinjaman         = (float) $row['pinjaman_karyawan'];
    $totalPenghasilan = (float) $row['total_penghasilan'];
    $totalPotongan    = (float) $row['total_potongan'];
    $gajiBersih       = (float) $row['gaji_bersih'];
    $periodeAwal      = $row['periode_awal'];
    $periodeAkhir     = $row['periode_akhir'];
}

$notif = $_SESSION['dashboard_notif'] ?? null;
unset($_SESSION['dashboard_notif']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Slip Gaji - <?= htmlspecialchars($nama) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
<style>
  .grid{
    display: flex !important;
    flex-direction: column !important;
    gap: 14px !important;
    margin-bottom: 22px !important;
  }
  .grid .field{
    width: 100% !important;
  }
  .modal-overlay{ display:none; position:fixed; inset:0; background:rgba(13,33,56,.45); align-items:center; justify-content:center; z-index:50; padding:16px; }
  .modal-overlay.show{ display:flex; }
  .modal-box{ background:#fff; border-radius:14px; width:100%; max-width:380px; padding:22px 22px 20px; box-shadow:0 20px 50px rgba(0,0,0,.25); }
  .modal-box h3{ margin:0 0 4px; font-size:16px; }
  .modal-box p{ margin:0 0 16px; font-size:12.5px; color:var(--muted); }
  .modal-box label{ font-size:11.5px; font-weight:600; color:var(--muted); }
  .modal-box input{ width:100%; padding:10px 12px; border:1.5px solid var(--border); border-radius:9px; font-size:14px; font-family:inherit; margin:6px 0 16px; }
  .modal-actions{ display:flex; gap:10px; }
  .modal-actions button{ flex:1; padding:10px; border-radius:9px; font-size:13.5px; font-weight:700; border:1.5px solid var(--border); cursor:pointer; font-family:inherit; }
  .modal-actions .batal{ background:#fff; color:var(--navy); }
  .modal-actions .kirim{ background:var(--teal); color:#fff; border-color:var(--teal); }
  .share-tabs{ display:flex; gap:8px; margin-bottom:14px; }
  .share-tab{
    flex:1; padding:9px; border-radius:9px; border:1.5px solid var(--border); background:#fff;
    font-size:12.5px; font-weight:700; font-family:inherit; cursor:pointer; color:var(--muted);
  }
  .share-tab.active{ background:var(--navy); border-color:var(--navy); color:#fff; }
</style>
</head>
<body>
  <div class="page-wrap">
    <div class="topbar no-print">
      <div class="brand" style="display:flex;align-items:center;gap:8px;font-weight:800;">
        <span style="width:9px;height:9px;border-radius:3px;background:var(--teal);display:inline-block;"></span>
        Slip Gaji
      </div>
      <div class="nav-links">
        <a class="logout nav-dashboard" href="dashboard.php">&larr; Data Gaji</a>
        <a class="logout nav-tambah" href="form_gaji.php">+ Tambah</a>
        <a class="logout nav-keluar" href="logout.php">Keluar</a>
      </div>
    </div>

    <div class="card">
      <div class="card-head" style="text-align: center;">
        <h1>Slip Gaji Karyawan</h1>
        <p style="margin-top:5px; font-size:13px; color:#B9C7D6;">Periode <?= formatTanggalIndo($periodeAwal) ?> &ndash; <?= formatTanggalIndo($periodeAkhir) ?></p>
      </div>
      <div class="card-body">

        <?php if ($notif): ?>
          <div class="<?= $notif['ok'] ? 'alert-success' : 'alert-error' ?> no-print"><?= htmlspecialchars($notif['pesan']) ?></div>
        <?php endif; ?>

        <div class="grid">
          <div class="field"><label>Nama</label>
            <input type="text" value="<?= htmlspecialchars($nama) ?>" disabled></div>
          <div class="field"><label>NIK</label>
            <input type="text" value="<?= htmlspecialchars($nik) ?>" disabled></div>
          <div class="field"><label>Jabatan</label>
            <input type="text" value="<?= htmlspecialchars($jabatan) ?>" disabled></div>
        </div>

        <div class="cols">
          <div class="col">
            <p class="col-title income"><span class="sw"></span>PENGHASILAN</p>
            <div class="line"><span>Gaji Pokok</span><span><?= rupiah($gajiPokok) ?></span></div>
            <div class="line"><span>Lembur</span><span><?= rupiah($lembur) ?></span></div>
            <div class="subtotal"><span>Total Penghasilan</span><span><?= rupiah($totalPenghasilan) ?></span></div>
          </div>
          <div class="col">
            <p class="col-title deduct"><span class="sw"></span>POTONGAN</p>
            <div class="line"><span>Pinjaman Karyawan</span><span><?= rupiah($pinjaman) ?></span></div>
            <div class="subtotal"><span>Total Potongan</span><span><?= rupiah($totalPotongan) ?></span></div>
          </div>
        </div>

        <div class="result">
          <span class="lbl">GAJI BERSIH</span>
          <span class="val"><?= rupiah($gajiBersih) ?></span>
        </div>

        <div class="actions no-print">
          <button type="button" class="btn secondary" onclick="window.print()">Cetak PDF</button>
          <button type="button" class="btn" onclick="bukaModalShare()">Bagikan</button>
        </div>

      </div>
    </div>
  </div>

  <!-- Modal: bagikan (pilih WhatsApp atau Email, tujuan diisi manual oleh admin) -->
  <div class="modal-overlay no-print" id="modal-share">
    <div class="modal-box">
      <h3>Bagikan Slip Gaji</h3>
      <p>Pilih media pengiriman, lalu isi tujuannya.</p>

      <div class="share-tabs">
        <button type="button" class="share-tab" id="tab-btn-wa" onclick="pilihShareTab('wa')">WhatsApp</button>
        <button type="button" class="share-tab" id="tab-btn-email" onclick="pilihShareTab('email')">Email</button>
      </div>

      <form id="form-share-wa" action="kirim_wa.php" method="POST" target="_blank">
        <input type="hidden" name="id" value="<?= (int) $slipId ?>">
        <label>Nomor WhatsApp Tujuan</label>
        <input type="text" name="telepon" placeholder="contoh: 081234567890" required>
        <div class="modal-actions">
          <button type="button" class="batal" onclick="tutupModalShare()">Batal</button>
          <button type="submit" class="kirim">Kirim</button>
        </div>
      </form>

      <form id="form-share-email" action="kirim_email.php" method="POST" target="_blank" style="display:none">
        <input type="hidden" name="id" value="<?= (int) $slipId ?>">
        <label>Alamat Email Tujuan</label>
        <input type="email" name="email" placeholder="contoh: karyawan@email.com" required>
        <div class="modal-actions">
          <button type="button" class="batal" onclick="tutupModalShare()">Batal</button>
          <button type="submit" class="kirim">Kirim</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function bukaModalShare() {
      pilihShareTab('wa');
      document.getElementById('modal-share').classList.add('show');
    }
    function tutupModalShare() {
      document.getElementById('modal-share').classList.remove('show');
    }
    function pilihShareTab(tab) {
      document.getElementById('form-share-wa').style.display = (tab === 'wa') ? 'block' : 'none';
      document.getElementById('form-share-email').style.display = (tab === 'email') ? 'block' : 'none';
      document.getElementById('tab-btn-wa').classList.toggle('active', tab === 'wa');
      document.getElementById('tab-btn-email').classList.toggle('active', tab === 'email');
    }
  </script>

  <?php if ($autoPrint): ?>
  <script>window.addEventListener('load', function () { window.print(); });</script>
  <?php endif; ?>
</body>
</html>
