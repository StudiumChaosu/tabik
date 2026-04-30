<?php
require_once __DIR__ . '/../wspolne.php';

sprawdz_csrf_api($_POST);
$plik = pobierz_plik_importu('plik');
$zawartosc = file_get_contents($plik['tmp_name']) ?: '';
$json = json_decode($zawartosc, true);

if (!is_array($json)) {
    odpowiedz_json(['sukces' => false, 'komunikat' => 'Niepoprawny plik JSON.'], 422);
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
