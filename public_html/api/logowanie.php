<?php
require_once __DIR__ . '/baza.php';

$akcja = $_GET['akcja'] ?? '';

if ($akcja === 'wyloguj') {
    session_destroy();
    session_start();
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    odpowiedz_json(['sukces' => false, 'komunikat' => 'Bledne zadanie.'], 405);
}

sprawdz_csrf($_POST['token_csrf'] ?? null);
$email = trim((string) ($_POST['email'] ?? ''));
$haslo = (string) ($_POST['haslo'] ?? '');

if ($email === '' || $haslo === '') {
    odpowiedz_json(['sukces' => false, 'komunikat' => 'Podaj email i haslo.'], 422);
}

$stmt = baza()->prepare('SELECT * FROM uzytkownicy WHERE email = :email AND aktywny = 1 LIMIT 1');
$stmt->execute(['email' => $email]);
$uzytkownik = $stmt->fetch();

if (!$uzytkownik || !password_verify($haslo, (string) $uzytkownik['haslo_hash'])) {
    odpowiedz_json(['sukces' => false, 'komunikat' => 'Niepoprawny email lub haslo.'], 401);
}

$_SESSION['uzytkownik'] = $uzytkownik;
baza()->prepare('UPDATE uzytkownicy SET ostatnie_logowanie = NOW() WHERE id = :id')->execute(['id' => (int) $uzytkownik['id']]);
odpowiedz_json(['sukces' => true, 'komunikat' => 'Zalogowano.', 'przekierowanie' => 'panel.php']);
