<?php
require_once __DIR__ . '/wspolne.php';
$dane = dane_wejscia_api(); sprawdz_csrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($dane['token_csrf'] ?? null));
$id = (int) ($dane['id'] ?? 0);
baza()->prepare('DELETE FROM zakladki WHERE id=:id AND id_uzytkownika=:u')->execute(['id'=>$id,'u'=>id_uzytkownika()]);
odpowiedz_json(['sukces'=>true,'komunikat'=>'Zakladka zostala usunieta.']);
