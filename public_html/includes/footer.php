<?php require_once __DIR__ . '/funkcje.php'; $u = uzytkownik(); ?>
<aside class="panel-kontekstowy" data-panel-kontekstowy>
    <div class="panel-kontekstowy-naglowek">
        <strong>Panel</strong>
        <button type="button" class="przycisk-ikona przycisk-zwin" data-przelacz-prawy-panel aria-label="Zwin panel kontekstowy">
            <i class="fa-solid fa-angle-right"></i>
        </button>
    </div>
    <div class="sekcja-kontekstowa">
        <div class="naglowek-sekcji">
            <h2>Czas lokalny</h2>
            <small><?= esc($u['strefa_czasowa'] ?? 'Europe/Warsaw') ?></small>
        </div>
        <div class="zegar-cyfrowy" data-zegar>--:--</div>
    </div>
    <div class="sekcja-kontekstowa">
        <div class="naglowek-sekcji">
            <h2>Kalendarz</h2>
            <small>Air Datepicker</small>
        </div>
        <div id="kalendarz-panelu"></div>
    </div>
    <div class="sekcja-kontekstowa">
        <div class="naglowek-sekcji"><h2>Szybkie wskazowki</h2></div>
        <ul class="lista-wskazowek">
            <li>PRZECIAGNIJ KARTY MIEDZY KOLUMNAMI, ABY ZMIENIC GRUPY.</li>
            <li>UZYJ EKSPORTU JSON DO ARCHIWIZACJI DANYCH.</li>
            <li>W MODULE ZAKLADKI MOZESZ IMPORTOWAC PLIKI Z POPRZEDNICH EKSPORTOW.</li>
        </ul>
    </div>
</aside>
</div>
<button type="button" class="przycisk-ikona przycisk-zwin przycisk-zwin-prawy jest-ukryty" data-przelacz-prawy-panel aria-label="Przelacz panel kontekstowy">
    <i class="fa-solid fa-angle-left"></i>
</button>
</div>
</body>
</html>
