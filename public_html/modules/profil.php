<?php
ensure_uzytkownicy_domyslny_modul_column();
ensure_uzytkownicy_profil_columns();
$u = uzytkownik();
$email = trim((string) ($u['email'] ?? ''));
$imie = trim((string) ($u['imie'] ?? ''));
$nazwa = nazwa_wyswietlana_uzytkownika($u);
$inicjaly = inicjaly_uzytkownika($u);
$avatar = sciezka_awatara($u['avatar'] ?? '');
$kolorTlaZakladki = kolor_hex_lub_domyslny($u['kolor_tla_zakladki'] ?? null, '#f5f7fb');
$kolorTlaWidok2 = kolor_hex_lub_domyslny($u['kolor_tla_widok2'] ?? null, '#f5f7fb');
?>
<section class="profil-nowy" aria-label="Profil uzytkownika">
    <form method="post" action="api/uzytkownicy.php?akcja=ustawienia" class="profil-nowy-powloka" enctype="multipart/form-data" novalidate>
        <input type="hidden" name="token_csrf" value="<?= esc(token_csrf()) ?>">

        <div class="profil-nowy-siatka">
            <aside class="profil-karta profil-karta-konto">
                <div class="profil-konto-gora">
                    <label class="profil-avatar-upload" for="pole-avatar" title="Zmien avatar">
                        <?php if ($avatar !== ''): ?>
                            <img src="<?= esc($avatar) ?>" alt="Avatar uzytkownika" class="profil-avatar-obraz">
                        <?php else: ?>
                            <span class="profil-avatar-duzy" aria-hidden="true"><?= esc($inicjaly) ?></span>
                        <?php endif; ?>
                        <span class="profil-avatar-podpowiedz"><i class="fa-solid fa-camera"></i></span>
                    </label>
                    <input type="file" id="pole-avatar" name="avatar" accept="image/jpeg,image/png,.jpg,.jpeg,.png" hidden>

                    <div class="profil-meta-uzytkownika">
                        <strong><?= esc($nazwa) ?></strong>
                        <small><?= esc($email) ?></small>
                    </div>
                </div>

                <div class="profil-lista-meta">
                    <label class="profil-pole">
                        <span>Nazwa uzytkownika</span>
                        <input type="text" name="imie" value="<?= esc($imie) ?>" maxlength="80" autocomplete="name" placeholder="Wpisz nazwe uzytkownika">
                    </label>
                    <label class="profil-pole">
                        <span>E-mail</span>
                        <input type="email" value="<?= esc($email) ?>" readonly>
                    </label>
                </div>
            </aside>

            <section class="profil-karta profil-karta-preferencje" aria-label="Preferencje aplikacji">
                <div class="formularz-pionowy profil-formularz">
                    <label class="pole-formularza profil-pole">
                        <span>Motyw</span>
                        <select name="motyw">
                            <option value="jasny" <?= ($u['motyw'] ?? 'jasny') === 'jasny' ? 'selected' : '' ?>>Jasny</option>
                            <option value="kontrast" <?= ($u['motyw'] ?? '') === 'kontrast' ? 'selected' : '' ?>>Wysoki kontrast</option>
                        </select>
                    </label>

                    <label class="pole-formularza profil-pole">
                        <span>Widok startowy</span>
                        <select name="domyslny_modul">
                            <option value="zakladki" <?= ($u['domyslny_modul'] ?? 'zakladki') === 'zakladki' ? 'selected' : '' ?>>Zakladki</option>
                            <option value="profil" <?= ($u['domyslny_modul'] ?? '') === 'profil' ? 'selected' : '' ?>>Profil</option>
                            <option value="widok2" <?= ($u['domyslny_modul'] ?? '') === 'widok2' ? 'selected' : '' ?>>Widok 2</option>
                        </select>
                    </label>

                    <label class="pole-formularza profil-pole">
                        <span>Domyslna kategoria zakladek</span>
                        <select name="domyslna_kategoria">
                            <option value="pierwsza" <?= ($u['domyslna_kategoria'] ?? 'pierwsza') === 'pierwsza' ? 'selected' : '' ?>>Pierwsza kategoria</option>
                            <option value="ostatnia" <?= ($u['domyslna_kategoria'] ?? '') === 'ostatnia' ? 'selected' : '' ?>>Ostatnio otwarta</option>
                        </select>
                    </label>

                    <div class="profil-kolory-tla">
                        <label class="pole-formularza profil-pole profil-pole-kolor">
                            <span>Tlo zakladek</span>
                            <input type="hidden" name="kolor_tla_zakladki" value="<?= esc($kolorTlaZakladki) ?>" data-pickr-wartosc>
                            <button type="button" class="profil-pickr-przycisk" data-pickr-profil aria-label="Wybierz kolor tla zakladek" style="--profil-kolor: <?= esc($kolorTlaZakladki) ?>;"></button>
                        </label>
                        <label class="pole-formularza profil-pole profil-pole-kolor">
                            <span>Tlo Widok 2</span>
                            <input type="hidden" name="kolor_tla_widok2" value="<?= esc($kolorTlaWidok2) ?>" data-pickr-wartosc>
                            <button type="button" class="profil-pickr-przycisk" data-pickr-profil aria-label="Wybierz kolor tla Widok 2" style="--profil-kolor: <?= esc($kolorTlaWidok2) ?>;"></button>
                        </label>
                    </div>

                    <div class="profil-akcje">
                        <button type="reset" class="przycisk-subtelny profil-przycisk-anuluj">Anuluj</button>
                        <button type="submit" class="przycisk-glowny profil-przycisk-zapisz">Zapisz preferencje</button>
                    </div>
                </div>
            </section>
        </div>
    </form>
</section>
