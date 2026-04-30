<?php
require_once __DIR__ . '/baza.php';

$akcja = $_GET['akcja'] ?? '';

function czy_zadanie_ajax(): bool
{
    return strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
}

function odpowiedz_logowanie(array $dane, int $status = 200): never
{
    if (czy_zadanie_ajax()) {
        odpowiedz_json($dane, $status);
    }

    if (!empty($dane['sukces'])) {
        przekieruj((string) ($dane['przekierowanie'] ?? url('panel')));
    }

    ustaw_flash('blad_logowania', (string) ($dane['komunikat'] ?? 'Nie udalo sie zalogowac.'));
    przekieruj(url('logowanie'));
}

if ($akcja === 'wyloguj') {
    session_destroy();
    session_start();
    header('Location: ' . url('logowanie'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    odpowiedz_logowanie(['sukces' => false, 'komunikat' => 'Bledne zadanie.'], 405);
}

sprawdz_csrf($_POST['token_csrf'] ?? null);
$email = trim((string) ($_POST['email'] ?? ''));
$haslo = (string) ($_POST['haslo'] ?? '');

if ($email === '' || $haslo === '') {
    odpowiedz_logowanie(['sukces' => false, 'komunikat' => 'Podaj email i haslo.'], 422);
}

$stmt = baza()->prepare('SELECT * FROM uzytkownicy WHERE email = :email AND aktywny = 1 LIMIT 1');
$stmt->execute(['email' => $email]);
$uzytkownik = $stmt->fetch();

if (!$uzytkownik || !password_verify($haslo, (string) $uzytkownik['haslo_hash'])) {
    odpowiedz_logowanie(['sukces' => false, 'komunikat' => 'Niepoprawny email lub haslo.'], 401);
}

$_SESSION['uzytkownik'] = $uzytkownik;
baza()->prepare('UPDATE uzytkownicy SET ostatnie_logowanie = NOW() WHERE id = :id')->execute(['id' => (int) $uzytkownik['id']]);
odpowiedz_logowanie(['sukces' => true, 'komunikat' => 'Zalogowano.', 'przekierowanie' => url('panel')]);
