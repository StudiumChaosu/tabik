<?php
/*
 * Bezpieczenstwo: generowanie i walidacja tokenu CSRF dla formularzy i endpointow AJAX.
 */

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
