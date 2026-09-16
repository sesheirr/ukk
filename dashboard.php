<?php

require_once __DIR__ . '/config/auth_guard.php';
require_once __DIR__ . '/config/data.php';

$user    = $_SESSION['user'];
$riwayat = getRiwayat((int) $user['id']);

$notif = $_SESSION['dashboard_notif'] ?? null;
unset($_SESSION['dashboard_notif']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Data Gaji | Slip Gaji</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
<style>
  .toolbar{ display:flex; align-items:center; justify-content:space-between; margin-bottom:18px; gap:14px; flex-wrap:wrap; }
  .toolbar > p{ margin:0; font-size:13px; color:var(--muted); }
  .toolbar-actions{ display:flex; align-items:center; gap:10px; }

  .btn-add{
    display:inline-flex; align-items:center; gap:7px; background:var(--teal); color:#fff;
    border:none; border-radius:9px; padding:10px 18px; font-size:13.5px; font-weight:700;
    cursor:pointer; font-family:inherit; text-decoration:none; white-space:nowrap;
  }
  .btn-add:hover{ background:#176E55; }
  .btn-add .plus{ font-size:16px; line-height:1; }

  .table-wrap{ width:100%; }
  table{ width:100%; border-collapse:collapse; font-size:13px; }
  th, td{ padding:11px 12px; text-align:left; border-bottom:1px solid var(--border); vertical-align:middle; }
  th{ color:var(--muted); font-size:11.5px; text-transform:none; font-weight:600; text-align:left; }
  td.num{ text-align:left; font-variant-numeric:tabular-nums; }
  td.bersih{ font-weight:700; color:var(--teal); text-align:left; }
  .empty{ text-align:center; color:var(--muted); padding:40px 0; font-size:13.5px; }

  /* ---- Tabel jadi kartu bertumpuk di layar kecil ---- */
  @media (max-width:680px){
    thead{ display:none; }
    table, tbody, tr, td{ display:block; width:100%; }
    tr{
      border:1.5px solid var(--border); border-radius:12px; padding:6px 14px;
      margin-bottom:12px; background:#fff;
    }
    td{ border-bottom:1px dashed var(--border); padding:10px 0; display:flex;
        align-items:center; justify-content:space-between; gap:12px; }
    td:last-child{ border-bottom:none; }
    td::before{
      content:attr(data-label); font-size:11px; font-weight:700; color:var(--muted);
      flex-shrink:0;
    }
    td[data-label="Periode"]{ text-align:right; }
    td.bersih{ font-size:15px; }
    td[data-label="Aksi"]::before{ display:none; }
    td[data-label="Aksi"]{ justify-content:flex-end; padding-top:12px; }
    .aksi{ justify-content:flex-end; }
  }

  .aksi{ display:flex; gap:6px; flex-wrap:wrap; align-items:center; }
  .aksi-form{ display:inline-flex; margin:0; }
  .aksi button, .aksi a{
    display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px;
    border-radius:7px; border:1.5px solid var(--border); background:#fff; padding:0;
    color:var(--navy); cursor:pointer; text-decoration:none; font-family:inherit;
  }
  .aksi svg{ width:15px; height:15px; }
  .aksi .cetak:hover{ background:#F0F2F5; }
  .aksi .share:hover{ background:#EFF7F3; border-color:#CFE9DE; color:var(--teal); }
  .aksi .edit:hover{ background:#EFF4FD; border-color:#C7DBF7; color:var(--nav-blue); }
  .aksi .hapus:hover{ background:#FDEDEA; border-color:#F3C6BB; color:var(--nav-red); }

  .share-tabs{ display:flex; gap:8px; margin-bottom:14px; }
  .share-tab{
    flex:1; padding:9px; border-radius:9px; border:1.5px solid var(--border); background:#fff;
    font-size:12.5px; font-weight:700; font-family:inherit; cursor:pointer; color:var(--muted);
  }
  .share-tab.active{ background:var(--navy); border-color:var(--navy); color:#fff; }

  .modal-overlay{
    display:none; position:fixed; inset:0; background:rgba(13,33,56,.45);
    align-items:center; justify-content:center; z-index:50; padding:16px;
  }
  .modal-overlay.show{ display:flex; }
  .modal-box{
    background:#fff; border-radius:14px; width:100%; max-width:400px;
    padding:24px 22px 22px; box-shadow:0 20px 50px rgba(0,0,0,.25);
  }
  .modal-box h3{ margin:0 0 4px; font-size:17px; color:var(--navy); }
  .modal-box p{ margin:0 0 16px; font-size:12.5px; color:var(--muted); }
  .modal-box label{ font-size:11.5px; font-weight:600; color:var(--muted); display:block; margin-bottom:5px; }
  .modal-box input, .modal-box select{
    width:100%; padding:10px 12px; border:1.5px solid var(--border); border-radius:9px;
    font-size:14px; font-family:inherit; margin:0 0 14px; outline:none; background:#fff;
  }
  .modal-box select:focus, .modal-box input:focus{ border-color:var(--navy); }

  .periode-select-grid{ display:grid; grid-template-columns:1fr 1fr; gap:12px; }

  .preview-periode-box{
    background:#F0F8F5; border:1.5px dashed var(--teal); border-radius:9px;
    padding:10px 14px; margin-bottom:18px; text-align:center;
  }
  .preview-periode-box .sub{ font-size:11px; font-weight:700; color:var(--teal); text-transform:uppercase; letter-spacing:.04em; }
  .preview-periode-box .val{ font-size:14px; font-weight:700; color:var(--navy); margin-top:3px; }

  .modal-actions{ display:flex; gap:10px; margin-top:4px; }
  .modal-actions button{ flex:1; padding:11px; border-radius:9px; font-size:13.5px; font-weight:700; border:1.5px solid var(--border); cursor:pointer; font-family:inherit; }
  .modal-actions .batal{ background:#fff; color:var(--navy); }
  .modal-actions .kirim{ background:var(--teal); color:#fff; border-color:var(--teal); }
  .modal-actions .kirim:hover{ background:#176E55; }
</style>
</head>
<body>
  <div class="page-wrap">
    <div class="topbar" style="max-width:920px;">
      <div class="brand" style="display:flex;align-items:center;gap:8px;font-weight:800;">
        <span style="width:9px;height:9px;border-radius:3px;background:var(--teal);display:inline-block;"></span>
        Slip Gaji
      </div>
      <div class="nav-links">
        <a class="logout nav-keluar" href="logout.php">Keluar</a>
      </div>
    </div>

    <div class="card" style="max-width:920px;">
      <div class="card-head">
        <h1>Data Gaji</h1>
        <p><?= count($riwayat) ?> Slip Tercatat</p>
      </div>
      <div class="card-body">

        <?php if ($notif): ?>
          <div class="<?= $notif['ok'] ? 'alert-success' : 'alert-error' ?>"><?= htmlspecialchars($notif['pesan']) ?></div>
        <?php endif; ?>

        <div class="toolbar">
          <p>Daftar slip gaji yang pernah dibuat. Klik "+ Tambah" untuk membuat slip baru.</p>
          <div class="toolbar-actions">
            <button type="button" class="btn-add" onclick="bukaModalPilihPeriode()">
              <span class="plus">+</span> Tambah
            </button>
          </div>
        </div>

        <?php if (empty($riwayat)): ?>
          <p class="empty">Belum ada slip gaji. Klik "+ Tambah" untuk membuat slip pertama.</p>
        <?php else: ?>
          <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Nama</th>
                <th>Jabatan</th>
                <th>Periode</th>
                <th>Gaji Bersih</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($riwayat as $r): ?>
                <tr>
                  <td data-label="Nama"><strong><?= htmlspecialchars($r['nama']) ?></strong></td>
                  <td data-label="Jabatan"><?= htmlspecialchars($r['jabatan']) ?></td>
                  <td data-label="Periode"><?= formatTanggalIndo($r['periode_awal']) ?> &ndash; <?= formatTanggalIndo($r['periode_akhir']) ?></td>
                  <td class="bersih" data-label="Gaji Bersih"><?= rupiah((float) $r['gaji_bersih']) ?></td>
                  <td data-label="Aksi">
                    <div class="aksi">
                      <a class="cetak" href="cetak_slip.php?id=<?= (int) $r['id'] ?>&print=1" target="_blank" title="Cetak PDF" aria-label="Cetak PDF">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                      </a>
                      <a class="edit" href="edit_gaji.php?id=<?= (int) $r['id'] ?>" title="Edit" aria-label="Edit">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                      </a>
                      <button type="button" class="share" onclick="bukaModalShare(<?= (int) $r['id'] ?>)" title="Bagikan (Email/WhatsApp)" aria-label="Bagikan">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line></svg>
                      </button>
                      <form class="aksi-form" action="hapus_gaji.php" method="POST" onsubmit="return confirm('Hapus slip gaji ini? Tindakan tidak bisa dibatalkan.');">
                        <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                        <button type="submit" class="hapus" title="Hapus" aria-label="Hapus">
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path><path d="M10 11v6M14 11v6M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path></svg>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Modal Pilih Periode sebelum ke Form Gaji -->
  <div class="modal-overlay" id="modal-pilih-periode">
    <div class="modal-box">
      <h3>Pilih Periode Gaji</h3>
      <p>Pilih bulan dan tahun penggajian untuk membuat formulir slip baru.</p>

      <form action="form_gaji.php" method="GET">
        <input type="hidden" name="periode_awal" id="input-periode-awal" value="2026-11-25">
        <input type="hidden" name="periode_akhir" id="input-periode-akhir" value="2026-12-25">

        <div class="periode-select-grid">
          <div>
            <label for="pilih-bulan">Bulan Penggajian</label>
            <select id="pilih-bulan" onchange="updatePeriodePreview()">
              <option value="1">Januari</option>
              <option value="2">Februari</option>
              <option value="3">Maret</option>
              <option value="4">April</option>
              <option value="5">Mei</option>
              <option value="6">Juni</option>
              <option value="7">Juli</option>
              <option value="8">Agustus</option>
              <option value="9">September</option>
              <option value="10">Oktober</option>
              <option value="11">November</option>
              <option value="12" selected>Desember</option>
            </select>
          </div>
          <div>
            <label for="pilih-tahun">Tahun</label>
            <select id="pilih-tahun" onchange="updatePeriodePreview()">
              <?php for ($y = 2024; $y <= 2030; $y++): ?>
                <option value="<?= $y ?>" <?= $y === 2026 ? 'selected' : '' ?>><?= $y ?></option>
              <?php endfor; ?>
            </select>
          </div>
        </div>

        <div class="preview-periode-box">
          <div class="sub">Rentang Periode Terpilih</div>
          <div class="val" id="preview-periode-text">25 Nov – 25 Des 2026</div>
        </div>

        <div class="modal-actions">
          <button type="button" class="batal" onclick="tutupModal('modal-pilih-periode')">Batal</button>
          <button type="submit" class="kirim">Lanjutkan</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal Bagikan Slip Gaji -->
  <div class="modal-overlay" id="modal-share">
    <div class="modal-box">
      <h3>Bagikan Slip Gaji</h3>
      <p>Pilih media pengiriman, lalu isi tujuannya.</p>

      <div class="share-tabs">
        <button type="button" class="share-tab" id="tab-btn-wa" onclick="pilihShareTab('wa')">WhatsApp</button>
        <button type="button" class="share-tab" id="tab-btn-email" onclick="pilihShareTab('email')">Email</button>
      </div>

      <form id="form-share-wa" action="kirim_wa.php" method="POST" target="_blank">
        <input type="hidden" name="id" id="share-wa-id">
        <label>Nomor WhatsApp Tujuan</label>
        <input type="text" name="telepon" placeholder="contoh: 081234567890" required>
        <div class="modal-actions">
          <button type="button" class="batal" onclick="tutupModal('modal-share')">Batal</button>
          <button type="submit" class="kirim">Kirim</button>
        </div>
      </form>

      <form id="form-share-email" action="kirim_email.php" method="POST" target="_blank" style="display:none">
        <input type="hidden" name="id" id="share-email-id">
        <label>Alamat Email Tujuan</label>
        <input type="email" name="email" placeholder="contoh: karyawan@email.com" required>
        <div class="modal-actions">
          <button type="button" class="batal" onclick="tutupModal('modal-share')">Batal</button>
          <button type="submit" class="kirim">Kirim</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    const NAMA_BULAN_PENDEK = ['Nov', 'Des', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    const NAMA_BULAN_ID = [
      'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
      'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'
    ];

    function updatePeriodePreview() {
      const bulan = parseInt(document.getElementById('pilih-bulan').value, 10);
      const tahun = parseInt(document.getElementById('pilih-tahun').value, 10);

      if (!bulan || !tahun) return;

      let prevBulan = bulan - 1;
      let prevTahun = tahun;
      if (prevBulan === 0) {
        prevBulan = 12;
        prevTahun = tahun - 1;
      }

      const strBulanAwal = String(prevBulan).padStart(2, '0');
      const strBulanAkhir = String(bulan).padStart(2, '0');

      const valAwal = `${prevTahun}-${strBulanAwal}-25`;
      const valAkhir = `${tahun}-${strBulanAkhir}-25`;

      document.getElementById('input-periode-awal').value = valAwal;
      document.getElementById('input-periode-akhir').value = valAkhir;

      const textAwal = `25 ${NAMA_BULAN_ID[prevBulan - 1]}` + (prevTahun !== tahun ? ` ${prevTahun}` : '');
      const textAkhir = `25 ${NAMA_BULAN_ID[bulan - 1]} ${tahun}`;

      document.getElementById('preview-periode-text').textContent = `${textAwal} – ${textAkhir}`;
    }

    function bukaModalPilihPeriode() {
      updatePeriodePreview();
      document.getElementById('modal-pilih-periode').classList.add('show');
    }

    function bukaModalShare(id) {
      document.getElementById('share-wa-id').value = id;
      document.getElementById('share-email-id').value = id;
      pilihShareTab('wa');
      document.getElementById('modal-share').classList.add('show');
    }

    function pilihShareTab(tab) {
      document.getElementById('form-share-wa').style.display = (tab === 'wa') ? 'block' : 'none';
      document.getElementById('form-share-email').style.display = (tab === 'email') ? 'block' : 'none';
      document.getElementById('tab-btn-wa').classList.toggle('active', tab === 'wa');
      document.getElementById('tab-btn-email').classList.toggle('active', tab === 'email');
    }

    function tutupModal(modalId) {
      document.getElementById(modalId).classList.remove('show');
    }

    document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
      overlay.addEventListener('click', function (e) {
        if (e.target === overlay) overlay.classList.remove('show');
      });
    });
  </script>
</body>
</html>
