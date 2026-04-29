<?php
require_once __DIR__ . '/includes/funkcje.php';

wymagaj_logowania();

$u = uzytkownik();
$modul = $aktywny_modul ?? 'zakladki';
$nazwaGorna = nazwa_wyswietlana_uzytkownika($u);
$inicjal = function_exists('mb_substr') ? mb_strtoupper(mb_substr($nazwaGorna, 0, 1, 'UTF-8'), 'UTF-8') : strtoupper(substr($nazwaGorna, 0, 1));
$avatarGorny = sciezka_awatara($u['avatar'] ?? '');
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
            <?php if ($avatarGorny !== ''): ?>
                <img src="<?= esc($avatarGorny) ?>" alt="Avatar" class="avatar-profilu avatar-profilu-img">
            <?php else: ?>
                <div class="avatar-profilu" aria-hidden="true"><?= esc($inicjal) ?></div>
            <?php endif; ?>
            <div class="dane-profilu-gorne">
                <strong><?= esc($nazwaGorna) ?></strong>
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
