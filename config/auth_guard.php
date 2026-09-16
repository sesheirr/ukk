<?php
/**
 * auth_guard.php
 * ---------------------------------------------------------
 * Komponen reusable (pre-existing component) untuk melindungi
 * halaman yang butuh login. Cukup di-require di baris paling
 * atas setiap halaman yang membutuhkan proteksi.
 * ---------------------------------------------------------
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['is_login']) || $_SESSION['is_login'] !== true) {
    header('Location: login.php');
    exit;
}
