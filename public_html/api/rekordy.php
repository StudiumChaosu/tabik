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

if ($akcja === 'eksport_html') {
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="tabik-eksport-' . date('Y-m-d-His') . '.html"');
    ?>
<!doctype html>
<html lang="pl"><head><meta charset="utf-8"><title>Eksport Tabik</title></head><body>
<h1>Eksport zakladek Tabik</h1>
<script id="dane-zakladek" type="application/json"><?= json_encode($pakiet, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<ul>
<?php foreach ($dane as $zakladka): ?>
<li><a href="<?= esc($zakladka['adres_url']) ?>"><?= esc($zakladka['tytul']) ?></a></li>
<?php endforeach; ?>
</ul>
</body></html>
<?php
    exit;
}

odpowiedz_json(['sukces' => false, 'komunikat' => 'Nieznana akcja.'], 404);
