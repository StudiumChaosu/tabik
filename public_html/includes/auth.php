<?php
/*
 * Autoryzacja i sesja uzytkownika: status logowania, wymaganie dostepu,
 * pobieranie danych zalogowanego uzytkownika i odswiezanie sesji po zapisie profilu.
 */

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
