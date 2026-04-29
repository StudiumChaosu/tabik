<?php
require_once __DIR__ . '/baza.php';
wymagaj_logowania_api();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    odpowiedz_json(['sukces' => false, 'komunikat' => 'Bledne zadanie.'], 405);
}

$dane = str_contains(strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? '')), 'application/json')
    ? pobierz_json_wejscia()
    : $_POST;

sprawdz_csrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($dane['token_csrf'] ?? null));
ensure_uzytkownicy_profil_columns();

$dozwoloneObszary = ['idkolor_zak', 'idkolor_gru', 'idkolor_prom'];
$obszar = (string) ($dane['obszar'] ?? '');
$kolor = strtolower(trim((string) ($dane['kolor'] ?? $dane['colorHex'] ?? '')));

if (!in_array($obszar, $dozwoloneObszary, true)) {
    odpowiedz_json(['sukces' => false, 'komunikat' => 'Nieznany obszar koloru.'], 422);
}

if (!preg_match('/^#[0-9a-f]{6}$/', $kolor)) {
    odpowiedz_json(['sukces' => false, 'komunikat' => 'Nieprawidlowy format koloru.'], 422);
}

$stmt = baza()->prepare("UPDATE uzytkownicy SET {$obszar} = :kolor, data_aktualizacji = NOW() WHERE id = :id");
$stmt->execute([
    'kolor' => $kolor,
    'id' => id_uzytkownika(),
]);

odswiez_sesje_uzytkownika(id_uzytkownika());

odpowiedz_json([
    'sukces' => true,
    'komunikat' => 'Kolor zostal zapisany.',
    'obszar' => $obszar,
    'kolor' => $kolor,
]);
