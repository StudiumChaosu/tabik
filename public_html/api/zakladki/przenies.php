<?php
require_once __DIR__ . '/wspolne.php';
$dane = dane_wejscia_api(); sprawdz_csrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($dane['token_csrf'] ?? null));
$id = (int) ($dane['id'] ?? 0); $idDoc = int_lub_null($dane['id_grupy_docelowej'] ?? null); $idZrod = int_lub_null($dane['id_grupy_zrodlowej'] ?? null);
baza()->prepare('UPDATE zakladki SET id_grupy = :g, data_aktualizacji = NOW() WHERE id = :id AND id_uzytkownika = :u')->execute(['g'=>$idDoc,'id'=>$id,'u'=>id_uzytkownika()]);
ustaw_kolejnosc_grupy(id_uzytkownika(), $idDoc, array_map('intval', (array) ($dane['kolejnosc_docelowa'] ?? [])));
if ($idZrod !== $idDoc) { ustaw_kolejnosc_grupy(id_uzytkownika(), $idZrod, array_map('intval', (array) ($dane['kolejnosc_zrodlowa'] ?? []))); }
odpowiedz_json(['sukces'=>true,'komunikat'=>'Kolejnosc zakladek zostala zapisana.']);
