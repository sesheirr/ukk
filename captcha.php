<?php
/**
 * captcha.php
 * ---------------------------------------------------------
 * Endpoint AJAX (dipanggil dari assets/js/script.js) untuk
 * membuat soal captcha matematika sederhana secara acak.
 * Jawaban disimpan di session agar bisa divalidasi di server
 * saat form disubmit (tidak bisa dicurangi lewat DevTools).
 * ---------------------------------------------------------
 */
session_start();
header('Content-Type: application/json');

$operators = ['×'];
$op  = $operators[array_rand($operators)];
$a   = rand(1, 10);
$b   = rand(1, 10);

switch ($op) {
    case '+':
        $hasil = $a + $b;
        break;
    case '-':
        // pastikan hasil tidak negatif
        if ($a < $b) { [$a, $b] = [$b, $a]; }
        $hasil = $a - $b;
        break;
    case '×':
        $hasil = $a * $b;
        break;
}

$_SESSION['captcha_answer'] = $hasil;

echo json_encode([
    'question' => "$a $op $b",
]);
exit;
