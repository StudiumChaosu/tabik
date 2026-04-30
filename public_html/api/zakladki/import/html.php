<?php
require_once __DIR__ . '/../wspolne.php';

sprawdz_csrf_api($_POST);
$plik = pobierz_plik_importu('plik');
$zawartosc = file_get_contents($plik['tmp_name']) ?: '';

if (!preg_match('/<script[^>]+id="dane-zakladek"[^>]*>(.*?)<\/script>/si', $zawartosc, $m)) {
    odpowiedz_json(['sukces' => false, 'komunikat' => 'Plik HTML nie zawiera pakietu danych.'], 422);
}

$json = json_decode(html_entity_decode(trim($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'), true);
if (!is_array($json)) {
    odpowiedz_json(['sukces' => false, 'komunikat' => 'Niepoprawny pakiet danych w pliku HTML.'], 422);
}

$zakladki = $json['zakladki'] ?? $json;
if (!is_array($zakladki)) {
    odpowiedz_json(['sukces' => false, 'komunikat' => 'Brak danych do importu.'], 422);
}

$wynik = importuj_zakladki_z_tablicy($zakladki, id_uzytkownika());
odpowiedz_json([
    'sukces' => true,
    'komunikat' => 'Import zakonczony.',
    'importowane' => $wynik['importowane'],
    'pominiete' => $wynik['pominiete'],
]);
