<?php
require_once __DIR__ . '/baza.php';
wymagaj_logowania();

$akcja = $_GET['akcja'] ?? '';
if ($akcja !== 'ustawienia' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../panel.php?modul=profil');
    exit;
}

sprawdz_csrf($_POST['token_csrf'] ?? null);
$motyw = in_array($_POST['motyw'] ?? 'jasny', ['jasny', 'kontrast'], true) ? $_POST['motyw'] : 'jasny';
$cfg = require __DIR__ . '/../../config/baza.php';
$strefa = in_array($_POST['strefa_czasowa'] ?? 'Europe/Warsaw', $cfg['strefy_czasowe'], true) ? $_POST['strefa_czasowa'] : 'Europe/Warsaw';
$domyslna = in_array($_POST['domyslna_kategoria'] ?? 'pierwsza', ['pierwsza', 'ostatnia'], true) ? $_POST['domyslna_kategoria'] : 'pierwsza';
$domyslnyModul = in_array($_POST['domyslny_modul'] ?? 'zakladki', ['zakladki', 'profil', 'widok2'], true) ? $_POST['domyslny_modul'] : 'zakladki';

ensure_uzytkownicy_domyslny_modul_column();

baza()->prepare('UPDATE uzytkownicy SET motyw = :motyw, strefa_czasowa = :strefa, domyslna_kategoria = :domyslna, domyslny_modul = :domyslny_modul, data_aktualizacji = NOW() WHERE id = :id')
    ->execute([
        'motyw' => $motyw,
        'strefa' => $strefa,
        'domyslna' => $domyslna,
        'domyslny_modul' => $domyslnyModul,
        'id' => id_uzytkownika(),
    ]);
odswiez_sesje_uzytkownika(id_uzytkownika());
ustaw_flash('sukces', 'USTAWIENIA ZOSTALY ZAPISANE.');
header('Location: ../panel.php?modul=profil');
exit;
