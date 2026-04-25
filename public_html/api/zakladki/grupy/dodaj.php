<?php
require_once __DIR__ . '/../wspolne.php';
$dane = dane_wejscia_api(); sprawdz_csrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($dane['token_csrf'] ?? null)); $nazwa = trim((string) ($dane['nazwa'] ?? '')); if (mb_strlen($nazwa) < 2) odpowiedz_json(['sukces'=>false,'komunikat'=>'Podaj nazwe grupy.'],422);
$idKategorii = int_lub_null($dane['id_kategorii'] ?? null);
$id = znajdz_lub_utworz_grupe(id_uzytkownika(), $nazwa, $idKategorii); odpowiedz_json(['sukces'=>true,'komunikat'=>'Grupa zostala dodana.','id'=>$id],201);
