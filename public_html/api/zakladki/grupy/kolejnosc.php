<?php
require_once __DIR__ . '/../wspolne.php';
$dane = dane_wejscia_api(); sprawdz_csrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($dane['token_csrf'] ?? null)); $ids = array_map('intval', (array) ($dane['ids'] ?? []));
$stmt = baza()->prepare('UPDATE grupy_zakladek SET kolejnosc = :k WHERE id = :id AND id_uzytkownika = :u'); foreach (array_values($ids) as $kolejnosc => $id) { $stmt->execute(['k'=>$kolejnosc,'id'=>$id,'u'=>id_uzytkownika()]); }
odpowiedz_json(['sukces'=>true,'komunikat'=>'Kolejnosc grup zostala zapisana.']);
