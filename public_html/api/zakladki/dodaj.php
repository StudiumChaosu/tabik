<?php
require_once __DIR__ . '/wspolne.php';

$dane = dane_wejscia_api_z_csrf();
$zakladka = normalizuj_dane_zakladki($dane);

if ($zakladka['tytul'] === '' || $zakladka['adres'] === '') {
    odpowiedz_json(['sukces' => false, 'komunikat' => 'Uzupelnij tytul i adres URL.'], 422);
}

$idUzytkownika = id_uzytkownika();
$stmt = baza()->prepare(
    'INSERT INTO zakladki (id_uzytkownika,id_grupy,id_kategorii,tytul,adres_url,opis,czy_ulubiona,kolejnosc)
     VALUES (:u,:g,:k,:t,:a,:o,:c,:kol)'
);
$stmt->execute([
    'u' => $idUzytkownika,
    'g' => $zakladka['id_grupy'],
    'k' => $zakladka['id_kategorii'],
    't' => $zakladka['tytul'],
    'a' => $zakladka['adres'],
    'o' => $zakladka['opis'],
    'c' => $zakladka['czy_ulubiona'],
    'kol' => nastepna_kolejnosc_grupy($idUzytkownika, $zakladka['id_grupy']),
]);

odpowiedz_json(['sukces' => true, 'komunikat' => 'Zakladka zostala dodana.', 'id' => (int) baza()->lastInsertId()], 201);
