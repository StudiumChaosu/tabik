<?php
require_once __DIR__ . '/../wspolne.php';

$dane = dane_wejscia_api();
sprawdz_csrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($dane['token_csrf'] ?? null));

$id = (int) ($dane['id'] ?? 0);
$kolor = trim((string) ($dane['kolor'] ?? ''));

if ($id <= 0 || !preg_match('/^#[0-9a-fA-F]{6}$/', $kolor)) {
    odpowiedz_json(['sukces' => false, 'komunikat' => 'Nieprawidlowy kolor grupy.'], 422);
}

if (!upewnij_kolumne_koloru_grupy()) {
    odpowiedz_json([
        'sukces' => false,
        'komunikat' => 'Brak kolumny kolor w tabeli grupy_zakladek. Uruchom migracje SQL dodajaca kolumne kolor VARCHAR(7).',
    ], 500);
}

$stmt = baza()->prepare('UPDATE grupy_zakladek SET kolor = :kolor, data_aktualizacji = NOW() WHERE id = :id AND id_uzytkownika = :u');
$stmt->execute(['kolor' => strtolower($kolor), 'id' => $id, 'u' => id_uzytkownika()]);

odpowiedz_json(['sukces' => true, 'komunikat' => 'Kolor grupy zostal zapisany.', 'kolor' => strtolower($kolor)]);
