<?php
/*
 * Bootstrap Tabik: konfiguracja, sesja, polaczenie PDO i pomocnicze sprawdzanie schematu bazy.
 */

/* START - SESJA I KONFIGURACJA */
$konfiguracja = require __DIR__ . '/../../config/baza.php';
date_default_timezone_set($konfiguracja['strefa_czasowa'] ?? 'Europe/Warsaw');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name($konfiguracja['nazwa_sesji'] ?? 'tabik_sesja');
    session_start();
}

/* START - BAZA DANYCH */
function baza(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $cfg = require __DIR__ . '/../../config/baza.php';
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $cfg['host'], $cfg['port'], $cfg['nazwa'], $cfg['kodowanie']);
    $pdo = new PDO($dsn, $cfg['uzytkownik'], $cfg['haslo'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}

/* BAZA DANYCH - sprawdzenie istnienia kolumny z cache w ramach requestu. */
function czy_kolumna_istnieje(string $tabela, string $kolumna): bool
{
    static $cache = [];
    $klucz = $tabela . '.' . $kolumna;
    if (array_key_exists($klucz, $cache)) {
        return $cache[$klucz];
    }

    $stmt = baza()->prepare(
        'SELECT COUNT(*)
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :tabela AND COLUMN_NAME = :kolumna'
    );
    $stmt->execute(['tabela' => $tabela, 'kolumna' => $kolumna]);

    return $cache[$klucz] = ((int) $stmt->fetchColumn()) > 0;
}
