<?php
/*
 * HTTP i formatowanie: HTML escaping, przekierowania, odpowiedzi JSON,
 * odczyt JSON body oraz male helpery walidacyjne uzywane przez widoki i API.
 */

/* POMOC - HTML */
function esc(?string $tekst): string
{
    return htmlspecialchars((string) $tekst, ENT_QUOTES, 'UTF-8');
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
