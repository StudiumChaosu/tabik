<?php
require_once __DIR__ . '/../wspolne.php';
sprawdz_csrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['token_csrf'] ?? null)); $plik = pobierz_plik_importu('plik'); $zaw = file_get_contents($plik['tmp_name']) ?: ''; $json = json_decode($zaw, true);
if (!is_array($json)) odpowiedz_json(['sukces'=>false,'komunikat'=>'Niepoprawny plik JSON.'],422);
$zakladki = $json['zakladki'] ?? $json; if (!is_array($zakladki)) odpowiedz_json(['sukces'=>false,'komunikat'=>'Brak danych do importu.'],422);
$importowane = 0; $pominiete = 0; $ins = baza()->prepare('INSERT INTO zakladki (id_uzytkownika,id_grupy,id_kategorii,tytul,adres_url,opis,czy_ulubiona,kolejnosc) VALUES (:u,:g,:k,:t,:a,:o,:c,:kol)');
foreach ($zakladki as $z) {
    if (!is_array($z)) continue; $tytul = trim((string) ($z['tytul'] ?? $z['title'] ?? '')); $adres = uporzadkuj_url((string) ($z['adres_url'] ?? $z['url'] ?? '')); if ($tytul === '' || $adres === '') { $pominiete++; continue; }
    $sprawdz = baza()->prepare('SELECT id FROM zakladki WHERE id_uzytkownika = :u AND tytul = :t AND adres_url = :a LIMIT 1'); $sprawdz->execute(['u'=>id_uzytkownika(),'t'=>$tytul,'a'=>$adres]); if ($sprawdz->fetchColumn()) { $pominiete++; continue; }
    $idKat = znajdz_lub_utworz_kategorie(id_uzytkownika(), (string) ($z['kategoria'] ?? ''));
    $idGrupy = znajdz_lub_utworz_grupe(id_uzytkownika(), (string) ($z['grupa'] ?? ''), $idKat);
    $ins->execute(['u'=>id_uzytkownika(),'g'=>$idGrupy,'k'=>$idKat,'t'=>$tytul,'a'=>$adres,'o'=>trim((string) ($z['opis'] ?? '')),'c'=>(int) ($z['czy_ulubiona'] ?? 0),'kol'=>nastepna_kolejnosc_grupy(id_uzytkownika(), $idGrupy)]); $importowane++;
}
odpowiedz_json(['sukces'=>true,'komunikat'=>'Import zakonczony.','importowane'=>$importowane,'pominiete'=>$pominiete]);
