<?php
require_once __DIR__ . '/baza.php';

function czy_zadanie_ajax(): bool
{
    return strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
}

function odpowiedz_rejestracja(array $dane, int $status = 200): never
{
    if (czy_zadanie_ajax()) {
        odpowiedz_json($dane, $status);
    }

    if (!empty($dane['sukces'])) {
        ustaw_flash('blad_logowania', (string) ($dane['komunikat'] ?? 'Konto zostalo utworzone. Mozesz sie teraz zalogowac.'));
        przekieruj(url('logowanie'));
    }

    ustaw_flash('komunikat_rejestracji', (string) ($dane['komunikat'] ?? 'Nie udalo sie utworzyc konta.'));
    przekieruj(url('rejestracja'));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    odpowiedz_rejestracja(['sukces' => false, 'komunikat' => 'Bledne zadanie.'], 405);
}

sprawdz_csrf($_POST['token_csrf'] ?? null);
$email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
$haslo = (string) ($_POST['haslo'] ?? '');
$hasloPowtorz = (string) ($_POST['haslo_powtorz'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    odpowiedz_rejestracja(['sukces' => false, 'komunikat' => 'Podaj poprawny adres email.'], 422);
}

if (mb_strlen($haslo) < 8) {
    odpowiedz_rejestracja(['sukces' => false, 'komunikat' => 'Haslo musi miec co najmniej 8 znakow.'], 422);
}

if (!hash_equals($haslo, $hasloPowtorz)) {
    odpowiedz_rejestracja(['sukces' => false, 'komunikat' => 'Hasla nie sa identyczne.'], 422);
}

$stmt = baza()->prepare('SELECT id FROM uzytkownicy WHERE email = :email LIMIT 1');
$stmt->execute(['email' => $email]);
if ($stmt->fetch()) {
    odpowiedz_rejestracja(['sukces' => false, 'komunikat' => 'Konto o tym adresie email juz istnieje.'], 409);
}

ensure_uzytkownicy_domyslny_modul_column();
ensure_uzytkownicy_profil_columns();

$kolumny = ['email', 'haslo_hash'];
$placeholders = [':email', ':haslo_hash'];
$wartosci = [
    'email' => $email,
    'haslo_hash' => password_hash($haslo, PASSWORD_DEFAULT),
];

if (czy_kolumna_istnieje('uzytkownicy', 'aktywny')) {
    $kolumny[] = 'aktywny';
    $placeholders[] = ':aktywny';
    $wartosci['aktywny'] = 1;
}
if (czy_kolumna_istnieje('uzytkownicy', 'motyw')) {
    $kolumny[] = 'motyw';
    $placeholders[] = ':motyw';
    $wartosci['motyw'] = 'jasny';
}
if (czy_kolumna_istnieje('uzytkownicy', 'strefa_czasowa')) {
    $kolumny[] = 'strefa_czasowa';
    $placeholders[] = ':strefa_czasowa';
    $wartosci['strefa_czasowa'] = 'Europe/Warsaw';
}
if (czy_kolumna_istnieje('uzytkownicy', 'domyslna_kategoria')) {
    $kolumny[] = 'domyslna_kategoria';
    $placeholders[] = ':domyslna_kategoria';
    $wartosci['domyslna_kategoria'] = 'pierwsza';
}
if (czy_kolumna_istnieje('uzytkownicy', 'domyslny_modul')) {
    $kolumny[] = 'domyslny_modul';
    $placeholders[] = ':domyslny_modul';
    $wartosci['domyslny_modul'] = 'zakladki';
}

$domyslneKolory = [
    'idkolor_zak' => '#f5f7fb',
    'idkolor_gru' => '#d8b500',
    'idkolor_prom' => '#f5f7fb',
];
foreach ($domyslneKolory as $kolumna => $kolor) {
    if (czy_kolumna_istnieje('uzytkownicy', $kolumna)) {
        $kolumny[] = $kolumna;
        $placeholders[] = ':' . $kolumna;
        $wartosci[$kolumna] = $kolor;
    }
}

if (czy_kolumna_istnieje('uzytkownicy', 'data_utworzenia')) {
    $kolumny[] = 'data_utworzenia';
    $placeholders[] = 'NOW()';
}
if (czy_kolumna_istnieje('uzytkownicy', 'data_aktualizacji')) {
    $kolumny[] = 'data_aktualizacji';
    $placeholders[] = 'NOW()';
}

$sql = 'INSERT INTO uzytkownicy (' . implode(', ', $kolumny) . ') VALUES (' . implode(', ', $placeholders) . ')';
baza()->prepare($sql)->execute($wartosci);

odpowiedz_rejestracja([
    'sukces' => true,
    'komunikat' => 'Konto zostalo utworzone. Mozesz sie teraz zalogowac.',
    'przekierowanie' => url('logowanie'),
]);
