/**
 * script.js
 * ---------------------------------------------------------
 * 1. Menghitung Total Penghasilan, Total Potongan, dan Gaji
 *    Bersih secara live di sisi client (untuk kenyamanan user).
 *    Perhitungan FINAL tetap divalidasi ulang di server
 *    (cetak_slip.php) agar tidak bisa dimanipulasi.
 * 2. Format input angka otomatis menjadi format ribuan.
 * 3. Refresh captcha via AJAX tanpa reload halaman.
 * ---------------------------------------------------------
 */

document.addEventListener('DOMContentLoaded', function () {
  const gajiPokok = document.getElementById('gaji_pokok');
  const lembur = document.getElementById('lembur');
  const pinjaman = document.getElementById('pinjaman');

  const totalPenghasilanEl = document.getElementById('total_penghasilan');
  const totalPotonganEl = document.getElementById('total_potongan');
  const gajiBersihEl = document.getElementById('gaji_bersih');

  function parseAngka(str) {
    const bersih = (str || '').toString().replace(/[^0-9]/g, '');
    return bersih === '' ? 0 : parseInt(bersih, 10);
  }

  function formatRibuan(angka) {
    return angka.toLocaleString('id-ID');
  }

  function formatRupiah(angka) {
    return 'Rp ' + formatRibuan(angka);
  }

  function hitungGaji() {
    const pokok = parseAngka(gajiPokok.value);
    const lb = parseAngka(lembur.value);
    const pjm = parseAngka(pinjaman.value);

    const totalPenghasilan = pokok + lb;      // Total Penghasilan = Gaji Pokok + Lembur
    const totalPotongan = pjm;                // Total Potongan = Pinjaman Karyawan
    const gajiBersih = totalPenghasilan - totalPotongan; // Gaji Bersih = Penghasilan - Potongan

    if (totalPenghasilanEl) totalPenghasilanEl.textContent = formatRupiah(totalPenghasilan);
    if (totalPotonganEl) totalPotonganEl.textContent = formatRupiah(totalPotongan);
    if (gajiBersihEl) gajiBersihEl.textContent = formatRupiah(gajiBersih);
  }

  // Auto-format input angka saat diketik + trigger hitung ulang
  [gajiPokok, lembur, pinjaman].forEach(function (input) {
    if (!input) return;
    input.addEventListener('input', function () {
      const posisi = input.value.length;
      const angka = parseAngka(input.value);
      input.value = angka === 0 ? '' : formatRibuan(angka);
      hitungGaji();
    });
  });

  hitungGaji(); // hitung nilai awal saat halaman dimuat

  // ---------- Refresh Captcha via AJAX ----------
  const refreshBtn = document.getElementById('refresh-captcha');
  const captchaBox = document.getElementById('captcha-question');

  if (refreshBtn) {
    refreshBtn.addEventListener('click', function () {
      fetch('captcha.php')
        .then(function (res) { return res.json(); })
        .then(function (data) {
          captchaBox.textContent = data.question;
        })
        .catch(function () {
          console.error('Gagal memuat captcha baru.');
        });
    });
  }
});
