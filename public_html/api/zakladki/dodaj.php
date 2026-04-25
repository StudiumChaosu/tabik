<?php
require_once __DIR__ . '/wspolne.php';
$dane = dane_wejscia_api();
sprawdz_csrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($dane['token_csrf'] ?? null));
$tytul = trim((string) ($dane['tytul'] ?? ''));
$adres = uporzadkuj_url((string) ($dane['adres_url'] ?? ''));
if ($tytul === '' || $adres === '') { odpowiedz_json(['sukces' => false, 'komunikat' => 'Uzupelnij tytul i adres URL.'], 422); }
$idGrupy = int_lub_null($dane['id_grupy'] ?? null); $idKategorii = int_lub_null($dane['id_kategorii'] ?? null);
$stmt = baza()->prepare('INSERT INTO zakladki (id_uzytkownika,id_grupy,id_kategorii,tytul,adres_url,opis,czy_ulubiona,kolejnosc) VALUES (:u,:g,:k,:t,:a,:o,:c,:kol)');
$stmt->execute(['u'=>id_uzytkownika(),'g'=>$idGrupy,'k'=>$idKategorii,'t'=>$tytul,'a'=>$adres,'o'=>trim((string) ($dane['opis'] ?? '')),'c'=>!empty($dane['czy_ulubiona']) ? 1 : 0,'kol'=>nastepna_kolejnosc_grupy(id_uzytkownika(), $idGrupy)]);
odpowiedz_json(['sukces' => true, 'komunikat' => 'Zakladka zostala dodana.', 'id' => (int) baza()->lastInsertId()], 201);
