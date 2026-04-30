<?php
require_once __DIR__ . '/../baza.php';
wymagaj_logowania_api();

/* WEJSCIE API - POST/JSON */
function dane_wejscia_api(): array
{
    return str_contains(strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? '')), 'application/json') ? pobierz_json_wejscia() : $_POST;
}

/* CSRF API - wspolny punkt kontroli tokenu */
function sprawdz_csrf_api(array $dane = []): void
{
    sprawdz_csrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($dane['token_csrf'] ?? null));
}

/* WEJSCIE API Z CSRF - standardowy bootstrap endpointow zakladek */
function dane_wejscia_api_z_csrf(): array
{
    $dane = dane_wejscia_api();
    sprawdz_csrf_api($dane);
    return $dane;
}

/* ZAKLADKI - pobranie pojedynczej zakladki wlasciciela */
function znajdz_zakladke(int $id, int $idUzytkownika): ?array
{
    $stmt = baza()->prepare('SELECT * FROM zakladki WHERE id = :id AND id_uzytkownika = :u LIMIT 1');
    $stmt->execute(['id' => $id, 'u' => $idUzytkownika]);
    return $stmt->fetch() ?: null;
}

/* ZAKLADKI - wspolna normalizacja danych dodawania/edycji */
function normalizuj_dane_zakladki(array $dane): array
{
    return [
        'tytul' => trim((string) ($dane['tytul'] ?? '')),
        'adres' => uporzadkuj_url((string) ($dane['adres_url'] ?? $dane['adres'] ?? '')),
        'id_grupy' => int_lub_null($dane['id_grupy'] ?? null),
        'id_kategorii' => int_lub_null($dane['id_kategorii'] ?? null),
        'opis' => trim((string) ($dane['opis'] ?? '')),
        'czy_ulubiona' => !empty($dane['czy_ulubiona']) ? 1 : 0,
    ];
}

/* ZAKLADKI - kolejny numer w grupie */
function nastepna_kolejnosc_grupy(int $idUzytkownika, ?int $idGrupy): int
{
    $sql = 'SELECT COALESCE(MAX(kolejnosc), -1) + 1 FROM zakladki WHERE id_uzytkownika = :u AND ' . ($idGrupy ? 'id_grupy = :g' : 'id_grupy IS NULL');
    $stmt = baza()->prepare($sql);
    $parametry = ['u' => $idUzytkownika];
    if ($idGrupy) {
        $parametry['g'] = $idGrupy;
    }
    $stmt->execute($parametry);
    return (int) $stmt->fetchColumn();
}

/* ZAKLADKI - zapis kolejnosci elementow w grupie */
function ustaw_kolejnosc_grupy(int $idUzytkownika, array $ids): void
{
    $stmt = baza()->prepare('UPDATE zakladki SET kolejnosc = :k WHERE id = :id AND id_uzytkownika = :u');
    foreach (array_values($ids) as $kolejnosc => $id) {
        $stmt->execute(['k' => $kolejnosc, 'id' => (int) $id, 'u' => $idUzytkownika]);
    }
}

/* KOLEJNOSC - wspolny zapis kolejnosci grup/kategorii, tylko przez biala liste */
function ustaw_kolejnosc_rekordow(string $typ, int $idUzytkownika, array $ids): void
{
    $mapa = [
        'grupy' => 'grupy_zakladek',
        'kategorie' => 'kategorie_zakladek',
    ];

    if (!isset($mapa[$typ])) {
        odpowiedz_json(['sukces' => false, 'komunikat' => 'Nieznany typ kolejnosci.'], 422);
    }

    $tabela = $mapa[$typ];
    $stmt = baza()->prepare("UPDATE {$tabela} SET kolejnosc = :k WHERE id = :id AND id_uzytkownika = :u");
    foreach (array_values($ids) as $kolejnosc => $id) {
        $stmt->execute(['k' => $kolejnosc, 'id' => (int) $id, 'u' => $idUzytkownika]);
    }
}

/* GRUPY - znajdz istniejaca grupe lub utworz nowa dla konkretnego uzytkownika */
function znajdz_lub_utworz_grupe(int $idUzytkownika, string $nazwa, ?int $idKategorii = null): ?int
{
    $nazwa = trim($nazwa);
    if ($nazwa === '') {
        return null;
    }

    $czyPowiazaneZKategoria = grupy_maja_id_kategorii();

    if ($czyPowiazaneZKategoria) {
        $sqlSzukania = 'SELECT id FROM grupy_zakladek WHERE id_uzytkownika = :u AND nazwa = :n AND ' . ($idKategorii ? 'id_kategorii = :k' : 'id_kategorii IS NULL') . ' LIMIT 1';
        $stmt = baza()->prepare($sqlSzukania);
        $parametry = ['u' => $idUzytkownika, 'n' => $nazwa];
        if ($idKategorii) {
            $parametry['k'] = $idKategorii;
        }
        $stmt->execute($parametry);
    } else {
        $stmt = baza()->prepare('SELECT id FROM grupy_zakladek WHERE id_uzytkownika = :u AND nazwa = :n LIMIT 1');
        $stmt->execute(['u' => $idUzytkownika, 'n' => $nazwa]);
    }

    $id = $stmt->fetchColumn();
    if ($id) {
        return (int) $id;
    }

    if ($czyPowiazaneZKategoria) {
        $sqlKolejnosci = 'SELECT COALESCE(MAX(kolejnosc), -1) + 1 FROM grupy_zakladek WHERE id_uzytkownika = :u AND ' . ($idKategorii ? 'id_kategorii = :k' : 'id_kategorii IS NULL');
        $stmt = baza()->prepare($sqlKolejnosci);
        $parametry = ['u' => $idUzytkownika];
        if ($idKategorii) {
            $parametry['k'] = $idKategorii;
        }
        $stmt->execute($parametry);
        $kolejnosc = (int) $stmt->fetchColumn();

        $stmt = baza()->prepare('INSERT INTO grupy_zakladek (id_uzytkownika, id_kategorii, nazwa, kolejnosc) VALUES (:u, :k, :n, :kol)');
        $stmt->execute(['u' => $idUzytkownika, 'k' => $idKategorii, 'n' => $nazwa, 'kol' => $kolejnosc]);
    } else {
        $stmt = baza()->prepare('SELECT COALESCE(MAX(kolejnosc), -1) + 1 FROM grupy_zakladek WHERE id_uzytkownika = :u');
        $stmt->execute(['u' => $idUzytkownika]);
        $kolejnosc = (int) $stmt->fetchColumn();

        $stmt = baza()->prepare('INSERT INTO grupy_zakladek (id_uzytkownika, nazwa, kolejnosc) VALUES (:u, :n, :k)');
        $stmt->execute(['u' => $idUzytkownika, 'n' => $nazwa, 'k' => $kolejnosc]);
    }

    return (int) baza()->lastInsertId();
}

/* KATEGORIE - znajdz istniejaca kategorie lub utworz nowa dla konkretnego uzytkownika */
function znajdz_lub_utworz_kategorie(int $idUzytkownika, string $nazwa): ?int
{
    $nazwa = trim($nazwa);
    if ($nazwa === '') {
        return null;
    }

    $stmt = baza()->prepare('SELECT id FROM kategorie_zakladek WHERE id_uzytkownika = :u AND nazwa = :n LIMIT 1');
    $stmt->execute(['u' => $idUzytkownika, 'n' => $nazwa]);
    $id = $stmt->fetchColumn();
    if ($id) {
        return (int) $id;
    }

    $stmt = baza()->prepare('SELECT COALESCE(MAX(kolejnosc), -1) + 1 FROM kategorie_zakladek WHERE id_uzytkownika = :u');
    $stmt->execute(['u' => $idUzytkownika]);
    $kolejnosc = (int) $stmt->fetchColumn();

    $stmt = baza()->prepare('INSERT INTO kategorie_zakladek (id_uzytkownika, nazwa, kolejnosc) VALUES (:u, :n, :k)');
    $stmt->execute(['u' => $idUzytkownika, 'n' => $nazwa, 'k' => $kolejnosc]);
    return (int) baza()->lastInsertId();
}

/* IMPORT - wspolny zapis zakladek niezalezny od formatu zrodla */
function importuj_zakladki_z_tablicy(array $zakladki, int $idUzytkownika): array
{
    $importowane = 0;
    $pominiete = 0;
    $insert = baza()->prepare(
        'INSERT INTO zakladki (id_uzytkownika,id_grupy,id_kategorii,tytul,adres_url,opis,czy_ulubiona,kolejnosc)
         VALUES (:u,:g,:k,:t,:a,:o,:c,:kol)'
    );
    $sprawdz = baza()->prepare('SELECT id FROM zakladki WHERE id_uzytkownika = :u AND tytul = :t AND adres_url = :a LIMIT 1');

    foreach ($zakladki as $zakladka) {
        if (!is_array($zakladka)) {
            $pominiete++;
            continue;
        }

        $tytul = trim((string) ($zakladka['tytul'] ?? $zakladka['title'] ?? ''));
        $adres = uporzadkuj_url((string) ($zakladka['adres_url'] ?? $zakladka['url'] ?? ''));
        if ($tytul === '' || $adres === '') {
            $pominiete++;
            continue;
        }

        $sprawdz->execute(['u' => $idUzytkownika, 't' => $tytul, 'a' => $adres]);
        if ($sprawdz->fetchColumn()) {
            $pominiete++;
            continue;
        }

        $idKategorii = znajdz_lub_utworz_kategorie($idUzytkownika, (string) ($zakladka['kategoria'] ?? ''));
        $idGrupy = znajdz_lub_utworz_grupe($idUzytkownika, (string) ($zakladka['grupa'] ?? ''), $idKategorii);

        $insert->execute([
            'u' => $idUzytkownika,
            'g' => $idGrupy,
            'k' => $idKategorii,
            't' => $tytul,
            'a' => $adres,
            'o' => trim((string) ($zakladka['opis'] ?? '')),
            'c' => (int) ($zakladka['czy_ulubiona'] ?? 0),
            'kol' => nastepna_kolejnosc_grupy($idUzytkownika, $idGrupy),
        ]);
        $importowane++;
    }

    return ['importowane' => $importowane, 'pominiete' => $pominiete];
}
