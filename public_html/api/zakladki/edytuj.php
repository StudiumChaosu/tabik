<?php
require_once __DIR__ . '/wspolne.php';

$dane = dane_wejscia_api_z_csrf();
$idUzytkownika = id_uzytkownika();
$id = (int) ($dane['id'] ?? 0);
$aktualna = znajdz_zakladke($id, $idUzytkownika);

if (!$aktualna) {
    odpowiedz_json(['sukces' => false, 'komunikat' => 'Nie znaleziono zakladki.'], 404);
}

$zakladka = normalizuj_dane_zakladki($dane);
if ($zakladka['tytul'] === '' || $zakladka['adres'] === '') {
    odpowiedz_json(['sukces' => false, 'komunikat' => 'Uzupelnij tytul i adres URL.'], 422);
}

$kolejnosc = ((int) ($aktualna['id_grupy'] ?? 0) !== (int) ($zakladka['id_grupy'] ?? 0))
    ? nastepna_kolejnosc_grupy($idUzytkownika, $zakladka['id_grupy'])
    : (int) $aktualna['kolejnosc'];

$stmt = baza()->prepare(
    'UPDATE zakladki
     SET id_grupy=:g,id_kategorii=:k,tytul=:t,adres_url=:a,opis=:o,czy_ulubiona=:c,kolejnosc=:kol,data_aktualizacji=NOW()
     WHERE id=:id AND id_uzytkownika=:u'
);
$stmt->execute([
    'g' => $zakladka['id_grupy'],
    'k' => $zakladka['id_kategorii'],
    't' => $zakladka['tytul'],
    'a' => $zakladka['adres'],
    'o' => $zakladka['opis'],
    'c' => $zakladka['czy_ulubiona'],
    'kol' => $kolejnosc,
    'id' => $id,
    'u' => $idUzytkownika,
]);

odpowiedz_json(['sukces' => true, 'komunikat' => 'Zakladka zostala zaktualizowana.']);
