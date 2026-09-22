<?php

class UserController extends Controller
{
    public function index(): void
    {
        Auth::requireAdmin();
        $users = (new User())->allWithBranchName();
        $branches = (new Branch())->activeBranches();
        $this->view('users/index', ['users' => $users, 'branches' => $branches, 'csrf' => $this->csrfToken()]);
    }

    private function validateRoleAndBranch(string $role, $branchId): array
    {
        $errors = [];
        if (!in_array($role, ['Administrator', 'Operator', 'Branch'], true)) {
            $errors['role'] = 'Invalid role.';
        } elseif ($role === 'Branch' && empty($branchId)) {
            $errors['branch_id'] = 'A Branch account must be assigned a branch.';
        }
        return $errors;
    }

    public function store(): void
    {
        Auth::requireAdmin();
        if (!$this->verifyCsrf()) $this->json(['success' => false, 'message' => 'Invalid session token.'], 419);

        $fullName = trim($this->input('full_name', ''));
        $username = trim($this->input('username', ''));
        $password = (string) $this->input('password', '');
        $role     = $this->input('role', 'Operator');
        $branchId = $this->input('branch_id', '');

        $errors = $this->validateRoleAndBranch($role, $branchId);
        if ($fullName === '') $errors['full_name'] = 'Full name is required.';
        if ($username === '') $errors['username'] = 'Username is required.';
        if (strlen($password) < 6) $errors['password'] = 'Password must be at least 6 characters.';

        $userModel = new User();
        if ($username !== '' && $userModel->usernameExists($username)) {
            $errors['username'] = 'This username is already taken.';
        }
        if (!empty($errors)) $this->json(['success' => false, 'errors' => $errors], 422);

        $id = $userModel->create([
            'full_name' => $fullName, 'username' => $username, 'password' => $password,
            'role' => $role, 'branch_id' => $branchId ?: null, 'status' => 'Active',
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
        $branchId = $this->input('branch_id', '');
        $status   = $this->input('status', 'Active');

        $errors = $this->validateRoleAndBranch($role, $branchId);
        if ($fullName === '') $errors['full_name'] = 'Full name is required.';
        if ($username === '') $errors['username'] = 'Username is required.';

        $userModel = new User();
        if ($username !== '' && $userModel->usernameExists($username, (int) $id)) {
            $errors['username'] = 'This username is already taken.';
        }
        if (!empty($errors)) $this->json(['success' => false, 'errors' => $errors], 422);

        $ok = $userModel->update((int) $id, [
            'full_name' => $fullName, 'username' => $username, 'role' => $role,
            'branch_id' => $branchId ?: null, 'status' => $status,
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
