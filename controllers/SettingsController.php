<?php

class SettingsController extends Controller
{
    public function index(): void
    {
        Auth::requireLogin();
        $this->view('settings/index', ['csrf' => $this->csrfToken()]);
    }

    public function changePassword(): void
    {
        Auth::requireLogin();
        if (!$this->verifyCsrf()) $this->json(['success' => false, 'message' => 'Invalid session token.'], 419);

        $current = (string) $this->input('current_password', '');
        $new     = (string) $this->input('new_password', '');

        $userModel = new User();
        $user = $userModel->find(Auth::user()['id']);

        if (!password_verify($current, $user['password_hash'])) {
            $this->json(['success' => false, 'message' => 'Current password is incorrect.'], 422);
        }
        if (strlen($new) < 6) {
            $this->json(['success' => false, 'message' => 'New password must be at least 6 characters.'], 422);
        }

        $userModel->resetPassword($user['id'], $new);
        $this->json(['success' => true]);
    }
}
