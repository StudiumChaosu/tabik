<?php
require_once __DIR__ . '/funkcje.php';
$cfg = require __DIR__ . '/../../config/baza.php';
ensure_uzytkownicy_profil_columns();
$u = uzytkownik();
$kolorTlaZakladki = kolor_hex_lub_domyslny($u['idkolor_zak'] ?? null, '#f5f7fb');
$kolorGrupGlowny = kolor_hex_lub_domyslny($u['idkolor_gru'] ?? null, '#d8b500');
$kolorTlaWidok2 = kolor_hex_lub_domyslny($u['idkolor_prom'] ?? null, '#f5f7fb');
$bazowy_url = bazowy_url_aplikacji();
$pliki_assetow = [
    __DIR__ . '/../assets/js/glowny.js',
    __DIR__ . '/../assets/js/formularze.js',
    __DIR__ . '/../assets/js/zakladki.js',
    __DIR__ . '/../assets/js/widok2.js',
    __DIR__ . '/../assets/css/tokens.css',
    __DIR__ . '/../assets/css/glowny.css',
    __DIR__ . '/../assets/css/panel.css',
    __DIR__ . '/../assets/css/zakladki.css',
];
$wersja_assetow = (string) max(array_map(static fn($plik) => is_file($plik) ? (int) filemtime($plik) : 0, $pliki_assetow));
?>
<!doctype html>
<html lang="pl" data-bazowy-url="<?= esc($bazowy_url) ?>" data-token-csrf="<?= esc(token_csrf()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc(($tytul ?? 'Tabik') . ' • ' . ($cfg['nazwa_aplikacji'] ?? 'Tabik')) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="assets/css/tokens.css?v=<?= esc($wersja_assetow) ?>">
    <link rel="stylesheet" href="assets/css/glowny.css?v=<?= esc($wersja_assetow) ?>">
    <link rel="stylesheet" href="assets/css/panel.css?v=<?= esc($wersja_assetow) ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@simonwep/pickr@1.9.1/dist/themes/monolith.min.css">
    <?php if (($strona_css ?? '') === 'zakladki'): ?>
        <link rel="stylesheet" href="assets/css/zakladki.css?v=<?= esc($wersja_assetow) ?>">
    <?php endif; ?>
    <?= tabik_config_script([
        'koloryUzytkownika' => [
            'idkolor_zak' => $kolorTlaZakladki,
            'idkolor_gru' => $kolorGrupGlowny,
            'idkolor_prom' => $kolorTlaWidok2,
        ],
    ]) ?>
    <script defer src="assets/js/glowny.js?v=<?= esc($wersja_assetow) ?>"></script>
    <script defer src="assets/js/formularze.js?v=<?= esc($wersja_assetow) ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/@simonwep/pickr@1.9.1/dist/pickr.min.js"></script>
    <?php if (($strona_js ?? '') === 'zakladki'): ?>
        <script defer src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
        <script defer src="assets/js/zakladki.js?v=<?= esc($wersja_assetow) ?>"></script>
    <?php endif; ?>
    <?php if (($strona_js ?? '') === 'widok2'): ?>
        <script defer src="assets/js/widok2.js?v=<?= esc($wersja_assetow) ?>"></script>
    <?php endif; ?>
</head>
<body class="uklad-panel modul-<?= esc($aktywny_modul ?? 'panel') ?> motyw-<?= esc($u['motyw'] ?? 'jasny') ?>" style="--kolor-tla-zakladki: <?= esc($kolorTlaZakladki) ?>; --kolor-tla-widok2: <?= esc($kolorTlaWidok2) ?>;">
<div id="stos-powiadomien">
    <?php foreach (['sukces', 'blad'] as $typ): $tekst = pobierz_flash($typ); if ($tekst !== ''): ?>
        <div class="powiadomienie <?= $typ === 'blad' ? 'blad' : 'sukces' ?>"><?= esc($tekst) ?></div>
    <?php endif; endforeach; ?>
</div>
<div class="powloka-aplikacji">
