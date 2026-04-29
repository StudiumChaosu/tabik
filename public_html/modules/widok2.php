<?php
$u = uzytkownik();
$kolorTlaWidok2 = kolor_hex_lub_domyslny($u['kolor_tla_widok2'] ?? null, '#f5f7fb');
?>
<section class="sekcja-panelowa widok2-shell">
    <div class="widok2-toolbar">
        <button type="button" class="przycisk-tla-modulu" data-tlo-modulu-pickr data-tlo-modulu-pole="kolor_tla_widok2" data-tlo-modulu-css="--kolor-tla-widok2" data-tlo-modulu-domyslny="#f5f7fb" style="--kolor-tla-modulu: <?= esc($kolorTlaWidok2) ?>" aria-label="Zmien tlo Widok 2" title="Zmien tlo Widok 2"></button>
    </div>
    <div class="sekcja-naglowek"><div><h2>Widok 2</h2><p>MIEJSCE NA KOLEJNY PROSTY MODUL.</p></div></div>
    <div class="karta-ustawien">TEN WIDOK JEST GOTOWY DO DALSZEJ ROZBUDOWY.</div>
</section>
