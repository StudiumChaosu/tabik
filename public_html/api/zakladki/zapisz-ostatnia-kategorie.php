<?php
require_once __DIR__ . '/wspolne.php';

/* DANE - WEJSCIE */
$dane = dane_wejscia_api_z_csrf();
$idKat = int_lub_null($dane['id_kategorii'] ?? null);

/* ZAPIS - OSTATNIA KATEGORIA */
try {
    baza()->prepare('UPDATE uzytkownicy SET ostatnia_kategoria_id = :k WHERE id = :id')->execute([
        'k' => $idKat,
        'id' => id_uzytkownika(),
    ]);
    odswiez_sesje_uzytkownika(id_uzytkownika());
} catch (Throwable $e) {
    /* FALLBACK - BRAK KOLUMNY LUB INNY BLAD */
}

odpowiedz_json(['sukces' => true]);
