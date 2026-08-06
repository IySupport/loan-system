<?php

class Database
{
    private static ?PDO $instance = null;

    public static function connect(): PDO
    {
        if (self::$instance === null) {
            $cfg = require APP_ROOT . '/config/database.php';
            // sslmode is required by managed Postgres providers like Neon
            // (they reject plain, unencrypted connections). Defaults to
            // 'prefer' so this stays a no-op against a local Postgres that
            // has no SSL configured.
            $dsn = sprintf(
                'pgsql:host=%s;port=%s;dbname=%s;sslmode=%s',
                $cfg['host'], $cfg['port'], $cfg['name'], $cfg['sslmode']
            );
            try {
                self::$instance = new PDO($dsn, $cfg['user'], $cfg['pass'], [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                http_response_code(500);
                die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
            }
        }
        return self::$instance;
    }
}
