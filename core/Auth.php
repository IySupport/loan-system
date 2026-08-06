<?php

class Auth
{
    public static function attempt(string $username, string $password): bool
    {
        $userModel = new User();
        $user = $userModel->findByUsername($username);

        if (!$user || $user['status'] !== 'Active' || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'        => $user['id'],
            'full_name' => $user['full_name'],
            'username'  => $user['username'],
            'role'      => $user['role'],
        ];
        return true;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function check(): bool { return isset($_SESSION['user']); }

    public static function user(): ?array { return $_SESSION['user'] ?? null; }

    public static function isAdmin(): bool { return self::check() && $_SESSION['user']['role'] === 'Administrator'; }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: ' . APP_URL . '/login');
            exit;
        }
    }

    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (!self::isAdmin()) {
            http_response_code(403);
            echo '403 - Administrator access required.';
            exit;
        }
    }
}
