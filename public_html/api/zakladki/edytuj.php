<?php
require_once __DIR__ . '/wspolne.php';
$dane = dane_wejscia_api_z_csrf();
$id = (int) ($dane['id'] ?? 0); $aktualna = znajdz_zakladke($id, id_uzytkownika());
if (!$aktualna) { odpowiedz_json(['sukces' => false, 'komunikat' => 'Nie znaleziono zakladki.'], 404); }
$tytul = trim((string) ($dane['tytul'] ?? ''));
$adres = uporzadkuj_url((string) ($dane['adres_url'] ?? ''));
if ($tytul === '' || $adres === '') { odpowiedz_json(['sukces' => false, 'komunikat' => 'Uzupelnij tytul i adres URL.'], 422); }
$idGrupy = int_lub_null($dane['id_grupy'] ?? null); $idKategorii = int_lub_null($dane['id_kategorii'] ?? null);
$kolejnosc = ((int) ($aktualna['id_grupy'] ?? 0) !== (int) ($idGrupy ?? 0)) ? nastepna_kolejnosc_grupy(id_uzytkownika(), $idGrupy) : (int) $aktualna['kolejnosc'];
$stmt = baza()->prepare('UPDATE zakladki SET id_grupy=:g,id_kategorii=:k,tytul=:t,adres_url=:a,opis=:o,czy_ulubiona=:c,kolejnosc=:kol,data_aktualizacji=NOW() WHERE id=:id AND id_uzytkownika=:u');
$stmt->execute(['g'=>$idGrupy,'k'=>$idKategorii,'t'=>$tytul,'a'=>$adres,'o'=>trim((string) ($dane['opis'] ?? '')),'c'=>!empty($dane['czy_ulubiona']) ? 1 : 0,'kol'=>$kolejnosc,'id'=>$id,'u'=>id_uzytkownika()]);
odpowiedz_json(['sukces' => true, 'komunikat' => 'Zakladka zostala zaktualizowana.']);
