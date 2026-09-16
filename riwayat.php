<?php

require_once __DIR__ . '/config/auth_guard.php';
require_once __DIR__ . '/config/data.php';

$user    = $_SESSION['user'];
$riwayat = getRiwayat((int) $user['id']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Riwayat Gaji</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
<style>
  table{ width:100%; border-collapse:collapse; font-size:13px; }
  th, td{ padding:10px 12px; text-align:left; border-bottom:1px solid var(--border); }
  th{ color:var(--muted); font-size:11.5px; text-transform:none; font-weight:600; }
  td.num{ text-align:right; font-variant-numeric:tabular-nums; }
  td.bersih{ font-weight:700; color:var(--teal); }
  .empty{ text-align:center; color:var(--muted); padding:40px 0; font-size:13.5px; }
</style>
</head>
<body>
  <div class="page-wrap">
    <div class="topbar">
      <div class="brand" style="display:flex;align-items:center;gap:8px;font-weight:800;">
        <span style="width:9px;height:9px;border-radius:3px;background:var(--teal);display:inline-block;"></span>
        Slip Gaji
      </div>
      <div>
        <a class="logout" href="dashboard.php">&larr; Dashboard</a>
        <a class="logout" href="form_gaji.php">+ Tambah</a>
        <a class="logout" href="logout.php">Keluar</a>
      </div>
    </div>

    <div class="card" style="max-width:820px;">
      <div class="card-head">
        <h1>Riwayat Slip Gaji</h1>
        <p><?= htmlspecialchars($user['nama']) ?> — <?= count($riwayat) ?> slip tercatat</p>
      </div>
      <div class="card-body">
        <?php if (empty($riwayat)): ?>
          <p class="empty">Belum ada slip gaji yang dicetak.</p>
        <?php else: ?>
          <table>
            <thead>
              <tr>
                <th>Tanggal Dibuat</th>
                <th>Periode</th>
                <th class="num">Penghasilan</th>
                <th class="num">Potongan</th>
                <th class="num">Gaji Bersih</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($riwayat as $r): ?>
                <tr>
                  <td><?= date('d M Y H:i', strtotime($r['created_at'])) ?></td>
                  <td><?= date('d/m/Y', strtotime($r['periode_awal'])) ?> - <?= date('d/m/Y', strtotime($r['periode_akhir'])) ?></td>
                  <td class="num"><?= rupiah((float) $r['total_penghasilan']) ?></td>
                  <td class="num"><?= rupiah((float) $r['total_potongan']) ?></td>
                  <td class="num bersih"><?= rupiah((float) $r['gaji_bersih']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>
  </div>
</body>
</html>
