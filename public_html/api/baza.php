<?php
ob_start();
require_once __DIR__ . '/../includes/funkcje.php';

if (!defined('TABIK_API_BOOTSTRAP')) {
    define('TABIK_API_BOOTSTRAP', true);

    ini_set('display_errors', '0');
    error_reporting(E_ALL);

    function tabik_api_odpowiedz_awaria(string $komunikat = 'Wystapil blad serwera. Sprobuj ponownie za chwile.', int $status = 500): never
    {
        if (ob_get_length() !== false) {
            @ob_clean();
        }

        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
        }

        echo json_encode([
            'sukces' => false,
            'komunikat' => $komunikat,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    set_error_handler(static function (int $poziom, string $komunikat, string $plik, int $linia): bool {
        if (!(error_reporting() & $poziom)) {
            return false;
        }
        throw new ErrorException($komunikat, 0, $poziom, $plik, $linia);
    });

    set_exception_handler(static function (Throwable $wyjatek): void {
        error_log('[Tabik API] ' . $wyjatek->getMessage() . ' w ' . $wyjatek->getFile() . ':' . $wyjatek->getLine());
        tabik_api_odpowiedz_awaria();
    });

    register_shutdown_function(static function (): void {
        $blad = error_get_last();
        if (!$blad) {
            return;
        }

        $fatalne = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
        if (!in_array($blad['type'], $fatalne, true)) {
            return;
        }

        error_log('[Tabik API fatal] ' . $blad['message'] . ' w ' . $blad['file'] . ':' . $blad['line']);
        tabik_api_odpowiedz_awaria();
    });
}
