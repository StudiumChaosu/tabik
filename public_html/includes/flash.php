<?php
/*
 * Komunikaty flash: jednorazowe powiadomienia trzymane w sesji.
 */

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
