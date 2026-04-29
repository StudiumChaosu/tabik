<?php
require_once __DIR__ . '/includes/funkcje.php';
if (czy_zalogowany()) {
    przekieruj(url('panel'));
}
$wersjaFormularzy = is_file(__DIR__ . '/assets/js/formularze.js') ? (string) filemtime(__DIR__ . '/assets/js/formularze.js') : '1';
?>
<!doctype html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tabik - Przypomnienie hasla</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="assets/css/tokens.css">
    <link rel="stylesheet" href="assets/css/glowny.css">
    <link rel="stylesheet" href="assets/css/panel.css">
    <?= tabik_config_script() ?>
    <script defer src="assets/js/formularze.js?v=<?= esc($wersjaFormularzy) ?>"></script>
</head>
<body class="uklad-gosc widok-logowania-tabik wariant-przypomnij-tabik">
    <div class="kontener-logowania-tabik">
        <section class="panel-logowania-tabik">
            <div class="sekcja-logo-logowanie-tabik">
                <img src="assets/img/logo.png" alt="Logo Tabik" class="logo-glowne-logowania-tabik">
            </div>

            <div id="powiadomienie-resetu" class="powiadomienie" style="display:none"></div>

            <form id="formularz-resetu" class="formularz-logowania-tabik" data-ajax-form data-endpoint="<?= esc(url('api.przypomnij_haslo')) ?>" data-powiadomienie="#powiadomienie-resetu" data-tekst-ladowania="Wysylanie..." method="post" novalidate>
                <input type="hidden" name="token_csrf" value="<?= esc(token_csrf()) ?>">

                <label class="pole-formularza pole-formularza-logowanie-tabik">
                    <span>Email</span>
                    <div class="pole-z-ikona pole-z-ikona-logowanie-tabik">
                        <span class="ikona-pole-logowanie-tabik"><i class="fa-solid fa-at"></i></span>
                        <input class="pole-ui pole-ui--duze pole-ui--z-ikona pole-ui--brand" type="email" name="email" placeholder="twoj@email.pl" autocomplete="email" required>
                    </div>
                </label>

                <button type="submit" class="przycisk-zaloguj-tabik">Przypomnij haslo</button>
            </form>

            <div class="stopka-logowania-tabik">
                <a href="<?= esc(url('logowanie')) ?>">Wroc do logowania</a>
                <span>|</span>
                <a href="<?= esc(url('rejestracja')) ?>">Rejestracja</a>
            </div>
        </section>
    </div>
</body>
</html>
