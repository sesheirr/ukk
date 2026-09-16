<?php

session_start();
require_once __DIR__ . '/config/data.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$email    = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($email === '' || $password === '') {
    $_SESSION['login_error'] = 'Email dan sandi wajib diisi.';
    header('Location: login.php');
    exit;
}

$user = findUser($email, $password);

if ($user === null) {
    $_SESSION['login_error'] = 'Email atau sandi salah. Silakan coba lagi.';
    header('Location: login.php');
    exit;
}

$_SESSION['is_login'] = true;
$_SESSION['user']     = $user;

header('Location: dashboard.php');
exit;
