<?php
/**
 * lupa_password.php
 * ---------------------------------------------------------
 * Reset password TANPA mengirim email asli, karena aplikasi
 * ini tidak punya SMTP otomatis yang terjamin jalan (lihat
 * catatan di kirim_email.php -- "kirim email" di sana cuma
 * membuka draft Gmail, bukan mengirim beneran dari server).
 *
 * Sebagai gantinya, identitas user diverifikasi lewat Email
 * saja, baru setelah itu boleh langsung mengganti password.
 * CATATAN KEAMANAN: siapa pun yang tahu email yang terdaftar
 * bisa langsung mengganti password akun tersebut (tidak ada
 * token/OTP). Cocok untuk demo/tugas, bukan untuk produksi.
 *
 * Alur:
 *  Langkah 1 (GET / POST step=verify) -> isi Email.
 *  Langkah 2 (POST step=reset)        -> isi password baru.
 * ---------------------------------------------------------
 */
session_start();
require_once __DIR__ . '/config/data.php';

$error  = '';
$step   = 2; // default: kalau sudah pernah terverifikasi di session, langsung ke langkah 2
$emailVerified = $_SESSION['reset_email'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['step'] ?? '') === 'verify') {
    $email = trim($_POST['email'] ?? '');

    $user = ($email !== '') ? findUserByEmail($email) : null;

    if ($user) {
        $_SESSION['reset_email'] = $user['email'];
        $emailVerified = $user['email'];
        $step = 2;
    } else {
        $error = 'Email tidak terdaftar. Periksa kembali data Anda.';
        $step  = 1;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['step'] ?? '') === 'reset') {
    if (!$emailVerified) {
        $error = 'Sesi verifikasi sudah habis. Silakan ulangi dari langkah 1.';
        $step  = 1;
    } else {
        $pass1 = $_POST['password'] ?? '';
        $pass2 = $_POST['password_confirm'] ?? '';

        if (strlen($pass1) < 6) {
            $error = 'Password baru minimal 6 karakter.';
            $step  = 2;
        } elseif ($pass1 !== $pass2) {
            $error = 'Konfirmasi password tidak sama.';
            $step  = 2;
        } else {
            updatePasswordByEmail($emailVerified, password_hash($pass1, PASSWORD_DEFAULT));
            unset($_SESSION['reset_email']);
            $_SESSION['login_success'] = 'Password berhasil diubah. Silakan login dengan password baru Anda.';
            header('Location: login.php');
            exit;
        }
    }
} else {
    // GET: tampilkan langkah 1 kalau belum ada sesi terverifikasi, kalau sudah -> langkah 2
    $step = $emailVerified ? 2 : 1;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Lupa Kata Sandi</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="login-wrap">
    <div class="login-box">
      <div class="login-form">

        <?php if ($step === 1): ?>
          <h2>Verifikasi Identitas</h2>
          <p class="hint">Masukkan email sesuai akun Anda.</p>

          <?php if ($error): ?>
            <div class="alert-error"><?= htmlspecialchars($error) ?></div>
          <?php endif; ?>

          <form action="lupa_password.php" method="POST">
            <input type="hidden" name="step" value="verify">
            <div class="field">
              <label for="email">Email</label>
              <input type="email" id="email" name="email" placeholder="nama@perusahaan.com" required>
            </div>
            <button type="submit" class="btn">Verifikasi</button>
          </form>

        <?php else: ?>
          <h2>Buat Password Baru</h2>
          <p class="hint">Identitas terverifikasi untuk <b><?= htmlspecialchars($emailVerified) ?></b>. Masukkan password baru Anda.</p>

          <?php if ($error): ?>
            <div class="alert-error"><?= htmlspecialchars($error) ?></div>
          <?php endif; ?>

          <form action="lupa_password.php" method="POST">
            <input type="hidden" name="step" value="reset">
            <div class="field">
              <label for="password">Password Baru</label>
              <input type="password" id="password" name="password" placeholder="Minimal 6 karakter" required>
            </div>
            <div class="field">
              <label for="password_confirm">Konfirmasi Password</label>
              <input type="password" id="password_confirm" name="password_confirm" placeholder="Ulangi password baru" required>
            </div>
            <button type="submit" class="btn">Simpan Password Baru</button>
          </form>
        <?php endif; ?>

        <p style="font-size:12px;color:#8896A6;margin-top:18px;">
          <a href="login.php" style="color:var(--teal);font-weight:600;text-decoration:none;">&larr; Kembali ke halaman login</a>
        </p>
      </div>
    </div>
  </div>
</body>
</html>
