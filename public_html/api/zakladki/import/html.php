<?php
require_once __DIR__ . '/../wspolne.php';
sprawdz_csrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['token_csrf'] ?? null)); $plik = pobierz_plik_importu('plik'); $zaw = file_get_contents($plik['tmp_name']) ?: '';
if (!preg_match('/<script[^>]+id="dane-zakladek"[^>]*>(.*?)<\/script>/si', $zaw, $m)) odpowiedz_json(['sukces'=>false,'komunikat'=>'Plik HTML nie zawiera pakietu danych.'],422);
$tmpPlik = sys_get_temp_dir() . '/tabik_import_' . uniqid('', true) . '.json';
file_put_contents($tmpPlik, html_entity_decode(trim($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
$_FILES['plik']['tmp_name'] = $tmpPlik;
$_FILES['plik']['name'] = basename($tmpPlik);
require __DIR__ . '/json.php';
