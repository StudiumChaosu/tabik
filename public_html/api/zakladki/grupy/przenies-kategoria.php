<?php
require_once __DIR__ . '/../wspolne.php';

$dane = dane_wejscia_api();
sprawdz_csrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($dane['token_csrf'] ?? null));

$id = (int) ($dane['id'] ?? 0);
$idKategorii = int_lub_null($dane['id_kategorii'] ?? null);

if ($id <= 0) {
    odpowiedz_json(['sukces' => false, 'komunikat' => 'Nieprawidlowa grupa.'], 422);
}

if (!grupy_maja_id_kategorii()) {
    odpowiedz_json([
        'sukces' => false,
        'komunikat' => 'Brak kolumny id_kategorii w tabeli grupy_zakladek.',
    ], 500);
}

$stmt = baza()->prepare('SELECT id FROM grupy_zakladek WHERE id = :id AND id_uzytkownika = :u LIMIT 1');
$stmt->execute(['id' => $id, 'u' => id_uzytkownika()]);
if (!$stmt->fetchColumn()) {
    odpowiedz_json(['sukces' => false, 'komunikat' => 'Nie znaleziono grupy.'], 404);
}

if ($idKategorii !== null) {
    $stmt = baza()->prepare('SELECT id FROM kategorie_zakladek WHERE id = :id AND id_uzytkownika = :u LIMIT 1');
    $stmt->execute(['id' => $idKategorii, 'u' => id_uzytkownika()]);
    if (!$stmt->fetchColumn()) {
        odpowiedz_json(['sukces' => false, 'komunikat' => 'Nie znaleziono kategorii docelowej.'], 404);
    }
}

$stmt = baza()->prepare(
    'SELECT COALESCE(MAX(kolejnosc), -1) + 1
     FROM grupy_zakladek
     WHERE id_uzytkownika = :u AND ' . ($idKategorii ? 'id_kategorii = :k' : 'id_kategorii IS NULL')
);
$parametry = ['u' => id_uzytkownika()];
if ($idKategorii) {
    $parametry['k'] = $idKategorii;
}
$stmt->execute($parametry);
$kolejnosc = (int) $stmt->fetchColumn();

baza()->beginTransaction();
try {
    baza()->prepare(
        'UPDATE grupy_zakladek
         SET id_kategorii = :k, kolejnosc = :kol, data_aktualizacji = NOW()
         WHERE id = :id AND id_uzytkownika = :u'
    )->execute(['k' => $idKategorii, 'kol' => $kolejnosc, 'id' => $id, 'u' => id_uzytkownika()]);

    baza()->prepare(
        'UPDATE zakladki
         SET id_kategorii = :k, data_aktualizacji = NOW()
         WHERE id_grupy = :id AND id_uzytkownika = :u'
    )->execute(['k' => $idKategorii, 'id' => $id, 'u' => id_uzytkownika()]);

    baza()->commit();
} catch (Throwable $e) {
    baza()->rollBack();
    odpowiedz_json(['sukces' => false, 'komunikat' => 'Nie udalo sie przeniesc grupy.'], 500);
}

odpowiedz_json(['sukces' => true, 'komunikat' => 'Grupa zostala przeniesiona do kategorii.']);
