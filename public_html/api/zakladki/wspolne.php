<?php
require_once __DIR__ . '/../baza.php';
wymagaj_logowania_api();

function dane_wejscia_api(): array
{
    return str_contains(strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? '')), 'application/json') ? pobierz_json_wejscia() : $_POST;
}

function znajdz_zakladke(int $id, int $idUzytkownika): ?array
{
    $stmt = baza()->prepare('SELECT * FROM zakladki WHERE id = :id AND id_uzytkownika = :u LIMIT 1');
    $stmt->execute(['id' => $id, 'u' => $idUzytkownika]);
    return $stmt->fetch() ?: null;
}

function nastepna_kolejnosc_grupy(int $idUzytkownika, ?int $idGrupy): int
{
    $sql = 'SELECT COALESCE(MAX(kolejnosc), -1) + 1 FROM zakladki WHERE id_uzytkownika = :u AND ' . ($idGrupy ? 'id_grupy = :g' : 'id_grupy IS NULL');
    $stmt = baza()->prepare($sql);
    $param = ['u' => $idUzytkownika];
    if ($idGrupy) { $param['g'] = $idGrupy; }
    $stmt->execute($param);
    return (int) $stmt->fetchColumn();
}

function ustaw_kolejnosc_grupy(int $idUzytkownika, ?int $idGrupy, array $ids): void
{
    $stmt = baza()->prepare('UPDATE zakladki SET kolejnosc = :k WHERE id = :id AND id_uzytkownika = :u');
    foreach (array_values($ids) as $kolejnosc => $id) {
        $stmt->execute(['k' => $kolejnosc, 'id' => (int) $id, 'u' => $idUzytkownika]);
    }
}

function znajdz_lub_utworz_grupe(int $idUzytkownika, string $nazwa, ?int $idKategorii = null): ?int
{
    $nazwa = trim($nazwa);
    if ($nazwa === '') { return null; }

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
    if ($id) { return (int) $id; }

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
        $kolejnosc = (int) baza()->query('SELECT COALESCE(MAX(kolejnosc), -1) + 1 FROM grupy_zakladek WHERE id_uzytkownika = ' . id_uzytkownika())->fetchColumn();
        $stmt = baza()->prepare('INSERT INTO grupy_zakladek (id_uzytkownika, nazwa, kolejnosc) VALUES (:u, :n, :k)');
        $stmt->execute(['u' => $idUzytkownika, 'n' => $nazwa, 'k' => $kolejnosc]);
    }

    return (int) baza()->lastInsertId();
}

function znajdz_lub_utworz_kategorie(int $idUzytkownika, string $nazwa): ?int
{
    $nazwa = trim($nazwa);
    if ($nazwa === '') { return null; }
    $stmt = baza()->prepare('SELECT id FROM kategorie_zakladek WHERE id_uzytkownika = :u AND nazwa = :n LIMIT 1');
    $stmt->execute(['u' => $idUzytkownika, 'n' => $nazwa]);
    $id = $stmt->fetchColumn();
    if ($id) { return (int) $id; }
    $kolejnosc = (int) baza()->query('SELECT COALESCE(MAX(kolejnosc), -1) + 1 FROM kategorie_zakladek WHERE id_uzytkownika = ' . id_uzytkownika())->fetchColumn();
    $stmt = baza()->prepare('INSERT INTO kategorie_zakladek (id_uzytkownika, nazwa, kolejnosc) VALUES (:u, :n, :k)');
    $stmt->execute(['u' => $idUzytkownika, 'n' => $nazwa, 'k' => $kolejnosc]);
    return (int) baza()->lastInsertId();
}
