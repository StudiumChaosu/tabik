<?php
$u = uzytkownik();
$cfg = require __DIR__ . '/../../config/baza.php';
?>
<section class="sekcja-panelowa">
    <div class="sekcja-naglowek">
        <div>
            <h2>Profil</h2>
            <p>PREFERENCJE APLIKACJI I DANE KONTA W JEDNYM WIDOKU.</p>
        </div>
    </div>

    <div class="siatka-profilu-dwie-kolumny">
        <section class="karta-profilu">
            <div class="naglowek-sekcji-profilu">
                <div>
                    <h3>Preferencje aplikacji</h3>
                    <p>Ustawienia interfejsu i widoku startowego po zalogowaniu.</p>
                </div>
            </div>

            <form method="post" action="api/uzytkownicy.php?akcja=ustawienia" class="formularz-pionowy" novalidate>
                <input type="hidden" name="token_csrf" value="<?= esc(token_csrf()) ?>">

                <label class="pole-formularza">
                    <span>Motyw</span>
                    <select name="motyw">
                        <option value="jasny" <?= ($u['motyw'] ?? 'jasny') === 'jasny' ? 'selected' : '' ?>>Jasny</option>
                        <option value="kontrast" <?= ($u['motyw'] ?? '') === 'kontrast' ? 'selected' : '' ?>>Wysoki kontrast</option>
                    </select>
                </label>

                <label class="pole-formularza">
                    <span>Strefa czasowa</span>
                    <select name="strefa_czasowa">
                        <?php foreach (($cfg['strefy_czasowe'] ?? ['Europe/Warsaw']) as $strefa): ?>
                            <option value="<?= esc($strefa) ?>" <?= ($u['strefa_czasowa'] ?? 'Europe/Warsaw') === $strefa ? 'selected' : '' ?>><?= esc($strefa) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="pole-formularza">
                    <span>Widok startowy po zalogowaniu</span>
                    <select name="domyslny_modul">
                        <option value="zakladki" <?= ($u['domyslny_modul'] ?? 'zakladki') === 'zakladki' ? 'selected' : '' ?>>Zakladki</option>
                        <option value="profil" <?= ($u['domyslny_modul'] ?? '') === 'profil' ? 'selected' : '' ?>>Profil</option>
                        <option value="widok2" <?= ($u['domyslny_modul'] ?? '') === 'widok2' ? 'selected' : '' ?>>Widok 2</option>
                    </select>
                </label>

                <label class="pole-formularza">
                    <span>Domyslna kategoria zakladek</span>
                    <select name="domyslna_kategoria">
                        <option value="pierwsza" <?= ($u['domyslna_kategoria'] ?? 'pierwsza') === 'pierwsza' ? 'selected' : '' ?>>Pierwsza kategoria</option>
                        <option value="ostatnia" <?= ($u['domyslna_kategoria'] ?? '') === 'ostatnia' ? 'selected' : '' ?>>Ostatnio otwarta kategoria</option>
                    </select>
                </label>

                <button type="submit" class="przycisk-glowny">Zapisz preferencje</button>
            </form>
        </section>

        <section class="karta-profilu">
            <div class="naglowek-sekcji-profilu">
                <div>
                    <h3>Dane konta</h3>
                    <p>Podstawowe informacje o aktualnie zalogowanym uzytkowniku.</p>
                </div>
            </div>

            <dl class="lista-danych-konta">
                <div>
                    <dt>Imie</dt>
                    <dd><?= esc($u['imie'] ?? '') ?></dd>
                </div>
                <div>
                    <dt>Email</dt>
                    <dd><?= esc($u['email'] ?? '') ?></dd>
                </div>
                <div>
                    <dt>Motyw</dt>
                    <dd><?= esc($u['motyw'] ?? 'jasny') ?></dd>
                </div>
                <div>
                    <dt>Strefa czasowa</dt>
                    <dd><?= esc($u['strefa_czasowa'] ?? 'Europe/Warsaw') ?></dd>
                </div>
                <div>
                    <dt>Widok startowy</dt>
                    <dd><?= esc(etykieta_modulu($u['domyslny_modul'] ?? 'zakladki')) ?></dd>
                </div>
                <div>
                    <dt>Kategoria startowa zakladek</dt>
                    <dd><?= ($u['domyslna_kategoria'] ?? 'pierwsza') === 'ostatnia' ? 'Ostatnio otwarta' : 'Pierwsza' ?></dd>
                </div>
            </dl>
        </section>
    </div>
</section>
