<?php

class BranchController extends Controller
{
    public function index(): void
    {
        Auth::requireLogin();
        $branches = (new Branch())->all('branch_name ASC');
        $this->view('branches/index', ['branches' => $branches, 'csrf' => $this->csrfToken()]);
    }

    public function store(): void
    {
        Auth::requireLogin();
        if (!$this->verifyCsrf()) $this->json(['success' => false, 'message' => 'Invalid session token.'], 419);

        $name = trim($this->input('branch_name', ''));
        if ($name === '') $this->json(['success' => false, 'message' => 'Branch name is required.'], 422);

        $branchModel = new Branch();
        if ($branchModel->nameExists($name)) {
            $this->json(['success' => false, 'message' => 'A branch with this name already exists.'], 422);
        }
        $id = $branchModel->create(['branch_name' => $name, 'status' => 'Active']);
        $this->json(['success' => true, 'id' => $id]);
    }

    public function update(string $id): void
    {
        Auth::requireLogin();
        if (!$this->verifyCsrf()) $this->json(['success' => false, 'message' => 'Invalid session token.'], 419);

        $name = trim($this->input('branch_name', ''));
        $status = $this->input('status', 'Active');
        if ($name === '') $this->json(['success' => false, 'message' => 'Branch name is required.'], 422);

        $branchModel = new Branch();
        if ($branchModel->nameExists($name, (int) $id)) {
            $this->json(['success' => false, 'message' => 'A branch with this name already exists.'], 422);
        }
        $ok = $branchModel->update((int) $id, ['branch_name' => $name, 'status' => $status]);
        $this->json(['success' => $ok]);
    }

    public function deactivate(string $id): void
    {
        Auth::requireLogin();
        if (!$this->verifyCsrf()) $this->json(['success' => false, 'message' => 'Invalid session token.'], 419);
        $branchModel = new Branch();
        $branch = $branchModel->find((int) $id);
        if (!$branch) $this->json(['success' => false, 'message' => 'Branch not found.'], 404);
        $newStatus = $branch['status'] === 'Active' ? 'Inactive' : 'Active';
        $ok = $branchModel->update((int) $id, ['branch_name' => $branch['branch_name'], 'status' => $newStatus]);
        $this->json(['success' => $ok, 'status' => $newStatus]);
    }
}
