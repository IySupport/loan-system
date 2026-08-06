<?php

class BudgetController extends Controller
{
    private function normalizeMonth(string $month): string
    {
        $ts = strtotime($month);
        if (!$ts) $ts = time();
        return date('Y-m-01', $ts);
    }

    // Admin-only: Branch Budgets management screen
    public function index(): void
    {
        Auth::requireAdmin();
        $this->view('budgets/index', [
            'branches'     => (new Branch())->activeBranches(),
            'currentMonth' => date('Y-m-01'),
            'csrf'         => $this->csrfToken(),
        ]);
    }

    // AJAX (admin): GET /budgets/list?month=YYYY-MM-DD
    public function list(): void
    {
        Auth::requireAdmin();
        $month = $this->normalizeMonth($this->input('month', date('Y-m-01')));

        $budgetModel = new BranchBudget();
        $loanModel   = new Loan();

        $spentMap = [];
        foreach ($loanModel->spentByBranchForMonth($month) as $r) {
            $spentMap[$r['branch_id']] = (float) $r['spent'];
        }

        $totalAllocated = 0.0;
        $totalSpent     = 0.0;
        $data = [];
        foreach ($budgetModel->listForMonth($month) as $r) {
            $allocated = (float) $r['allocated_amount'];
            $spent     = $spentMap[$r['branch_id']] ?? 0.0;
            $totalAllocated += $allocated;
            $totalSpent     += $spent;
            $data[] = [
                'branch_id'   => $r['branch_id'],
                'branch_name' => $r['branch_name'],
                'allocated'   => $allocated,
                'spent'       => $spent,
                'remaining'   => $allocated - $spent,
            ];
        }

        $this->json([
            'success'  => true,
            'month'    => $month,
            'branches' => $data,
            'company'  => [
                'allocated' => $totalAllocated,
                'spent'     => $totalSpent,
                'remaining' => $totalAllocated - $totalSpent,
            ],
        ]);
    }

    // Admin-only: POST /budgets/save
    public function save(): void
    {
        Auth::requireAdmin();
        if (!$this->verifyCsrf()) {
            $this->json(['success' => false, 'message' => 'Invalid session token.'], 419);
        }

        $branchId = (int) $this->input('branch_id');
        $month    = $this->normalizeMonth($this->input('month', date('Y-m-01')));
        $amount   = $this->input('allocated_amount');

        if (!$branchId || $amount === null || $amount === '' || !is_numeric($amount) || (float) $amount < 0) {
            $this->json(['success' => false, 'message' => 'A valid branch and a non-negative amount are required.'], 422);
        }

        (new BranchBudget())->upsert($branchId, $month, (float) $amount, Auth::user()['id']);
        $this->json(['success' => true]);
    }

    // Any logged-in user: GET /budgets/status?branch_id=X&month=YYYY-MM-DD
    // Used by the Capture Loan screen to show live Allocated/Spent/Remaining
    // for the selected branch, plus company-wide totals.
    public function status(): void
    {
        Auth::requireLogin();
        $branchId = (int) $this->input('branch_id');
        $month    = $this->normalizeMonth($this->input('month', date('Y-m-01')));

        if (!$branchId) {
            $this->json(['success' => false, 'message' => 'branch_id is required.'], 422);
        }

        $budgetModel = new BranchBudget();
        $loanModel   = new Loan();
        $budget = $budgetModel->findBudget($branchId, $month);
        // $budget    = $budgetModel->find($branchId, $month);
        $allocated = $budget ? (float) $budget['allocated_amount'] : 0.0;
        $spent     = $loanModel->spentForBranchMonth($branchId, $month);

        $companyAllocated = $budgetModel->totalAllocatedForMonth($month);
        $companySpent = 0.0;
        foreach ($loanModel->spentByBranchForMonth($month) as $r) {
            $companySpent += (float) $r['spent'];
        }

        $this->json([
            'success' => true,
            'month'   => $month,
            'branch'  => [
                'allocated' => $allocated,
                'spent'     => $spent,
                'remaining' => $allocated - $spent,
            ],
            'company' => [
                'allocated' => $companyAllocated,
                'spent'     => $companySpent,
                'remaining' => $companyAllocated - $companySpent,
            ],
        ]);
    }
}
