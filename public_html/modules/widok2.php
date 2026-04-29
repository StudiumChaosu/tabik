<?php
$u = uzytkownik();
$kolorTlaWidok2 = kolor_hex_rgb_lub_domyslny($u['idkolor_prom'] ?? null, '#f5f7fb');
?>
<section class="sekcja-panelowa widok2-shell">
    <div class="widok2-toolbar">
        <button type="button" class="przycisk-tla-modulu" data-kolor-uzytkownika-pickr data-widok2-kolor-pickr data-kolor-uzytkownika-obszar="idkolor_prom" data-kolor-uzytkownika-css="--kolor-tla-widok2" data-kolor-uzytkownika-domyslny="#f5f7fb" style="--kolor-tla-modulu: <?= esc($kolorTlaWidok2) ?>" aria-label="Zmien tlo Widok 2" title="Zmien tlo Widok 2"></button>
    </div>
    <div class="sekcja-naglowek"><div><h2>Widok 2</h2><p>MIEJSCE NA KOLEJNY PROSTY MODUL.</p></div></div>
    <div>TEN WIDOK JEST GOTOWY DO DALSZEJ ROZBUDOWY.</div>
</section>
