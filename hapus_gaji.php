<?php

require_once __DIR__ . '/config/auth_guard.php';
require_once __DIR__ . '/config/data.php';

$user = $_SESSION['user'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$id = (int) ($_POST['id'] ?? 0);
$ok = $id > 0 && deleteRiwayat($id, (int) $user['id']);

$_SESSION['dashboard_notif'] = [
    'ok'    => $ok,
    'pesan' => $ok ? 'Slip gaji berhasil dihapus.' : 'Gagal menghapus slip gaji. Data tidak ditemukan.',
];

header('Location: dashboard.php');
exit;
