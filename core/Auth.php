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
            'id'          => $user['id'],
            'full_name'   => $user['full_name'],
            'username'    => $user['username'],
            'role'        => $user['role'],
            'branch_id'   => $user['branch_id'] ?? null,
            'branch_name' => $user['branch_name'] ?? null,
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

    public static function isBranch(): bool { return self::check() && $_SESSION['user']['role'] === 'Branch'; }

    /**
     * The branch a Branch-role account is locked to. Null for
     * Administrator/Operator (they aren't restricted to one branch).
     */
    public static function branchId(): ?int
    {
        return self::check() && self::isBranch() ? (int) $_SESSION['user']['branch_id'] : null;
    }

    /**
     * Where to send someone right after login / if they hit "/".
     * Branch accounts don't have a Dashboard, so they land on the
     * register instead.
     */
    public static function landingPath(): string
    {
        return self::isBranch() ? '/loans/register' : '/dashboard';
    }

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

    /**
     * Administrator or Operator - i.e. NOT a Branch account. Use this to
     * guard screens Branch accounts shouldn't reach at all (Dashboard,
     * Reports, Branches, Exports, Client management) even if they guess
     * the URL directly.
     */
    public static function requireStaff(): void
    {
        self::requireLogin();
        if (self::isBranch()) {
            http_response_code(403);
            echo '403 - This area is restricted to Administrator/Operator accounts.';
            exit;
        }
    }
}
