<?php
require_once __DIR__ . '/../wspolne.php';
$dane = dane_wejscia_api_z_csrf(); $id = (int) ($dane['id'] ?? 0);
$stmt = baza()->prepare('SELECT * FROM grupy_zakladek WHERE id = :id AND id_uzytkownika = :u LIMIT 1'); $stmt->execute(['id'=>$id,'u'=>id_uzytkownika()]); $grupa = $stmt->fetch(); if (!$grupa) odpowiedz_json(['sukces'=>false,'komunikat'=>'Nie znaleziono grupy.'],404);
$nowaNazwa = $grupa['nazwa'] . ' kopia'; $noweId = znajdz_lub_utworz_grupe(id_uzytkownika(), $nowaNazwa);
$stmt = baza()->prepare('SELECT * FROM zakladki WHERE id_uzytkownika = :u AND id_grupy = :g ORDER BY kolejnosc ASC, id ASC'); $stmt->execute(['u'=>id_uzytkownika(),'g'=>$id]); $zakladki = $stmt->fetchAll() ?: [];
$ins = baza()->prepare('INSERT INTO zakladki (id_uzytkownika,id_grupy,id_kategorii,tytul,adres_url,opis,czy_ulubiona,kolejnosc) VALUES (:u,:g,:k,:t,:a,:o,:c,:kol)');
foreach ($zakladki as $i => $z) { $ins->execute(['u'=>id_uzytkownika(),'g'=>$noweId,'k'=>$z['id_kategorii'],'t'=>$z['tytul'],'a'=>$z['adres_url'],'o'=>$z['opis'],'c'=>$z['czy_ulubiona'],'kol'=>$i]); }
odpowiedz_json(['sukces'=>true,'komunikat'=>'Grupa zostala zduplikowana.','id'=>$noweId]);
