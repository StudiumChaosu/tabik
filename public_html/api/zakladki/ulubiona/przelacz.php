<?php
require_once __DIR__ . '/../wspolne.php';
$dane = dane_wejscia_api(); sprawdz_csrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($dane['token_csrf'] ?? null));
$id = (int) ($dane['id'] ?? 0); $zakladka = znajdz_zakladke($id, id_uzytkownika()); if (!$zakladka) { odpowiedz_json(['sukces'=>false,'komunikat'=>'Nie znaleziono zakladki.'],404);} $nowa = (int) !(bool) $zakladka['czy_ulubiona'];
baza()->prepare('UPDATE zakladki SET czy_ulubiona = :c, data_aktualizacji = NOW() WHERE id = :id AND id_uzytkownika = :u')->execute(['c'=>$nowa,'id'=>$id,'u'=>id_uzytkownika()]);
odpowiedz_json(['sukces'=>true,'komunikat'=>$nowa ? 'Zakladka trafila do ulubionych.' : 'Zakladka zostala usunieta z ulubionych.','czy_ulubiona'=>$nowa]);
