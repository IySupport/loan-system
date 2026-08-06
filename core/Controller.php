<?php

abstract class Controller
{
    protected function view(string $view, array $data = [], ?string $layout = 'layouts/app'): void
    {
        extract($data);
        $viewFile = APP_ROOT . '/views/' . $view . '.php';
        if (!file_exists($viewFile)) {
            http_response_code(500);
            die("View not found: {$view}");
        }

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        if ($layout === null) {
            echo $content;
            return;
        }

        $layoutFile = APP_ROOT . '/views/' . $layout . '.php';
        require $layoutFile;
    }

    protected function json($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function input(string $key, $default = null)
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . APP_URL . $path);
        exit;
    }

    protected function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    protected function verifyCsrf(): bool
    {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        return !empty($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }
}
