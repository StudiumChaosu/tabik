<?php
require_once __DIR__ . '/baza.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    odpowiedz_json(['sukces' => false, 'komunikat' => 'Bledne zadanie.'], 405);
}

sprawdz_csrf($_POST['token_csrf'] ?? null);
$email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    odpowiedz_json(['sukces' => false, 'komunikat' => 'Podaj poprawny adres email.'], 422);
}

odpowiedz_json([
    'sukces' => true,
    'komunikat' => 'Jesli konto istnieje, administrator moze teraz zresetowac haslo dla adresu: ' . $email . '.'
]);
