<?php
require_once __DIR__ . '/includes/funkcje.php';

wymagaj_logowania();

$u = uzytkownik();
$modul = $aktywny_modul ?? 'zakladki';
$inicjal = strtoupper(substr((string) ($u['imie'] ?? 'T'), 0, 1));
?>
<aside class="panel-boczny" data-panel-boczny>
    <div class="sekcja-marki">
        <a href="panel.php" class="marka">
            <img src="assets/img/logo.png" alt="Tabik" class="logo-marki">
            <span class="opis-marki">
                <strong>Tabik</strong>
            </span>
        </a>

        <button
            type="button"
            class="przycisk-ikona przycisk-zwin"
            data-przelacz-panel-boczny
            title="Zwin panel"
            aria-label="Zwin panel"
        >
            <i class="fa-solid fa-chevron-left"></i>
        </button>
    </div>

    <nav class="nawigacja-glowna" aria-label="Menu glowne">
        <a class="link-boczny <?= $modul === 'zakladki' ? 'jest-aktywny' : '' ?>" href="panel.php?modul=zakladki">
            <i class="fa-solid fa-bookmark"></i>
            <span>Zakladki</span>
        </a>

        <a class="link-boczny <?= $modul === 'profil' ? 'jest-aktywny' : '' ?>" href="panel.php?modul=profil">
            <i class="fa-solid fa-user"></i>
            <span>Profil</span>
        </a>

        <a class="link-boczny <?= $modul === 'widok2' ? 'jest-aktywny' : '' ?>" href="panel.php?modul=widok2">
            <i class="fa-solid fa-window-maximize"></i>
            <span>Widok 2</span>
        </a>
    </nav>

    <div class="profil-boczny">
        <div class="avatar-profilu"><?= esc($inicjal) ?></div>
        <div>
            <strong><?= esc($u['imie'] ?? 'Uzytkownik') ?></strong>
            <small><?= esc($u['email'] ?? '') ?></small>
        </div>
    </div>

    <a class="link-boczny link-wyloguj" href="api/logowanie.php?akcja=wyloguj">
        <i class="fa-solid fa-right-from-bracket"></i>
        <span>Wyloguj</span>
    </a>
</aside>
