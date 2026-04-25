<?php
require_once __DIR__ . '/../wspolne.php';
$dane = dane_wejscia_api(); sprawdz_csrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($dane['token_csrf'] ?? null)); $id = (int) ($dane['id'] ?? 0); $nazwa = trim((string) ($dane['nazwa'] ?? '')); if ($id <= 0 || mb_strlen($nazwa) < 2) odpowiedz_json(['sukces'=>false,'komunikat'=>'Nieprawidlowe dane grupy.'],422);
baza()->prepare('UPDATE grupy_zakladek SET nazwa = :n, data_aktualizacji = NOW() WHERE id = :id AND id_uzytkownika = :u')->execute(['n'=>$nazwa,'id'=>$id,'u'=>id_uzytkownika()]); odpowiedz_json(['sukces'=>true,'komunikat'=>'Nazwa grupy zostala zmieniona.']);
