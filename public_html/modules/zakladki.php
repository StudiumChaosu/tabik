<?php
$dane_startowe = pobierz_dane_zakladek(id_uzytkownika(), $_GET, uzytkownik());
$stanPoczatkowy = [
    'filtry' => $dane_startowe['filtry'],
    'grupy' => $dane_startowe['grupy'],
    'kategorie' => $dane_startowe['kategorie'],
    'liczniki' => $dane_startowe['liczniki'],
];
$u = uzytkownik();
$kolorTlaZakladki = kolor_hex_rgb_lub_domyslny($u['idkolor_zak'] ?? null, '#f5f7fb');
?>
<section class="zakladki-shell zakladki-shell--panel" data-zakladki-app>
    <script type="application/json" id="zakladki-dane-startowe"><?= json_encode($stanPoczatkowy, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
    <div class="zakladki-toolbar">
        <div class="zakladki-toolbar-lewa">
            <button type="button" class="przycisk-tla-modulu" data-kolor-uzytkownika-pickr data-zakladki-kolor-pickr data-kolor-uzytkownika-obszar="idkolor_zak" data-kolor-uzytkownika-css="--kolor-tla-zakladki" data-kolor-uzytkownika-domyslny="#f5f7fb" style="--kolor-tla-modulu: <?= esc($kolorTlaZakladki) ?>" aria-label="Zmien tlo zakladek" title="Zmien tlo zakladek"></button>
        </div>
        <div class="zakladki-toolbar-srodek">
            <label class="zakladki-szukaj-mini" for="pole-szukania-zakladek">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <input type="search" id="pole-szukania-zakladek" placeholder="Wyszukaj zakładkę" autocomplete="off">
            </label>
        </div>
        <div class="zakladki-akcje-gorne">
            <button type="button" class="przycisk-panelowy" data-akcja="import-json">
                <i class="fa-solid fa-file-import" aria-hidden="true"></i>
                <span>Import</span>
            </button>
            <a href="<?= esc(url('api.rekordy.eksport_json')) ?>" class="przycisk-panelowy niebieski">
                <i class="fa-solid fa-file-export" aria-hidden="true"></i>
                <span>Eksport</span>
            </a>
        </div>
    </div>
    <div class="zakladki-kategorie" id="pasek-kategorii"></div>
    <div id="obszar-kolumn" class="siatka-grup-kompakt"></div>
    <input type="file" id="pole-importu-json" accept=".json,.html,text/html,application/json" hidden>
    <div class="warstwa-modalna ukryta" id="modal-zakladka"><div class="okno-modalne"><div class="naglowek-modalny"><div><small>Zakladka</small><h2 id="tytul-modalu-zakladki">Dodaj zakladke</h2></div><button type="button" class="przycisk-ikona" data-zamknij-modal="#modal-zakladka"><i class="fa-solid fa-xmark"></i></button></div><form id="formularz-zakladki" class="formularz-pionowy formularz-wewnetrzny" enctype="multipart/form-data"><input type="hidden" name="id" id="pole-id-zakladki"><div class="pole-formularza"><span>Nazwa linku</span><input type="text" name="tytul" id="pole-tytul-zakladki" required></div><div class="pole-formularza"><span>Adres URL</span><input type="text" name="adres_url" id="pole-adres-zakladki" placeholder="https://..., file:///..., chrome://..." required></div><div class="formularz-dwie-kolumny"><div class="pole-formularza"><span>Grupa</span><select name="id_grupy" id="pole-grupa-zakladki"></select></div><div class="pole-formularza"><span>Kategoria</span><select name="id_kategorii" id="pole-kategoria-zakladki"></select></div></div><div class="pole-formularza"><span>Opis</span><textarea name="opis" id="pole-opis-zakladki" rows="4"></textarea></div><label class="pole-checkbox"><input type="checkbox" name="czy_ulubiona" id="pole-ulubiona-zakladki" value="1"><span>Dodaj do ulubionych</span></label><div class="stopka-modalna"><button type="button" class="przycisk-subtelny" data-zamknij-modal="#modal-zakladka">Anuluj</button><button type="submit" class="przycisk-glowny">Zapisz</button></div></form></div></div>
    <div class="warstwa-modalna ukryta" id="modal-grupa"><div class="okno-modalne male"><div class="naglowek-modalny"><div><small>Grupa</small><h2 id="tytul-modalu-grupy">Dodaj grupe</h2></div><button type="button" class="przycisk-ikona" data-zamknij-modal="#modal-grupa"><i class="fa-solid fa-xmark"></i></button></div><form id="formularz-grupy" class="formularz-pionowy formularz-wewnetrzny"><input type="hidden" id="pole-id-grupy"><input type="hidden" id="pole-id-kategorii-grupy"><div class="pole-formularza"><span>Nazwa grupy</span><input type="text" id="pole-nazwa-grupy" required></div><div class="stopka-modalna"><button type="button" class="przycisk-subtelny" data-zamknij-modal="#modal-grupa">Anuluj</button><button type="submit" class="przycisk-glowny">Zapisz</button></div></form></div></div>
    <div class="warstwa-modalna ukryta" id="modal-kategoria"><div class="okno-modalne male"><div class="naglowek-modalny"><div><small>Kategoria</small><h2>Dodaj kategorie</h2></div><button type="button" class="przycisk-ikona" data-zamknij-modal="#modal-kategoria"><i class="fa-solid fa-xmark"></i></button></div><form id="formularz-kategorii" class="formularz-pionowy formularz-wewnetrzny"><div class="pole-formularza"><span>Nazwa kategorii</span><input type="text" id="pole-nazwa-kategorii" required></div><div class="stopka-modalna"><button type="button" class="przycisk-subtelny" data-zamknij-modal="#modal-kategoria">Anuluj</button><button type="submit" class="przycisk-glowny">Dodaj</button></div></form></div></div>
</section>
