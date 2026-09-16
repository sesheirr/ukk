<?php

require_once __DIR__ . '/config/auth_guard.php'; 
require_once __DIR__ . '/config/data.php';

$user = $_SESSION['user'];

// Initialize $error to avoid "Undefined variable" warning
$error = $_SESSION['form_error'] ?? $_SESSION['error'] ?? null;
unset($_SESSION['form_error'], $_SESSION['error']); // clear it after reading

$periodeAwal  = trim($_GET['periode_awal'] ?? $_POST['periode_awal'] ?? '');
$periodeAkhir = trim($_GET['periode_akhir'] ?? $_POST['periode_akhir'] ?? '');

// Fallback periode default jika tidak diset
if ($periodeAwal === '' || $periodeAkhir === '') {
    $periodeAwal  = '2026-11-25';
    $periodeAkhir = '2026-12-25';
}

$op = '×';
$a = rand(1, 10);
$b = rand(1, 10);
$hasil = $a * $b;
$_SESSION['captcha_answer'] = $hasil;
$captchaQuestion = "$a $op $b";

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Slip Gaji</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
<style>
  .periode-badge-wrap{
    margin-top: 5px;
    font-size: 13px;
    font-weight: 500;
    color: #B9C7D6;
    letter-spacing: .01em;
  }
  .grid{
    display: flex !important;
    flex-direction: column !important;
    gap: 14px !important;
    margin-bottom: 22px !important;
  }
  .grid .field{
    width: 100% !important;
  }
</style>
</head>
<body>
  <div class="page-wrap">
    <div class="topbar">
      <div class="brand" style="display:flex;align-items:center;gap:8px;font-weight:800;">
        <span style="width:9px;height:9px;border-radius:3px;background:var(--teal);display:inline-block;"></span>
        Slip Gaji
      </div>
      <div class="nav-links">
        <a class="logout nav-dashboard" href="dashboard.php">&larr; Data Gaji</a>
        <a class="logout nav-keluar" href="logout.php">Keluar</a>
      </div>
    </div>

    <div class="card">
      <div class="card-head" style="text-align: center;">
        <h1>Slip Gaji Karyawan</h1>
        <p class="periode-badge-wrap">Periode <?= formatTanggalIndo($periodeAwal) ?> &ndash; <?= formatTanggalIndo($periodeAkhir) ?></p>
      </div>
      <div class="card-body">

        <?php if ($error): ?>
          <div class="alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="cetak_slip.php" method="POST" id="form-gaji">
          <input type="hidden" name="periode_awal" value="<?= htmlspecialchars($periodeAwal) ?>">
          <input type="hidden" name="periode_akhir" value="<?= htmlspecialchars($periodeAkhir) ?>">

          <div class="grid">
            <div class="field">
              <label>Nama</label>
              <input type="text" name="nama" value="" placeholder="Masukkan nama" required>
            </div>
            <div class="field">
              <label>NIK</label>
              <input type="text" name="nik" value="" placeholder="Masukkan NIK" required>
            </div>
            <div class="field">
              <label>Jabatan</label>
              <input type="text" name="jabatan" value="" placeholder="Masukkan jabatan" required>
            </div>
          </div>

          <div class="cols">
            <div class="col">
              <p class="col-title income"><span class="sw"></span>PENGHASILAN</p>
              <div class="line">
                <span>Gaji Pokok</span>
                <input type="text" id="gaji_pokok" name="gaji_pokok" value="" placeholder="0" required>
              </div>
              <div class="line">
                <span>Lembur</span>
                <input type="text" id="lembur" name="lembur" value="" placeholder="0">
              </div>
              <div class="subtotal"><span>Total Penghasilan</span><span id="total_penghasilan">Rp 0</span></div>
            </div>
            <div class="col">
              <p class="col-title deduct"><span class="sw"></span>POTONGAN</p>
              <div class="line">
                <span>Pinjaman Karyawan</span>
                <input type="text" id="pinjaman" name="pinjaman" value="" placeholder="0">
              </div>
              <div class="subtotal"><span>Total Potongan</span><span id="total_potongan">Rp 0</span></div>
            </div>
          </div>

          <div class="captcha-row">
            <div class="captcha-box" id="captcha-question"><?= htmlspecialchars($captchaQuestion) ?></div>
            <div class="refresh" id="refresh-captcha" title="Ganti soal captcha">&#8635;</div>
            <input type="text" name="captcha" placeholder="Masukkan hasil captcha" required>
          </div>
          <p class="field-hint">Jawab soal hitung di atas untuk memverifikasi bahwa Anda bukan robot.</p>

          <div class="actions">
            <button type="submit" class="btn">Hitung &amp; Cetak Slip</button>
          </div>
        </form>

      </div>
    </div>
  </div>

  <script src="assets/js/script.js"></script>
</body>
</html>
