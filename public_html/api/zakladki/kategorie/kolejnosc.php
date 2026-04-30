<?php
require_once __DIR__ . '/../wspolne.php';

$dane = dane_wejscia_api_z_csrf();
$ids = array_map('intval', (array) ($dane['ids'] ?? []));
ustaw_kolejnosc_rekordow('kategorie', id_uzytkownika(), $ids);

odpowiedz_json(['sukces' => true, 'komunikat' => 'Kolejnosc kategorii zostala zapisana.']);
