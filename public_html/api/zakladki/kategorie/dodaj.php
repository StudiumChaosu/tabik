<?php
require_once __DIR__ . '/../wspolne.php';
$dane = dane_wejscia_api(); sprawdz_csrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($dane['token_csrf'] ?? null)); $nazwa = trim((string) ($dane['nazwa'] ?? '')); if (mb_strlen($nazwa) < 2) odpowiedz_json(['sukces'=>false,'komunikat'=>'Podaj nazwe kategorii.'],422);
$id = znajdz_lub_utworz_kategorie(id_uzytkownika(), $nazwa); odpowiedz_json(['sukces'=>true,'komunikat'=>'Kategoria zostala dodana.','id'=>$id],201);
