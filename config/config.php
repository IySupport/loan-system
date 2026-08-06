<?php
/**
 * Loads .env (if present) and exposes app-wide constants/helpers.
 */
$rootPath = dirname(__DIR__);

$envFile = $rootPath . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $value = trim($value, "\"'");
        putenv("$key=$value");
        $_ENV[$key] = $value;
    }
}

if (!function_exists('env')) {
    function env(string $key, $default = null) {
        $value = getenv($key);
        return $value === false ? $default : $value;
    }
}

define('APP_ROOT', $rootPath);
define('APP_NAME', env('APP_NAME', 'Loan Management'));
define('APP_URL', rtrim(env('APP_URL', ''), '/'));

date_default_timezone_set('Africa/Johannesburg');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', env('APP_DEBUG', '0'));
