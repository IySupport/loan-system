<?php

class UserController extends Controller
{
    public function index(): void
    {
        Auth::requireAdmin();
        $users = (new User())->all('full_name ASC');
        $this->view('users/index', ['users' => $users, 'csrf' => $this->csrfToken()]);
    }

    public function store(): void
    {
        Auth::requireAdmin();
        if (!$this->verifyCsrf()) $this->json(['success' => false, 'message' => 'Invalid session token.'], 419);

        $errors = [];
        $fullName = trim($this->input('full_name', ''));
        $username = trim($this->input('username', ''));
        $password = (string) $this->input('password', '');
        $role     = $this->input('role', 'Operator');

        if ($fullName === '') $errors['full_name'] = 'Full name is required.';
        if ($username === '') $errors['username'] = 'Username is required.';
        if (strlen($password) < 6) $errors['password'] = 'Password must be at least 6 characters.';
        if (!in_array($role, ['Administrator', 'Operator'], true)) $errors['role'] = 'Invalid role.';

        $userModel = new User();
        if ($username !== '' && $userModel->usernameExists($username)) {
            $errors['username'] = 'This username is already taken.';
        }
        if (!empty($errors)) $this->json(['success' => false, 'errors' => $errors], 422);

        $id = $userModel->create([
            'full_name' => $fullName, 'username' => $username, 'password' => $password,
            'role' => $role, 'status' => 'Active',
        ]);
        $this->json(['success' => true, 'id' => $id]);
    }

    public function update(string $id): void
    {
        Auth::requireAdmin();
        if (!$this->verifyCsrf()) $this->json(['success' => false, 'message' => 'Invalid session token.'], 419);

        $fullName = trim($this->input('full_name', ''));
        $username = trim($this->input('username', ''));
        $role     = $this->input('role', 'Operator');
        $status   = $this->input('status', 'Active');

        $errors = [];
        if ($fullName === '') $errors['full_name'] = 'Full name is required.';
        if ($username === '') $errors['username'] = 'Username is required.';

        $userModel = new User();
        if ($username !== '' && $userModel->usernameExists($username, (int) $id)) {
            $errors['username'] = 'This username is already taken.';
        }
        if (!empty($errors)) $this->json(['success' => false, 'errors' => $errors], 422);

        $ok = $userModel->update((int) $id, [
            'full_name' => $fullName, 'username' => $username, 'role' => $role, 'status' => $status,
        ]);
        $this->json(['success' => $ok]);
    }

    public function resetPassword(string $id): void
    {
        Auth::requireAdmin();
        if (!$this->verifyCsrf()) $this->json(['success' => false, 'message' => 'Invalid session token.'], 419);
        $password = (string) $this->input('password', '');
        if (strlen($password) < 6) $this->json(['success' => false, 'message' => 'Password must be at least 6 characters.'], 422);
        $ok = (new User())->resetPassword((int) $id, $password);
        $this->json(['success' => $ok]);
    }

    public function deactivate(string $id): void
    {
        Auth::requireAdmin();
        if (!$this->verifyCsrf()) $this->json(['success' => false, 'message' => 'Invalid session token.'], 419);
        $userModel = new User();
        $user = $userModel->find((int) $id);
        if (!$user) $this->json(['success' => false, 'message' => 'User not found.'], 404);
        $newStatus = $user['status'] === 'Active' ? 'Inactive' : 'Active';
        $ok = $userModel->setStatus((int) $id, $newStatus);
        $this->json(['success' => $ok, 'status' => $newStatus]);
    }
}
