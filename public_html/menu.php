<?php
require_once __DIR__ . '/includes/funkcje.php';

wymagaj_logowania();

$u = uzytkownik();
$modul = $aktywny_modul ?? 'zakladki';
$inicjal = strtoupper(substr((string) ($u['imie'] ?? 'T'), 0, 1));
?>
<header class="gorna-belka" data-gorna-belka>
    <div class="gorna-belka-glowna">
        <a href="panel.php" class="marka-gorna" aria-label="Tabik - panel glowny">
            <img src="assets/img/logo.png" alt="Tabik" class="logo-marki">
            <span class="opis-marki">
                <strong>Tabik</strong>
            </span>
        </a>

        <nav class="nawigacja-glowna" aria-label="Menu glowne">
            <a class="link-boczny <?= $modul === 'zakladki' ? 'jest-aktywny' : '' ?>" href="panel.php?modul=zakladki">
                <i class="fa-solid fa-bookmark"></i>
                <span>Zakladki</span>
            </a>

            <a class="link-boczny <?= $modul === 'widok2' ? 'jest-aktywny' : '' ?>" href="panel.php?modul=widok2">
                <i class="fa-solid fa-window-maximize"></i>
                <span>Widok 2</span>
            </a>
        </nav>

        <div class="profil-gorny">
            <div class="avatar-profilu" aria-hidden="true"><?= esc($inicjal) ?></div>
            <div class="dane-profilu-gorne">
                <strong><?= esc($u['imie'] ?? 'Uzytkownik') ?></strong>
                <small><?= esc($u['email'] ?? '') ?></small>
            </div>
            <a class="link-boczny link-profil-gorny <?= $modul === 'profil' ? 'jest-aktywny' : '' ?>" href="panel.php?modul=profil">
                <i class="fa-solid fa-user"></i>
                <span>Profil</span>
            </a>
        </div>

        <a class="link-wyloguj-gorny" href="api/logowanie.php?akcja=wyloguj">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Wyloguj</span>
        </a>
    </div>
</header>
