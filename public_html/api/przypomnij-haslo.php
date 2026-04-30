<?php
require_once __DIR__ . '/baza.php';

function czy_zadanie_ajax(): bool
{
    return strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
}

function odpowiedz_reset_hasla(array $dane, int $status = 200): never
{
    if (czy_zadanie_ajax()) {
        odpowiedz_json($dane, $status);
    }

    ustaw_flash('komunikat_resetu', (string) ($dane['komunikat'] ?? 'Zgloszenie zostalo przyjete.'));
    przekieruj(url('przypomnij_haslo'));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    odpowiedz_reset_hasla(['sukces' => false, 'komunikat' => 'Bledne zadanie.'], 405);
}

sprawdz_csrf($_POST['token_csrf'] ?? null);
$email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    odpowiedz_reset_hasla(['sukces' => false, 'komunikat' => 'Podaj poprawny adres email.'], 422);
}

odpowiedz_reset_hasla([
    'sukces' => true,
    'komunikat' => 'Jesli konto istnieje, administrator moze teraz zresetowac haslo dla adresu: ' . $email . '.'
]);
