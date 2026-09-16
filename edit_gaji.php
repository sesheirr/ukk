<?php
require_once __DIR__ . '/config/auth_guard.php';
require_once __DIR__ . '/config/data.php';

$user = $_SESSION['user'];
$id   = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$row  = $id > 0 ? getRiwayatById($id, (int) $user['id']) : null;

if (!$row) {
    header('Location: dashboard.php');
    exit;
}

$error = $_SESSION['form_error'] ?? '';
unset($_SESSION['form_error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama    = trim($_POST['nama'] ?? '');
    $nik     = trim($_POST['nik'] ?? '');
    $jabatan = trim($_POST['jabatan'] ?? '');

    if ($nama === '' || $nik === '' || $jabatan === '') {
        $_SESSION['form_error'] = 'Nama, NIK, dan Jabatan wajib diisi.';
        header('Location: edit_gaji.php?id=' . $id);
        exit;
    }

    $gajiPokok = parseAngka($_POST['gaji_pokok'] ?? '0');
    $lembur    = parseAngka($_POST['lembur'] ?? '0');
    $pinjaman  = parseAngka($_POST['pinjaman'] ?? '0');

    $totalPenghasilan = $gajiPokok + $lembur;
    $totalPotongan    = $pinjaman;
    $gajiBersih       = $totalPenghasilan - $totalPotongan;

    updateRiwayat($id, (int) $user['id'], [
        'nama'             => $nama,
        'nik'              => $nik,
        'jabatan'          => $jabatan,
        'gajiPokok'        => $gajiPokok,
        'lembur'           => $lembur,
        'pinjaman'         => $pinjaman,
        'totalPenghasilan' => $totalPenghasilan,
        'totalPotongan'    => $totalPotongan,
        'gajiBersih'       => $gajiBersih,
        'periodeAwal'      => $row['periode_awal'],
        'periodeAkhir'     => $row['periode_akhir'],
    ]);

    $_SESSION['dashboard_notif'] = ['ok' => true, 'pesan' => 'Slip gaji berhasil diperbarui.'];
    header('Location: dashboard.php');
    exit;
}

// ---- GET: siapkan nilai awal untuk mengisi form ----
$nama      = $row['nama'];
$nik       = $row['nik'];
$jabatan   = $row['jabatan'];
$gajiPokok = number_format((float) $row['gaji_pokok'], 0, ',', '.');
$lembur    = number_format((float) $row['lembur'], 0, ',', '.');
$pinjaman  = number_format((float) $row['pinjaman_karyawan'], 0, ',', '.');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Edit Slip Gaji</title>
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
        <p style="margin-top:5px; font-size:13px; color:#B9C7D6;">Periode <?= formatTanggalIndo($row['periode_awal']) ?> &ndash; <?= formatTanggalIndo($row['periode_akhir']) ?></p>
      </div>
      <div class="card-body">

        <?php if ($error): ?>
          <div class="alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="edit_gaji.php?id=<?= (int) $id ?>" method="POST" id="form-gaji">
          <input type="hidden" name="id" value="<?= (int) $id ?>">
          <div class="grid">
            <div class="field">
              <label>Nama</label>
              <input type="text" name="nama" value="<?= htmlspecialchars($nama) ?>" required>
            </div>
            <div class="field">
              <label>NIK</label>
              <input type="text" name="nik" value="<?= htmlspecialchars($nik) ?>" required>
            </div>
            <div class="field">
              <label>Jabatan</label>
              <input type="text" name="jabatan" value="<?= htmlspecialchars($jabatan) ?>" required>
            </div>
          </div>

          <div class="cols">
            <div class="col">
              <p class="col-title income"><span class="sw"></span>PENGHASILAN</p>
              <div class="line">
                <span>Gaji Pokok</span>
                <input type="text" id="gaji_pokok" name="gaji_pokok" value="<?= htmlspecialchars($gajiPokok) ?>" required>
              </div>
              <div class="line">
                <span>Lembur</span>
                <input type="text" id="lembur" name="lembur" value="<?= htmlspecialchars($lembur) ?>">
              </div>
              <div class="subtotal"><span>Total Penghasilan</span><span id="total_penghasilan">Rp 0</span></div>
            </div>
            <div class="col">
              <p class="col-title deduct"><span class="sw"></span>POTONGAN</p>
              <div class="line">
                <span>Pinjaman Karyawan</span>
                <input type="text" id="pinjaman" name="pinjaman" value="<?= htmlspecialchars($pinjaman) ?>">
              </div>
              <div class="subtotal"><span>Total Potongan</span><span id="total_potongan">Rp 0</span></div>
            </div>
          </div>

          <div class="actions">
            <a href="dashboard.php" class="btn secondary">Batal</a>
            <button type="submit" class="btn">Simpan Perubahan</button>
          </div>
        </form>

      </div>
    </div>
  </div>

  <script src="assets/js/script.js"></script>
</body>
</html>
