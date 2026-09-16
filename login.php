<?php

session_start();
require_once __DIR__ . '/config/data.php';

if (!empty($_SESSION['is_login'])) {
    header('Location: dashboard.php');
    exit;
}

$error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="login-wrap">
    <div class="login-box">
      <div class="login-form">
        <h2>Masuk</h2>
        <p class="hint">Gunakan akun yang telah terdaftar.</p>

        <?php if ($error): ?>
          <div class="alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="proses_login.php" method="POST">
          <div class="field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="nama@perusahaan.com" required>
          </div>
          <div class="field">
            <label for="password">Sandi</label>
            <input type="password" id="password" name="password" placeholder="••••••••" required>
          </div>
          <p style="text-align:right;margin:-8px 0 16px;">
            <a href="lupa_password.php" style="font-size:12px;color:var(--teal);font-weight:600;text-decoration:none;">Lupa kata sandi?</a>
          </p>
          <button type="submit" class="btn">Login</button>
        </form>

        <p style="font-size:11.5px;color:#8896A6;margin-top:18px;">
          Demo akun: <b>admin@sigap.com</b> / <b>admin123</b>
        </p>
      </div>
    </div>
  </div>
</body>
</html>