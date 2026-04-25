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
    <title>Tabik - Rejestracja</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="assets/css/tokens.css">
    <link rel="stylesheet" href="assets/css/glowny.css">
    <link rel="stylesheet" href="assets/css/panel.css">
</head>
<body class="uklad-gosc widok-logowania-tabik wariant-rejestracja-tabik">
    <div class="kontener-logowania-tabik">
        <section class="panel-logowania-tabik">
            <div class="sekcja-logo-logowanie-tabik">
                <img src="assets/img/logo.png" alt="Logo Tabik" class="logo-glowne-logowania-tabik">
            </div>

            <div id="powiadomienie-rejestracji" class="powiadomienie" style="display:none"></div>

            <form id="formularz-rejestracji" class="formularz-logowania-tabik" novalidate>
                <input type="hidden" name="token_csrf" value="<?= esc(token_csrf()) ?>">

                <label class="pole-formularza pole-formularza-logowanie-tabik">
                    <span>Email</span>
                    <div class="pole-z-ikona pole-z-ikona-logowanie-tabik">
                        <span class="ikona-pole-logowanie-tabik"><i class="fa-solid fa-at"></i></span>
                        <input class="pole-ui pole-ui--duze pole-ui--z-ikona pole-ui--brand" type="email" name="email" placeholder="twoj@email.pl" autocomplete="email" required>
                    </div>
                </label>

                <label class="pole-formularza pole-formularza-logowanie-tabik">
                    <span>Haslo</span>
                    <div class="pole-z-ikona pole-z-przyciskiem pole-z-ikona-logowanie-tabik">
                        <span class="ikona-pole-logowanie-tabik"><i class="fa-solid fa-lock"></i></span>
                        <input class="pole-ui pole-ui--duze pole-ui--z-ikona pole-ui--brand" type="password" name="haslo" id="pole-haslo-rejestracja" placeholder="Minimum 8 znakow" autocomplete="new-password" required>
                        <button type="button" class="przycisk-pokaz-haslo-tabik" data-przelacz-haslo="#pole-haslo-rejestracja" aria-label="Pokaz lub ukryj haslo">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </label>

                <label class="pole-formularza pole-formularza-logowanie-tabik">
                    <span>Powtorz haslo</span>
                    <div class="pole-z-ikona pole-z-przyciskiem pole-z-ikona-logowanie-tabik">
                        <span class="ikona-pole-logowanie-tabik"><i class="fa-solid fa-lock"></i></span>
                        <input class="pole-ui pole-ui--duze pole-ui--z-ikona pole-ui--brand" type="password" name="haslo_powtorz" id="pole-haslo-rejestracja-2" placeholder="Powtorz haslo" autocomplete="new-password" required>
                        <button type="button" class="przycisk-pokaz-haslo-tabik" data-przelacz-haslo="#pole-haslo-rejestracja-2" aria-label="Pokaz lub ukryj haslo">
                            <i class="fa-solid fa-eye"></i></button>
                    </div>
                </label>

                <button type="submit" class="przycisk-zaloguj-tabik">Zaloz konto</button>
            </form>

            <div class="stopka-logowania-tabik">
                <span>Masz juz konto?</span>
                <a href="index.php">Wroc do logowania</a>
            </div>
        </section>
    </div>
<script>
document.querySelectorAll('[data-przelacz-haslo]').forEach(function (przycisk) {
    przycisk.addEventListener('click', function () {
        const pole = document.querySelector(this.getAttribute('data-przelacz-haslo'));
        if (!pole) return;
        const ikona = this.querySelector('i');
        const czyHaslo = pole.getAttribute('type') === 'password';
        pole.setAttribute('type', czyHaslo ? 'text' : 'password');
        if (ikona) {
            ikona.classList.toggle('fa-eye', !czyHaslo);
            ikona.classList.toggle('fa-eye-slash', czyHaslo);
        }
    });
});

document.getElementById('formularz-rejestracji').addEventListener('submit', async function (e) {
    e.preventDefault();
    const box = document.getElementById('powiadomienie-rejestracji');
    box.className = 'powiadomienie';
    box.style.display = 'none';
    const przycisk = this.querySelector('button[type="submit"]');
    const pierwotnaEtykieta = przycisk.textContent;
    przycisk.disabled = true;
    przycisk.textContent = 'Tworzenie konta...';

    try {
        const fd = new FormData(this);
        const res = await fetch('api/rejestracja.php', { method: 'POST', body: fd, credentials: 'same-origin' });
        const dane = await res.json().catch(() => ({ sukces: false, komunikat: 'Niepoprawna odpowiedz serwera.' }));
        box.textContent = dane.komunikat || 'Wystapil blad.';
        box.classList.add(dane.sukces ? 'sukces' : 'blad');
        box.style.display = 'block';
        if (dane.sukces && dane.przekierowanie) {
            setTimeout(function () { window.location.href = dane.przekierowanie; }, 1200);
        }
    } finally {
        przycisk.disabled = false;
        przycisk.textContent = pierwotnaEtykieta;
    }
});
</script>
</body>
</html>
