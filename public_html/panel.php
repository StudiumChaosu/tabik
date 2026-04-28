<?php
require_once __DIR__ . '/includes/funkcje.php';
wymagaj_logowania();

$modulZQuery = $_GET['modul'] ?? null;
$dozwolone = ['zakladki', 'profil', 'widok2'];
$aliasy = ['panel' => 'zakladki', 'ustawienia' => 'profil'];

if ($modulZQuery !== null && isset($aliasy[$modulZQuery])) {
    przekieruj('panel.php?modul=' . $aliasy[$modulZQuery]);
}

$domyslnyModul = domyslny_modul_uzytkownika(uzytkownik());
$aktywny_modul = in_array((string) $modulZQuery, $dozwolone, true) ? (string) $modulZQuery : $domyslnyModul;
$tytul = etykieta_modulu($aktywny_modul);
$strona_css = $aktywny_modul === 'zakladki' ? 'zakladki' : '';
$strona_js = $aktywny_modul === 'zakladki' ? 'zakladki' : '';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/menu.php';
?>
<div class="uklad-roboczy">
<div class="obszar-aplikacji">
    <main class="obszar-glowny">
        <?php include __DIR__ . '/modules/' . $aktywny_modul . '.php'; ?>
    </main>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
