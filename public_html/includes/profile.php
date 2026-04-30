<?php
/*
 * Profil uzytkownika: kolumny profilu, kolory personalizacji,
 * avatar, nazwa wyswietlana oraz domyslny modul aplikacji.
 */

/* DANE - PROFIL UZYTKOWNIKA */
function ensure_uzytkownicy_profil_columns(): void
{
    ensure_uzytkownicy_domyslny_modul_column();
    $kolumny = [
        'avatar' => "ALTER TABLE uzytkownicy ADD COLUMN avatar VARCHAR(255) NULL DEFAULT NULL AFTER email",
        'idkolor_zak' => "ALTER TABLE uzytkownicy ADD COLUMN idkolor_zak VARCHAR(7) NULL DEFAULT NULL AFTER domyslny_modul",
        'idkolor_gru' => "ALTER TABLE uzytkownicy ADD COLUMN idkolor_gru VARCHAR(7) NULL DEFAULT NULL AFTER idkolor_zak",
        'idkolor_prom' => "ALTER TABLE uzytkownicy ADD COLUMN idkolor_prom VARCHAR(7) NULL DEFAULT NULL AFTER idkolor_gru",
    ];

    foreach ($kolumny as $kolumna => $sql) {
        if (czy_kolumna_istnieje('uzytkownicy', $kolumna)) {
            continue;
        }

        try {
            baza()->exec($sql);
        } catch (Throwable $e) {
            // Kolumna mogla zostac dodana rownolegle albo baza nie pozwala na ALTER.
        }
    }
}

function kolor_hex_lub_domyslny(mixed $kolor, string $domyslny, bool $dopuszczajAlfe = false): string
{
    $wartosc = strtolower(trim((string) $kolor));
    $wzorzec = $dopuszczajAlfe ? '/^#[0-9a-f]{6}([0-9a-f]{2})?$/' : '/^#[0-9a-f]{6}$/';
    return preg_match($wzorzec, $wartosc) ? $wartosc : $domyslny;
}

function nazwa_wyswietlana_uzytkownika(array $uzytkownik): string
{
    $nazwa = trim((string) ($uzytkownik['imie'] ?? ''));
    if ($nazwa !== '') {
        return $nazwa;
    }

    $email = trim((string) ($uzytkownik['email'] ?? ''));
    return $email !== '' ? $email : 'Uzytkownik';
}

function inicjaly_uzytkownika(array $uzytkownik): string
{
    $zrodlo = nazwa_wyswietlana_uzytkownika($uzytkownik);
    if (function_exists('mb_substr')) {
        $inicjaly = mb_strtoupper(mb_substr($zrodlo, 0, 2, 'UTF-8'), 'UTF-8');
    } else {
        $inicjaly = strtoupper(substr($zrodlo, 0, 2));
    }

    return trim($inicjaly) !== '' ? $inicjaly : 'TY';
}

function sciezka_awatara(?string $avatar): string
{
    $avatar = trim((string) $avatar);
    if ($avatar === '' || str_contains($avatar, '..') || str_starts_with($avatar, '/') || preg_match('~^[a-z][a-z0-9+.-]*://~i', $avatar)) {
        return '';
    }

    return $avatar;
}
function ensure_uzytkownicy_domyslny_modul_column(): void
{
    if (czy_kolumna_istnieje('uzytkownicy', 'domyslny_modul')) {
        return;
    }

    baza()->exec("ALTER TABLE uzytkownicy ADD COLUMN domyslny_modul VARCHAR(32) NOT NULL DEFAULT 'zakladki' AFTER domyslna_kategoria");
}

function domyslny_modul_uzytkownika(array $uzytkownik): string
{
    $modul = (string) ($uzytkownik['domyslny_modul'] ?? 'zakladki');
    return in_array($modul, ['zakladki', 'profil', 'widok2'], true) ? $modul : 'zakladki';
}

function etykieta_modulu(string $modul): string
{
    return match ($modul) {
        'zakladki' => 'Zakladki',
        'profil' => 'Profil',
        'widok2' => 'Widok 2',
        default => 'Zakladki',
    };
}
