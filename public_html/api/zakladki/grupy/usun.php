<?php
require_once __DIR__ . '/../wspolne.php';
$dane = dane_wejscia_api_z_csrf(); $id = (int) ($dane['id'] ?? 0);
baza()->prepare('UPDATE zakladki SET id_grupy = NULL WHERE id_grupy = :id AND id_uzytkownika = :u')->execute(['id'=>$id,'u'=>id_uzytkownika()]);
baza()->prepare('DELETE FROM grupy_zakladek WHERE id = :id AND id_uzytkownika = :u')->execute(['id'=>$id,'u'=>id_uzytkownika()]); odpowiedz_json(['sukces'=>true,'komunikat'=>'Grupa zostala usunieta.']);
