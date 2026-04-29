<?php
require_once __DIR__ . '/baza.php';
wymagaj_logowania();

$akcja = $_GET['akcja'] ?? '';
$dane = pobierz_zakladki_do_eksportu(id_uzytkownika());
$pakiet = ['zakladki' => $dane, 'data_eksportu' => date('c'), 'aplikacja' => 'Tabik'];

if ($akcja === 'eksport_json') {
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="tabik-eksport-' . date('Y-m-d-His') . '.json"');
    echo json_encode($pakiet, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

odpowiedz_json(['sukces' => false, 'komunikat' => 'Nieznana akcja.'], 404);
