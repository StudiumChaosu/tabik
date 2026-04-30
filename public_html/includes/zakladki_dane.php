<?php
/*
 * Dane modulu Zakladki: kategorie, grupy, filtry, lista robocza i eksport.
 */

/* DANE - LICZNIKI */
function pobierz_liczniki_zakladek(int $idUzytkownika): array
{
    $stmt = baza()->prepare(
        'SELECT COUNT(*) AS wszystkie,
                SUM(CASE WHEN czy_ulubiona = 1 THEN 1 ELSE 0 END) AS ulubione,
                SUM(CASE WHEN data_utworzenia >= DATE_SUB(NOW(), INTERVAL 14 DAY) THEN 1 ELSE 0 END) AS ostatnie
         FROM zakladki
         WHERE id_uzytkownika = :id'
    );
    $stmt->execute(['id' => $idUzytkownika]);
    $dane = $stmt->fetch() ?: [];
    return [
        'wszystkie' => (int) ($dane['wszystkie'] ?? 0),
        'ulubione' => (int) ($dane['ulubione'] ?? 0),
        'ostatnie' => (int) ($dane['ostatnie'] ?? 0),
    ];
}

/* DANE - KATEGORIE */
function pobierz_kategorie(int $idUzytkownika): array
{
    $stmt = baza()->prepare(
        'SELECT k.id, k.nazwa, k.ikona, k.kolejnosc, COUNT(z.id) AS licznik
         FROM kategorie_zakladek k
         LEFT JOIN zakladki z ON z.id_kategorii = k.id AND z.id_uzytkownika = k.id_uzytkownika
         WHERE k.id_uzytkownika = :id
         GROUP BY k.id
         ORDER BY k.kolejnosc ASC, k.id ASC'
    );
    $stmt->execute(['id' => $idUzytkownika]);
    return $stmt->fetchAll() ?: [];
}

/* DANE - GRUPY */
function grupy_maja_id_kategorii(): bool
{
    return czy_kolumna_istnieje('grupy_zakladek', 'id_kategorii');
}

function grupy_maja_kolor(): bool
{
    return czy_kolumna_istnieje('grupy_zakladek', 'kolor');
}

function upewnij_kolumne_koloru_grupy(): bool
{
    if (grupy_maja_kolor()) {
        try {
            baza()->exec("ALTER TABLE grupy_zakladek MODIFY COLUMN kolor VARCHAR(9) NULL DEFAULT NULL");
        } catch (Throwable $e) {
            // Starsze lub ograniczone bazy moga nie pozwalac na MODIFY; aplikacja nadal dziala dla istniejacej kolumny.
        }
        return true;
    }

    try {
        baza()->exec("ALTER TABLE grupy_zakladek ADD COLUMN kolor VARCHAR(9) NULL DEFAULT NULL AFTER czy_zwinieta");
        return true;
    } catch (Throwable $e) {
        return grupy_maja_kolor();
    }
}

function pobierz_grupy(int $idUzytkownika): array
{
    $czyPowiazaneZKategoria = grupy_maja_id_kategorii();
    $czyKolorGrupy = grupy_maja_kolor();
    $kolumnaKategorii = $czyPowiazaneZKategoria ? 'g.id_kategorii' : 'NULL AS id_kategorii';
    $kolumnaKoloru = $czyKolorGrupy ? 'g.kolor' : 'NULL AS kolor';

    $stmt = baza()->prepare(
        'SELECT g.id, g.nazwa, g.kolejnosc, g.czy_zwinieta, ' . $kolumnaKategorii . ', ' . $kolumnaKoloru . ', COUNT(z.id) AS licznik
         FROM grupy_zakladek g
         LEFT JOIN zakladki z ON z.id_grupy = g.id AND z.id_uzytkownika = g.id_uzytkownika
         WHERE g.id_uzytkownika = :id
         GROUP BY g.id
         ORDER BY g.kolejnosc ASC, g.id ASC'
    );
    $stmt->execute(['id' => $idUzytkownika]);
    return $stmt->fetchAll() ?: [];
}

/* DANE - FILTRY */
function pobierz_filtry_zakladek(array $wejscie, array $uzytkownik, int $idUzytkownika): array
{
    $filtry = [
        'q' => trim((string) ($wejscie['q'] ?? '')),
        'filtr' => trim((string) ($wejscie['filtr'] ?? 'wszystkie')),
        'id_grupy' => int_lub_null($wejscie['id_grupy'] ?? null),
        'id_kategorii' => int_lub_null($wejscie['id_kategorii'] ?? null),
    ];

    if ($filtry['id_kategorii'] === null) {
        if (($uzytkownik['domyslna_kategoria'] ?? 'pierwsza') === 'ostatnia' && !empty($uzytkownik['ostatnia_kategoria_id'])) {
            $filtry['id_kategorii'] = (int) $uzytkownik['ostatnia_kategoria_id'];
        } else {
            $kategorie = pobierz_kategorie($idUzytkownika);
            if (!empty($kategorie)) {
                $filtry['id_kategorii'] = (int) $kategorie[0]['id'];
            }
        }
    }

    return $filtry;
}

/* DANE - LISTA ZAKLADEK */
function pobierz_dane_zakladek(int $idUzytkownika, array $wejscie, array $uzytkownik): array
{
    $filtry = pobierz_filtry_zakladek($wejscie, $uzytkownik, $idUzytkownika);
    $grupyBazowe = pobierz_grupy($idUzytkownika);
    $kategorie = pobierz_kategorie($idUzytkownika);
    $liczniki = pobierz_liczniki_zakladek($idUzytkownika);
    $domyslnyKolorGrupy = kolor_hex_lub_domyslny($uzytkownik['idkolor_gru'] ?? null, '#d8b500') . '30';

    $grupy = [];
    foreach ($grupyBazowe as $grupa) {
        $idKategoriiGrupy = int_lub_null($grupa['id_kategorii'] ?? null);

        if (grupy_maja_id_kategorii() && $filtry['id_kategorii'] !== null && (int) $idKategoriiGrupy !== (int) $filtry['id_kategorii']) {
            continue;
        }

        $grupy[(int) $grupa['id']] = [
            'id' => (int) $grupa['id'],
            'nazwa' => $grupa['nazwa'],
            'kolejnosc' => (int) $grupa['kolejnosc'],
            'czy_zwinieta' => (int) ($grupa['czy_zwinieta'] ?? 0),
            'id_kategorii' => $idKategoriiGrupy,
            'kolor' => preg_match('/^#[0-9a-fA-F]{6}([0-9a-fA-F]{2})?$/', (string) ($grupa['kolor'] ?? '')) ? strtolower((string) $grupa['kolor']) : $domyslnyKolorGrupy,
            'licznik' => 0,
            'zakladki' => [],
        ];
    }
    $grupy[0] = ['id' => 0, 'nazwa' => 'Bez grupy', 'kolejnosc' => 999999, 'czy_zwinieta' => 0, 'kolor' => $domyslnyKolorGrupy, 'licznik' => 0, 'zakladki' => []];

    /* DANE - POBRANIE PELNEJ LISTY */
    $stmt = baza()->prepare(
        'SELECT z.id, z.tytul, z.adres_url, z.opis, z.czy_ulubiona, z.id_grupy, z.id_kategorii, z.kolejnosc, z.data_utworzenia,
                g.nazwa AS nazwa_grupy, k.nazwa AS nazwa_kategorii
         FROM zakladki z
         LEFT JOIN grupy_zakladek g ON g.id = z.id_grupy AND g.id_uzytkownika = z.id_uzytkownika
         LEFT JOIN kategorie_zakladek k ON k.id = z.id_kategorii AND k.id_uzytkownika = z.id_uzytkownika
         WHERE z.id_uzytkownika = :id_uzytkownika
         ORDER BY COALESCE(g.kolejnosc, 999999), z.kolejnosc ASC, z.id ASC'
    );
    $stmt->execute(['id_uzytkownika' => $idUzytkownika]);

    foreach ($stmt->fetchAll() ?: [] as $wiersz) {
        $idGrupy = int_lub_null($wiersz['id_grupy']);
        $idKategorii = int_lub_null($wiersz['id_kategorii']);
        $tytul = (string) ($wiersz['tytul'] ?? '');
        $adres = (string) ($wiersz['adres_url'] ?? '');
        $opis = (string) ($wiersz['opis'] ?? '');
        $czyUlubiona = (int) ($wiersz['czy_ulubiona'] ?? 0);

        /* FILTR - KATEGORIA */
        if ($filtry['id_kategorii'] !== null && (int) $idKategorii !== (int) $filtry['id_kategorii']) {
            continue;
        }

        /* FILTR - GRUPA */
        if ($filtry['id_grupy'] !== null && (int) $idGrupy !== (int) $filtry['id_grupy']) {
            continue;
        }

        /* FILTR - ULUBIONE */
        if ($filtry['filtr'] === 'ulubione' && $czyUlubiona !== 1) {
            continue;
        }

        /* FILTR - WYSZUKIWANIE */
        if ($filtry['q'] !== '') {
            $fraza = mb_strtolower($filtry['q']);
            $tekst = mb_strtolower($tytul . ' ' . $adres . ' ' . $opis);
            if (!str_contains($tekst, $fraza)) {
                continue;
            }
        }

        $kluczGrupy = (int) ($idGrupy ?? 0);
        if (!isset($grupy[$kluczGrupy])) {
            $grupy[$kluczGrupy] = [
                'id' => $kluczGrupy,
                'nazwa' => (string) ($wiersz['nazwa_grupy'] ?: 'Bez grupy'),
                'kolejnosc' => 999999,
                'czy_zwinieta' => 0,
                'kolor' => $domyslnyKolorGrupy,
                'licznik' => 0,
                'zakladki' => [],
            ];
        }

        $grupy[$kluczGrupy]['zakladki'][] = [
            'id' => (int) $wiersz['id'],
            'tytul' => $tytul,
            'adres_url' => $adres,
            'opis' => $opis,
            'czy_ulubiona' => $czyUlubiona,
            'id_grupy' => $idGrupy,
            'id_kategorii' => $idKategorii,
            'nazwa_kategorii' => $wiersz['nazwa_kategorii'],
            'data_utworzenia' => $wiersz['data_utworzenia'],
            'kolejnosc' => (int) ($wiersz['kolejnosc'] ?? 0),
        ];
        $grupy[$kluczGrupy]['licznik']++;
    }

        $czyOgraniczonyWidok = $filtry['q'] !== '' || $filtry['filtr'] !== 'wszystkie' || $filtry['id_grupy'] !== null;
    if ($czyOgraniczonyWidok) {
        $grupy = array_filter($grupy, static fn(array $grupa): bool => (int) ($grupa['licznik'] ?? 0) > 0);
    }

    if ($filtry['id_kategorii'] !== null) {
        $grupy = array_filter($grupy, static function (array $grupa): bool {
            if ((int) ($grupa['id'] ?? 0) === 0) {
                return (int) ($grupa['licznik'] ?? 0) > 0;
            }

            return int_lub_null($grupa['id_kategorii'] ?? null) !== null;
        });
    }

    $grupy = array_values($grupy);
    usort($grupy, static fn(array $a, array $b): int => ((int) $a['kolejnosc']) <=> ((int) $b['kolejnosc']));

    return [
        'filtry' => $filtry,
        'grupy' => $grupy,
        'kategorie' => $kategorie,
        'liczniki' => $liczniki,
    ];
}

/* DANE - EKSPORT */
function pobierz_zakladki_do_eksportu(int $idUzytkownika): array
{
    $stmt = baza()->prepare(
        'SELECT z.id, z.tytul, z.adres_url, z.opis, z.czy_ulubiona, z.kolejnosc, z.data_utworzenia, z.data_aktualizacji,
                g.nazwa AS grupa, k.nazwa AS kategoria
         FROM zakladki z
         LEFT JOIN grupy_zakladek g ON g.id = z.id_grupy AND g.id_uzytkownika = z.id_uzytkownika
         LEFT JOIN kategorie_zakladek k ON k.id = z.id_kategorii AND k.id_uzytkownika = z.id_uzytkownika
         WHERE z.id_uzytkownika = :id
         ORDER BY COALESCE(k.kolejnosc, 999999), COALESCE(g.kolejnosc, 999999), z.kolejnosc, z.id DESC'
    );
    $stmt->execute(['id' => $idUzytkownika]);
    return $stmt->fetchAll() ?: [];
}
