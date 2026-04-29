<?php

/* START - SESJA I KONFIGURACJA */
$konfiguracja = require __DIR__ . '/../../config/baza.php';
date_default_timezone_set($konfiguracja['strefa_czasowa'] ?? 'Europe/Warsaw');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name($konfiguracja['nazwa_sesji'] ?? 'tabik_sesja');
    session_start();
}

/* START - BAZA DANYCH */
function baza(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $cfg = require __DIR__ . '/../../config/baza.php';
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $cfg['host'], $cfg['port'], $cfg['nazwa'], $cfg['kodowanie']);
    $pdo = new PDO($dsn, $cfg['uzytkownik'], $cfg['haslo'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}

/* POMOC - HTML */
function esc(?string $tekst): string
{
    return htmlspecialchars((string) $tekst, ENT_QUOTES, 'UTF-8');
}

/* POMOC - ROUTING */
function tabik_trasy(): array
{
    return [
        'logowanie' => 'index.php',
        'rejestracja' => 'rejestracja.php',
        'przypomnij_haslo' => 'przypomnij-haslo.php',
        'panel' => 'panel.php',
        'panel.modul' => 'panel.php?modul=:modul',
        'api.logowanie' => 'api/logowanie.php',
        'api.wyloguj' => 'api/logowanie.php?akcja=wyloguj',
        'api.rejestracja' => 'api/rejestracja.php',
        'api.przypomnij_haslo' => 'api/przypomnij-haslo.php',
        'api.uzytkownicy.ustawienia' => 'api/uzytkownicy.php?akcja=ustawienia',
        'api.ustawienia_kolorow' => 'api/ustawienia_kolorow.php',
        'api.rekordy.eksport_json' => 'api/rekordy.php?akcja=eksport_json',
        'api.zakladki.lista' => 'api/zakladki/lista.php',
        'api.zakladki.dodaj' => 'api/zakladki/dodaj.php',
        'api.zakladki.edytuj' => 'api/zakladki/edytuj.php',
        'api.zakladki.usun' => 'api/zakladki/usun.php',
        'api.zakladki.przenies' => 'api/zakladki/przenies.php',
        'api.zakladki.ulubiona.przelacz' => 'api/zakladki/ulubiona/przelacz.php',
        'api.zakladki.ostatnia_kategoria' => 'api/zakladki/zapisz-ostatnia-kategorie.php',
        'api.zakladki.grupy.dodaj' => 'api/zakladki/grupy/dodaj.php',
        'api.zakladki.grupy.edytuj' => 'api/zakladki/grupy/edytuj.php',
        'api.zakladki.grupy.usun' => 'api/zakladki/grupy/usun.php',
        'api.zakladki.grupy.kolor' => 'api/zakladki/grupy/kolor.php',
        'api.zakladki.grupy.kolejnosc' => 'api/zakladki/grupy/kolejnosc.php',
        'api.zakladki.grupy.przenies_kategoria' => 'api/zakladki/grupy/przenies-kategoria.php',
        'api.zakladki.kategorie.dodaj' => 'api/zakladki/kategorie/dodaj.php',
        'api.zakladki.kategorie.kolejnosc' => 'api/zakladki/kategorie/kolejnosc.php',
        'api.zakladki.import.json' => 'api/zakladki/import/json.php',
        'api.zakladki.import.html' => 'api/zakladki/import/html.php',
    ];
}

function bazowy_url_aplikacji(): string
{
    $katalog = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? ''))), '/');
    if ($katalog === '' || $katalog === '.' || $katalog === '/') {
        return '';
    }

    $katalog = preg_replace('~/api(?:/.*)?$~', '', $katalog) ?: '';
    return rtrim($katalog, '/');
}

function url(string $nazwa = '', array $parametry = []): string
{
    $trasy = tabik_trasy();
    $sciezka = $nazwa === '' ? '' : ($trasy[$nazwa] ?? ltrim($nazwa, '/'));
    $uzyteParametry = [];

    $sciezka = preg_replace_callback('/\:([a-zA-Z_][a-zA-Z0-9_]*)/', static function (array $trafienie) use ($parametry, &$uzyteParametry): string {
        $klucz = $trafienie[1];
        if (!array_key_exists($klucz, $parametry)) {
            throw new InvalidArgumentException('Brak parametru trasy: ' . $klucz);
        }

        $uzyteParametry[$klucz] = true;
        return rawurlencode((string) $parametry[$klucz]);
    }, $sciezka);

    $nadmiarowe = array_diff_key($parametry, $uzyteParametry);
    if (!empty($nadmiarowe)) {
        $sciezka .= (str_contains($sciezka, '?') ? '&' : '?') . http_build_query($nadmiarowe);
    }

    $bazowy = bazowy_url_aplikacji();
    return ($bazowy === '' ? '' : $bazowy) . '/' . ltrim($sciezka, '/');
}

function tabik_konfiguracja_js(array $dodatkowa = []): array
{
    return array_replace_recursive([
        'bazowyUrl' => bazowy_url_aplikacji(),
        'tokenCsrf' => token_csrf(),
        'routes' => tabik_trasy(),
    ], $dodatkowa);
}

function tabik_config_script(array $dodatkowa = []): string
{
    $json = json_encode(tabik_konfiguracja_js($dodatkowa), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return '<script>window.tabik=window.tabik||{};window.tabik.config=' . $json . ';</script>';
}

/* POMOC - PRZEKIEROWANIE */
function przekieruj(string $sciezka): never
{
    header('Location: ' . $sciezka);
    exit;
}

/* POMOC - JSON */
function odpowiedz_json(array $dane, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($dane, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/* POMOC - WEJSCIE JSON */
function pobierz_json_wejscia(): array
{
    $surowe = file_get_contents('php://input') ?: '';
    $dane = json_decode($surowe, true);
    return is_array($dane) ? $dane : [];
}

/* POMOC - CSRF */
function token_csrf(): string
{
    if (empty($_SESSION['token_csrf'])) {
        $_SESSION['token_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['token_csrf'];
}

/* POMOC - WALIDACJA CSRF */
function sprawdz_csrf(?string $token): void
{
    if (!$token || !hash_equals((string) ($_SESSION['token_csrf'] ?? ''), $token)) {
        odpowiedz_json(['sukces' => false, 'komunikat' => 'Nieprawidlowy token formularza.'], 419);
    }
}

/* POMOC - LOGOWANIE */
function czy_zalogowany(): bool
{
    return !empty($_SESSION['uzytkownik']['id']);
}

/* POMOC - WYMAGAJ LOGOWANIA */
function wymagaj_logowania(): void
{
    if (!czy_zalogowany()) {
        przekieruj(url('logowanie'));
    }
}

/* POMOC - WYMAGAJ LOGOWANIA DLA API */
function wymagaj_logowania_api(): void
{
    if (!czy_zalogowany()) {
        odpowiedz_json(['sukces' => false, 'komunikat' => 'Brak autoryzacji.'], 401);
    }
}

/* POMOC - UZYTKOWNIK */
function uzytkownik(): array
{
    return $_SESSION['uzytkownik'] ?? [];
}

/* POMOC - ID UZYTKOWNIKA */
function id_uzytkownika(): int
{
    return (int) (uzytkownik()['id'] ?? 0);
}

/* POMOC - FLASH */
function ustaw_flash(string $typ, string $tekst): void
{
    $_SESSION['flash'][$typ] = $tekst;
}

/* POMOC - FLASH ODCZYT */
function pobierz_flash(string $typ): string
{
    $tekst = (string) ($_SESSION['flash'][$typ] ?? '');
    unset($_SESSION['flash'][$typ]);
    return $tekst;
}

/* POMOC - ODSWIEZENIE SESJI */
function odswiez_sesje_uzytkownika(int $id): void
{
    $stmt = baza()->prepare('SELECT * FROM uzytkownicy WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $uzytkownik = $stmt->fetch();
    if ($uzytkownik) {
        $_SESSION['uzytkownik'] = $uzytkownik;
    }
}

/* POMOC - NORMALIZACJA URL */
function uporzadkuj_url(string $url): string
{
    $url = trim($url);
    if ($url === '') {
        return '';
    }

    if (preg_match('~^file:///[^\s]+$~i', $url)) {
        return $url;
    }

    if (preg_match('~^chrome://[^\s]+$~i', $url)) {
        return $url;
    }

    if (!preg_match('~^[a-z][a-z0-9+.-]*://~i', $url)) {
        $url = 'https://' . $url;
    }

    if (preg_match('~^https?://~i', $url)) {
        return filter_var($url, FILTER_VALIDATE_URL) ? $url : '';
    }

    return '';
}

/* POMOC - INT LUB NULL */
function int_lub_null(mixed $wartosc): ?int
{
    if ($wartosc === null || $wartosc === '' || (int) $wartosc <= 0) {
        return null;
    }
    return (int) $wartosc;
}

/* POMOC - ZWROC PLIK */
function pobierz_plik_importu(string $nazwa): array
{
    if (empty($_FILES[$nazwa]) || !is_array($_FILES[$nazwa])) {
        odpowiedz_json(['sukces' => false, 'komunikat' => 'Nie wybrano pliku.'], 422);
    }
    return $_FILES[$nazwa];
}

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
function czy_kolumna_istnieje(string $tabela, string $kolumna): bool
{
    static $cache = [];
    $klucz = $tabela . '.' . $kolumna;
    if (array_key_exists($klucz, $cache)) {
        return $cache[$klucz];
    }

    $stmt = baza()->prepare(
        'SELECT COUNT(*)
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :tabela AND COLUMN_NAME = :kolumna'
    );
    $stmt->execute(['tabela' => $tabela, 'kolumna' => $kolumna]);

    return $cache[$klucz] = ((int) $stmt->fetchColumn()) > 0;
}

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
    $domyslnyKolorGrupy = kolor_hex_rgb_lub_domyslny($uzytkownik['idkolor_gru'] ?? null, '#d8b500') . '30';

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



/* DANE - PROFIL UZYTKOWNIKA */
function ensure_uzytkownicy_profil_columns(): void
{
    ensure_uzytkownicy_domyslny_modul_column();
    $kolumny = [
        'avatar' => "ALTER TABLE uzytkownicy ADD COLUMN avatar VARCHAR(255) NULL DEFAULT NULL AFTER email",
        'idkolor_zak' => "ALTER TABLE uzytkownicy ADD COLUMN idkolor_zak VARCHAR(7) NULL DEFAULT NULL AFTER domyslny_modul",
        'idkolor_gru' => "ALTER TABLE uzytkownicy ADD COLUMN idkolor_gru VARCHAR(7) NULL DEFAULT NULL AFTER idkolor_zak",
        'idkolor_prom' => "ALTER TABLE uzytkownicy ADD COLUMN idkolor_prom VARCHAR(7) NULL DEFAULT NULL AFTER idkolor_gru",
    ];

    foreach ($kolumny as $kolumna => $sql) {
        if (czy_kolumna_istnieje('uzytkownicy', $kolumna)) {
            continue;
        }

        try {
            baza()->exec($sql);
        } catch (Throwable $e) {
            // Kolumna mogla zostac dodana rownolegle albo baza nie pozwala na ALTER.
        }
    }
}

function kolor_hex_lub_domyslny(mixed $kolor, string $domyslny): string
{
    $wartosc = strtolower(trim((string) $kolor));
    return preg_match('/^#[0-9a-f]{6}([0-9a-f]{2})?$/', $wartosc) ? $wartosc : $domyslny;
}

function kolor_hex_rgb_lub_domyslny(mixed $kolor, string $domyslny): string
{
    $wartosc = strtolower(trim((string) $kolor));
    return preg_match('/^#[0-9a-f]{6}$/', $wartosc) ? $wartosc : $domyslny;
}

function nazwa_wyswietlana_uzytkownika(array $uzytkownik): string
{
    $nazwa = trim((string) ($uzytkownik['imie'] ?? ''));
    if ($nazwa !== '') {
        return $nazwa;
    }

    $email = trim((string) ($uzytkownik['email'] ?? ''));
    return $email !== '' ? $email : 'Uzytkownik';
}

function inicjaly_uzytkownika(array $uzytkownik): string
{
    $zrodlo = nazwa_wyswietlana_uzytkownika($uzytkownik);
    if (function_exists('mb_substr')) {
        $inicjaly = mb_strtoupper(mb_substr($zrodlo, 0, 2, 'UTF-8'), 'UTF-8');
    } else {
        $inicjaly = strtoupper(substr($zrodlo, 0, 2));
    }

    return trim($inicjaly) !== '' ? $inicjaly : 'TY';
}

function sciezka_awatara(?string $avatar): string
{
    $avatar = trim((string) $avatar);
    if ($avatar === '' || str_contains($avatar, '..') || str_starts_with($avatar, '/') || preg_match('~^[a-z][a-z0-9+.-]*://~i', $avatar)) {
        return '';
    }

    return $avatar;
}
function ensure_uzytkownicy_domyslny_modul_column(): void
{
    if (czy_kolumna_istnieje('uzytkownicy', 'domyslny_modul')) {
        return;
    }

    baza()->exec("ALTER TABLE uzytkownicy ADD COLUMN domyslny_modul VARCHAR(32) NOT NULL DEFAULT 'zakladki' AFTER domyslna_kategoria");
}

function domyslny_modul_uzytkownika(array $uzytkownik): string
{
    $modul = (string) ($uzytkownik['domyslny_modul'] ?? 'zakladki');
    return in_array($modul, ['zakladki', 'profil', 'widok2'], true) ? $modul : 'zakladki';
}

function etykieta_modulu(string $modul): string
{
    return match ($modul) {
        'zakladki' => 'Zakladki',
        'profil' => 'Profil',
        'widok2' => 'Widok 2',
        default => 'Zakladki',
    };
}
