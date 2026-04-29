<?php
require_once __DIR__ . '/baza.php';
wymagaj_logowania();

$akcja = $_GET['akcja'] ?? '';
if ($akcja === 'kolor_tla' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $dane = str_contains(strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? '')), 'application/json') ? pobierz_json_wejscia() : $_POST;
    sprawdz_csrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($dane['token_csrf'] ?? null));
    ensure_uzytkownicy_profil_columns();

    $pole = (string) ($dane['pole'] ?? '');
    if (!in_array($pole, ['kolor_tla_zakladki', 'kolor_tla_widok2'], true)) {
        odpowiedz_json(['sukces' => false, 'komunikat' => 'Nieprawidlowe pole tla.'], 422);
    }

    $kolor = kolor_hex_lub_domyslny($dane['kolor'] ?? null, '#f5f7fb');
    $sql = sprintf('UPDATE uzytkownicy SET %s = :kolor, data_aktualizacji = NOW() WHERE id = :id', $pole);
    baza()->prepare($sql)->execute([
        'kolor' => $kolor,
        'id' => id_uzytkownika(),
    ]);

    odswiez_sesje_uzytkownika(id_uzytkownika());
    odpowiedz_json(['sukces' => true, 'komunikat' => 'Kolor tla zostal zapisany.', 'kolor' => $kolor, 'pole' => $pole]);
}

if ($akcja !== 'ustawienia' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../panel.php?modul=profil');
    exit;
}

sprawdz_csrf($_POST['token_csrf'] ?? null);

ensure_uzytkownicy_domyslny_modul_column();
ensure_uzytkownicy_profil_columns();

$motyw = in_array($_POST['motyw'] ?? 'jasny', ['jasny', 'kontrast'], true) ? $_POST['motyw'] : 'jasny';
$domyslna = in_array($_POST['domyslna_kategoria'] ?? 'pierwsza', ['pierwsza', 'ostatnia'], true) ? $_POST['domyslna_kategoria'] : 'pierwsza';
$domyslnyModul = in_array($_POST['domyslny_modul'] ?? 'zakladki', ['zakladki', 'profil', 'widok2'], true) ? $_POST['domyslny_modul'] : 'zakladki';
$aktualnyUzytkownik = uzytkownik();
$nazwaUzytkownika = trim((string) ($_POST['imie'] ?? ''));
$nazwaUzytkownika = mb_substr($nazwaUzytkownika, 0, 80, 'UTF-8');
$kolorTlaZakladki = kolor_hex_lub_domyslny($_POST['kolor_tla_zakladki'] ?? ($aktualnyUzytkownik['kolor_tla_zakladki'] ?? null), '#f5f7fb');
$kolorTlaWidok2 = kolor_hex_lub_domyslny($_POST['kolor_tla_widok2'] ?? ($aktualnyUzytkownik['kolor_tla_widok2'] ?? null), '#f5f7fb');

$avatar = sciezka_awatara($aktualnyUzytkownik['avatar'] ?? '');

if (!empty($_FILES['avatar']) && is_array($_FILES['avatar']) && (int) ($_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    $plik = $_FILES['avatar'];

    if ((int) ($plik['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        ustaw_flash('blad', 'NIE UDALO SIE WCZYTAC AWATARA.');
        header('Location: ../panel.php?modul=profil');
        exit;
    }

    if ((int) ($plik['size'] ?? 0) > 2 * 1024 * 1024) {
        ustaw_flash('blad', 'AWATAR MOZE MIEC MAKSYMALNIE 2 MB.');
        header('Location: ../panel.php?modul=profil');
        exit;
    }

    $tmp = (string) ($plik['tmp_name'] ?? '');
    $infoObrazu = @getimagesize($tmp);
    $mime = (string) ($infoObrazu['mime'] ?? '');
    $rozszerzenie = match ($mime) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        default => '',
    };

    if ($rozszerzenie === '') {
        ustaw_flash('blad', 'AWATAR MUSI BYC PLIKIEM JPG ALBO PNG.');
        header('Location: ../panel.php?modul=profil');
        exit;
    }

    $katalogDocelowy = __DIR__ . '/../uploads/awatary';
    if (!is_dir($katalogDocelowy)) {
        mkdir($katalogDocelowy, 0755, true);
    }

    $nazwaPliku = 'avatar_' . id_uzytkownika() . '_' . time() . '.' . $rozszerzenie;
    $sciezkaDocelowa = $katalogDocelowy . '/' . $nazwaPliku;

    if (!move_uploaded_file($tmp, $sciezkaDocelowa)) {
        ustaw_flash('blad', 'NIE UDALO SIE ZAPISAC AWATARA.');
        header('Location: ../panel.php?modul=profil');
        exit;
    }

    if ($avatar !== '' && str_starts_with($avatar, 'uploads/awatary/')) {
        $staryPlik = __DIR__ . '/../' . $avatar;
        if (is_file($staryPlik)) {
            @unlink($staryPlik);
        }
    }

    $avatar = 'uploads/awatary/' . $nazwaPliku;
}

baza()->prepare('UPDATE uzytkownicy SET imie = :imie, avatar = :avatar, motyw = :motyw, domyslna_kategoria = :domyslna, domyslny_modul = :domyslny_modul, kolor_tla_zakladki = :kolor_tla_zakladki, kolor_tla_widok2 = :kolor_tla_widok2, data_aktualizacji = NOW() WHERE id = :id')
    ->execute([
        'imie' => $nazwaUzytkownika,
        'avatar' => $avatar !== '' ? $avatar : null,
        'motyw' => $motyw,
        'domyslna' => $domyslna,
        'domyslny_modul' => $domyslnyModul,
        'kolor_tla_zakladki' => $kolorTlaZakladki,
        'kolor_tla_widok2' => $kolorTlaWidok2,
        'id' => id_uzytkownika(),
    ]);

odswiez_sesje_uzytkownika(id_uzytkownika());
ustaw_flash('sukces', 'USTAWIENIA ZOSTALY ZAPISANE.');
header('Location: ../panel.php?modul=profil');
exit;
