<?php
/*
 * Loader wspolnych funkcji Tabik.
 * Plik zachowuje dotychczasowy punkt wejscia require_once, ale logika jest rozbita tematycznie.
 */
require_once __DIR__ . '/bootstrap.php';      // konfiguracja, sesja, PDO, helper schematu bazy
require_once __DIR__ . '/http.php';           // esc(), przekieruj(), JSON body/response, helpery wejscia
require_once __DIR__ . '/security.php';       // token_csrf(), sprawdz_csrf()
require_once __DIR__ . '/routing.php';        // tabik_trasy(), url(), tabik_config_script()
require_once __DIR__ . '/auth.php';           // czy_zalogowany(), wymagaj_logowania(), uzytkownik()
require_once __DIR__ . '/flash.php';          // ustaw_flash(), pobierz_flash()
require_once __DIR__ . '/profile.php';        // profil, avatar, kolory, domyslny modul
require_once __DIR__ . '/zakladki_dane.php';  // dane zakladek: filtry, grupy, lista, eksport
