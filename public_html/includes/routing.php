<?php
/*
 * Routing helper: centralna mapa tras PHP/JS oraz funkcje budowania URL.
 */

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
