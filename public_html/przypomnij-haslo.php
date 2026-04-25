<?php
require_once __DIR__ . '/includes/funkcje.php';
if (czy_zalogowany()) {
    przekieruj('panel.php');
}
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
</head>
<body class="uklad-gosc widok-logowania-tabik wariant-przypomnij-tabik">
    <div class="kontener-logowania-tabik">
        <section class="panel-logowania-tabik">
            <div class="sekcja-logo-logowanie-tabik">
                <img src="assets/img/logo.png" alt="Logo Tabik" class="logo-glowne-logowania-tabik">
            </div>

            <div id="powiadomienie-resetu" class="powiadomienie" style="display:none"></div>

            <form id="formularz-resetu" class="formularz-logowania-tabik" novalidate>
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
                <a href="index.php">Wroc do logowania</a>
                <span>|</span>
                <a href="rejestracja.php">Rejestracja</a>
            </div>
        </section>
    </div>
<script>
document.getElementById('formularz-resetu').addEventListener('submit', async function (e) {
    e.preventDefault();
    const box = document.getElementById('powiadomienie-resetu');
    box.className = 'powiadomienie';
    box.style.display = 'none';
    const przycisk = this.querySelector('button[type="submit"]');
    const pierwotnaEtykieta = przycisk.textContent;
    przycisk.disabled = true;
    przycisk.textContent = 'Wysylanie...';

    try {
        const fd = new FormData(this);
        const res = await fetch('api/przypomnij-haslo.php', { method: 'POST', body: fd, credentials: 'same-origin' });
        const dane = await res.json().catch(() => ({ sukces: false, komunikat: 'Niepoprawna odpowiedz serwera.' }));
        box.textContent = dane.komunikat || 'Wystapil blad.';
        box.classList.add(dane.sukces ? 'sukces' : 'blad');
        box.style.display = 'block';
    } finally {
        przycisk.disabled = false;
        przycisk.textContent = pierwotnaEtykieta;
    }
});
</script>
</body>
</html>
