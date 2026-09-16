<?php
/**
 * data.php
 * ---------------------------------------------------------
 * Fungsi-fungsi akses data aplikasi. Berbeda dari versi
 * sebelumnya, di sini data DIAMBIL DARI DATABASE MySQL
 * (lewat PDO di koneksi.php), bukan array statis.
 * ---------------------------------------------------------
 */

require_once __DIR__ . '/koneksi.php';

/**
 * Mencari user berdasarkan email, lalu mencocokkan password
 * dengan hash yang tersimpan di database (password_verify).
 */
function findUser(string $email, string $password): ?array
{
    $pdo  = getKoneksi();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        unset($user['password']); // jangan simpan hash password di session
        return $user;
    }

    return null;
}

/**
 * Menyimpan hasil perhitungan slip gaji ke tabel riwayat_gaji.
 * Mengembalikan id baris yang baru dibuat, supaya bisa langsung
 * dipakai untuk aksi lain (cetak/kirim email/kirim WhatsApp)
 * tanpa bergantung pada session.
 */
function saveRiwayat(array $data): int
{
    $pdo = getKoneksi();
    $sql = 'INSERT INTO riwayat_gaji
              (user_id, nama, nik, jabatan, gaji_pokok, lembur, pinjaman_karyawan,
               total_penghasilan, total_potongan, gaji_bersih, periode_awal, periode_akhir)
            VALUES
              (:user_id, :nama, :nik, :jabatan, :gaji_pokok, :lembur, :pinjaman_karyawan,
               :total_penghasilan, :total_potongan, :gaji_bersih, :periode_awal, :periode_akhir)';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'user_id'           => $data['user_id'],
        'nama'              => $data['nama'],
        'nik'               => $data['nik'],
        'jabatan'           => $data['jabatan'],
        'gaji_pokok'        => $data['gajiPokok'],
        'lembur'            => $data['lembur'],
        'pinjaman_karyawan' => $data['pinjaman'],
        'total_penghasilan' => $data['totalPenghasilan'],
        'total_potongan'    => $data['totalPotongan'],
        'gaji_bersih'       => $data['gajiBersih'],
        'periode_awal'      => $data['periodeAwal'],
        'periode_akhir'     => $data['periodeAkhir'],
    ]);

    return (int) $pdo->lastInsertId();
}

/**
 * Mengambil riwayat slip gaji milik satu user, terbaru dulu.
 * $dari / $sampai (format Y-m-d) opsional untuk memfilter
 * berdasarkan periode_awal slip (dipakai oleh filter periode
 * di dashboard.php).
 */
function getRiwayat(int $userId, ?string $dari = null, ?string $sampai = null): array
{
    $pdo = getKoneksi();
    $sql = 'SELECT * FROM riwayat_gaji WHERE user_id = :user_id';
    $params = ['user_id' => $userId];

    if ($dari !== null && $dari !== '') {
        $sql .= ' AND periode_awal >= :dari';
        $params['dari'] = $dari;
    }
    if ($sampai !== null && $sampai !== '') {
        $sql .= ' AND periode_akhir <= :sampai';
        $params['sampai'] = $sampai;
    }

    $sql .= ' ORDER BY created_at DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Mengambil SATU baris riwayat_gaji berdasarkan id, sekaligus
 * memastikan baris itu milik user yang sedang login (user_id
 * cocok). Dipakai oleh cetak_slip.php, kirim_email.php, dan
 * kirim_wa.php saat memproses aksi dari tabel dashboard.
 * Mengembalikan null jika tidak ditemukan / bukan milik user ini,
 * supaya user tidak bisa mengakses slip milik orang lain hanya
 * dengan menebak id di URL (IDOR).
 */
function getRiwayatById(int $id, int $userId): ?array
{
    $pdo  = getKoneksi();
    $stmt = $pdo->prepare('SELECT * FROM riwayat_gaji WHERE id = :id AND user_id = :user_id LIMIT 1');
    $stmt->execute(['id' => $id, 'user_id' => $userId]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

/**
 * Menghapus satu baris riwayat_gaji berdasarkan id, sekaligus
 * memastikan baris itu milik user yang sedang login (user_id
 * cocok), supaya user tidak bisa menghapus slip milik orang lain.
 * Mengembalikan true jika ada baris yang terhapus.
 */
function deleteRiwayat(int $id, int $userId): bool
{
    $pdo  = getKoneksi();
    $stmt = $pdo->prepare('DELETE FROM riwayat_gaji WHERE id = :id AND user_id = :user_id');
    $stmt->execute(['id' => $id, 'user_id' => $userId]);
    return $stmt->rowCount() > 0;
}

/**
 * Memperbarui satu baris riwayat_gaji (dipakai oleh edit_gaji.php).
 * Sama seperti deleteRiwayat, dibatasi hanya pada baris milik
 * user yang sedang login.
 */
function updateRiwayat(int $id, int $userId, array $data): bool
{
    $pdo = getKoneksi();
    $sql = 'UPDATE riwayat_gaji SET
              nama = :nama, nik = :nik, jabatan = :jabatan,
              gaji_pokok = :gaji_pokok, lembur = :lembur, pinjaman_karyawan = :pinjaman_karyawan,
              total_penghasilan = :total_penghasilan, total_potongan = :total_potongan, gaji_bersih = :gaji_bersih,
              periode_awal = :periode_awal, periode_akhir = :periode_akhir
            WHERE id = :id AND user_id = :user_id';

    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        'nama'              => $data['nama'],
        'nik'               => $data['nik'],
        'jabatan'           => $data['jabatan'],
        'gaji_pokok'        => $data['gajiPokok'],
        'lembur'            => $data['lembur'],
        'pinjaman_karyawan' => $data['pinjaman'],
        'total_penghasilan' => $data['totalPenghasilan'],
        'total_potongan'    => $data['totalPotongan'],
        'gaji_bersih'       => $data['gajiBersih'],
        'periode_awal'      => $data['periodeAwal'],
        'periode_akhir'     => $data['periodeAkhir'],
        'id'                => $id,
        'user_id'           => $userId,
    ]);
}

/**
 * Format angka menjadi format Rupiah.
 */
function rupiah(float $angka): string
{
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

/**
 * Membersihkan input angka dari format ribuan ("4.500.000" -> 4500000)
 */
function parseAngka(string $str): float
{
    $bersih = preg_replace('/[^0-9]/', '', $str);
    return $bersih === '' ? 0 : (float) $bersih;
}

/**
 * Format tanggal ("2025-11-25") menjadi "25 November 2025" dengan
 * NAMA BULAN Indonesia (bukan angka), dipakai di semua tampilan
 * periode (dashboard, cetak, email, WhatsApp, PDF).
 */
function formatTanggalIndo(string $tanggal): string
{
    static $bulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    $ts = strtotime($tanggal);
    if ($ts === false) {
        return $tanggal;
    }

    return date('d', $ts) . ' ' . $bulan[(int) date('n', $ts)] . ' ' . date('Y', $ts);
}

/**
 * Mengambil daftar "karyawan" milik satu user. Aplikasi ini tidak
 * punya tabel karyawan tersendiri -- data karyawan diambil dari
 * baris riwayat_gaji yang sudah pernah diinput (dikelompokkan per
 * NIK, diambil data TERBARU-nya). Dipakai untuk mengisi checklist
 * karyawan saat Tambah/Update Periode di dashboard.php.
 */
function getDaftarKaryawan(int $userId): array
{
    $pdo  = getKoneksi();
    $sql  = 'SELECT r.id AS riwayat_id, r.nama, r.nik, r.jabatan, r.gaji_pokok
             FROM riwayat_gaji r
             INNER JOIN (
               SELECT nik, MAX(created_at) AS terbaru
               FROM riwayat_gaji
               WHERE user_id = :user_id
               GROUP BY nik
             ) t ON t.nik = r.nik AND t.terbaru = r.created_at
             WHERE r.user_id = :user_id
             GROUP BY r.nik
             ORDER BY r.nama ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetchAll();
}

/**
 * Mengambil semua periode gaji milik satu user, lengkap dengan
 * daftar NIK karyawan yang terdaftar di masing-masing periode
 * (dipakai untuk mengisi otomatis form saat mode "Update Periode").
 */
function getPeriodeList(int $userId): array
{
    $pdo  = getKoneksi();
    $stmt = $pdo->prepare('SELECT * FROM periode_gaji WHERE user_id = :user_id ORDER BY periode_awal DESC, id DESC');
    $stmt->execute(['user_id' => $userId]);
    $periodeList = $stmt->fetchAll();

    $stmtK = $pdo->prepare('SELECT nik FROM periode_karyawan WHERE periode_id = :periode_id');
    foreach ($periodeList as &$p) {
        $stmtK->execute(['periode_id' => $p['id']]);
        $p['nik_list'] = array_column($stmtK->fetchAll(), 'nik');
    }
    unset($p);

    return $periodeList;
}

/**
 * Membuat periode gaji baru beserta daftar karyawan yang dipilih.
 * $karyawanList adalah array asosiatif ['nama','nik','jabatan','gaji_pokok','riwayat_id']
 * (hasil pencocokan NIK terpilih dengan getDaftarKaryawan(), lihat
 * tambah_periode.php) supaya data yang tersimpan tetap berasal dari
 * data karyawan yang sah milik user ini.
 *
 * Slip gaji terakhir (riwayat_gaji) milik tiap karyawan yang dipilih
 * ikut di-UPDATE periode_awal/periode_akhir-nya supaya kolom
 * "Periode" di tabel dashboard.php selalu sinkron dengan periode
 * yang diatur di sini (bukan cuma tersimpan terpisah di periode_gaji).
 */
function createPeriode(int $userId, string $periodeAwal, string $periodeAkhir, array $karyawanList): int
{
    $pdo = getKoneksi();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('INSERT INTO periode_gaji (user_id, periode_awal, periode_akhir) VALUES (:user_id, :awal, :akhir)');
        $stmt->execute(['user_id' => $userId, 'awal' => $periodeAwal, 'akhir' => $periodeAkhir]);
        $periodeId = (int) $pdo->lastInsertId();

        $stmtK = $pdo->prepare('INSERT INTO periode_karyawan (periode_id, nama, nik, jabatan, gaji_pokok, riwayat_id)
                                 VALUES (:periode_id, :nama, :nik, :jabatan, :gaji_pokok, :riwayat_id)');
        $stmtR = $pdo->prepare('UPDATE riwayat_gaji SET periode_awal = :awal, periode_akhir = :akhir
                                 WHERE id = :riwayat_id AND user_id = :user_id');
        foreach ($karyawanList as $k) {
            $riwayatId = $k['riwayat_id'] ?? null;

            $stmtK->execute([
                'periode_id' => $periodeId,
                'nama'       => $k['nama'],
                'nik'        => $k['nik'],
                'jabatan'    => $k['jabatan'],
                'gaji_pokok' => $k['gaji_pokok'],
                'riwayat_id' => $riwayatId,
            ]);

            if ($riwayatId !== null) {
                $stmtR->execute([
                    'awal'       => $periodeAwal,
                    'akhir'      => $periodeAkhir,
                    'riwayat_id' => $riwayatId,
                    'user_id'    => $userId,
                ]);
            }
        }

        $pdo->commit();
        return $periodeId;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Memperbarui rentang tanggal & daftar karyawan sebuah periode.
 * Karyawan yang NIK-nya masih dipilih akan dipertahankan baris
 * periode_karyawan-nya (supaya tautan riwayat_id yang sudah ada
 * tidak hilang), yang tidak dipilih lagi akan dihapus, dan yang
 * baru dipilih akan ditambahkan. Mengembalikan false jika periode
 * tidak ditemukan / bukan milik user ini.
 *
 * Slip gaji terakhir (riwayat_gaji) milik SETIAP karyawan yang
 * masih/baru dipilih ikut di-UPDATE periode_awal/periode_akhir-nya
 * supaya kolom "Periode" di tabel dashboard.php selalu sinkron
 * dengan perubahan tanggal periode yang dilakukan di sini.
 */
function updatePeriode(int $periodeId, int $userId, string $periodeAwal, string $periodeAkhir, array $karyawanList): bool
{
    $pdo = getKoneksi();

    $cek = $pdo->prepare('SELECT id FROM periode_gaji WHERE id = :id AND user_id = :user_id LIMIT 1');
    $cek->execute(['id' => $periodeId, 'user_id' => $userId]);
    if (!$cek->fetch()) {
        return false;
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('UPDATE periode_gaji SET periode_awal = :awal, periode_akhir = :akhir WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['awal' => $periodeAwal, 'akhir' => $periodeAkhir, 'id' => $periodeId, 'user_id' => $userId]);

        $nikBaru = array_column($karyawanList, 'nik');

        // Hapus karyawan yang sudah tidak dicentang lagi
        $stmtLama = $pdo->prepare('SELECT nik FROM periode_karyawan WHERE periode_id = :periode_id');
        $stmtLama->execute(['periode_id' => $periodeId]);
        $nikLama = array_column($stmtLama->fetchAll(), 'nik');

        $nikDihapus = array_diff($nikLama, $nikBaru);
        if (!empty($nikDihapus)) {
            $placeholders = implode(',', array_fill(0, count($nikDihapus), '?'));
            $stmtHapus = $pdo->prepare("DELETE FROM periode_karyawan WHERE periode_id = ? AND nik IN ($placeholders)");
            $stmtHapus->execute(array_merge([$periodeId], array_values($nikDihapus)));
        }

        // Tambahkan karyawan yang baru dicentang (yang sudah ada dibiarkan, tidak diduplikasi)
        $nikDitambah = array_diff($nikBaru, $nikLama);
        if (!empty($nikDitambah)) {
            $stmtTambah = $pdo->prepare('INSERT INTO periode_karyawan (periode_id, nama, nik, jabatan, gaji_pokok, riwayat_id)
                                          VALUES (:periode_id, :nama, :nik, :jabatan, :gaji_pokok, :riwayat_id)');
            foreach ($karyawanList as $k) {
                if (in_array($k['nik'], $nikDitambah, true)) {
                    $stmtTambah->execute([
                        'periode_id' => $periodeId,
                        'nama'       => $k['nama'],
                        'nik'        => $k['nik'],
                        'jabatan'    => $k['jabatan'],
                        'gaji_pokok' => $k['gaji_pokok'],
                        'riwayat_id' => $k['riwayat_id'] ?? null,
                    ]);
                }
            }
        }

        // Sinkronkan periode_awal/periode_akhir ke slip gaji (riwayat_gaji)
        // setiap karyawan yang MASIH/BARU dipilih di periode ini.
        $stmtR = $pdo->prepare('UPDATE riwayat_gaji SET periode_awal = :awal, periode_akhir = :akhir
                                 WHERE id = :riwayat_id AND user_id = :user_id');
        foreach ($karyawanList as $k) {
            $riwayatId = $k['riwayat_id'] ?? null;
            if ($riwayatId !== null) {
                $stmtR->execute([
                    'awal'       => $periodeAwal,
                    'akhir'      => $periodeAkhir,
                    'riwayat_id' => $riwayatId,
                    'user_id'    => $userId,
                ]);
            }
        }

        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Mencari user berdasarkan email saja, dipakai untuk memverifikasi
 * identitas di halaman "Lupa Kata Sandi" versi Email-only (tanpa
 * NIK). CATATAN: karena tidak ada pengiriman token email asli,
 * siapa pun yang tahu email terdaftar bisa langsung mengganti
 * password akun tersebut -- pilihan ini dipakai atas permintaan
 * eksplisit agar alurnya lebih singkat.
 */
function findUserByEmail(string $email): ?array
{
    $pdo  = getKoneksi();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();
    return $user === false ? null : $user;
}

/**
 * Mencari user berdasarkan kombinasi email + NIK sekaligus, dipakai
 * untuk memverifikasi identitas di halaman "Lupa Kata Sandi" (tanpa
 * perlu mengirim email token asli, karena aplikasi ini tidak punya
 * SMTP otomatis yang terjamin jalan -- lihat catatan di kirim_email.php).
 */
function findUserByEmailNik(string $email, string $nik): ?array
{
    $pdo  = getKoneksi();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email AND nik = :nik LIMIT 1');
    $stmt->execute(['email' => $email, 'nik' => $nik]);
    $user = $stmt->fetch();
    return $user === false ? null : $user;
}

/**
 * Mengganti password user (dipakai setelah identitas terverifikasi
 * di halaman "Lupa Kata Sandi"). $hash harus sudah di-hash lewat
 * password_hash(), jangan pernah simpan password polos.
 */
function updatePasswordByEmail(string $email, string $hash): bool
{
    $pdo  = getKoneksi();
    $stmt = $pdo->prepare('UPDATE users SET password = :password WHERE email = :email');
    return $stmt->execute(['password' => $hash, 'email' => $email]);
}