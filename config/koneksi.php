<?php

const DB_HOST = 'localhost';
const DB_NAME = 'sigap_payroll';
const DB_USER = 'root';
const DB_PASS = '';

function getKoneksi(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            die('Koneksi database gagal: ' . $e->getMessage() .
                '<br>Pastikan Anda sudah membuat database dengan mengimpor file database/db_slipgaji.sql');
        }
    }

    return $pdo;
}
