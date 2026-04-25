<?php
require_once __DIR__ . '/wspolne.php';

try {
    $dane = pobierz_dane_zakladek(id_uzytkownika(), $_GET, uzytkownik());
    odpowiedz_json(['sukces' => true, 'dane' => $dane]);
} catch (Throwable $e) {
    odpowiedz_json([
        'sukces' => false,
        'komunikat' => 'Nie udalo sie pobrac listy zakladek.',
        'szczegoly' => $e->getMessage(),
    ], 500);
}
