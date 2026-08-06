<?php

class DashboardController extends Controller
{
    public function index(): void
    {
        Auth::requireLogin();
        $loan = new Loan();

        $kpis              = $loan->kpis();
        $groupCounts       = $loan->groupCounts();
        $byBranch          = $loan->loansByBranch();
        $byMonth           = $loan->loansByMonth();
        $recent            = $loan->recentActivity();
        $paymentsDueToday  = $loan->paymentsDueTodayCount();

        $groups = ['Group 1' => 0, 'Group 2' => 0, 'Group 3' => 0];
        foreach ($groupCounts as $g) { $groups[$g['loan_group']] = (int) $g['total']; }

        // Current-month branch budget overview
        $month = date('Y-m-01');
        $budgetModel = new BranchBudget();
        $spentMap = [];
        foreach ($loan->spentByBranchForMonth($month) as $r) {
            $spentMap[$r['branch_id']] = (float) $r['spent'];
        }
        $budgetOverview = [];
        $companyAllocated = 0.0;
        $companySpent = 0.0;
        foreach ($budgetModel->listForMonth($month) as $r) {
            $allocated = (float) $r['allocated_amount'];
            $spent = $spentMap[$r['branch_id']] ?? 0.0;
            $companyAllocated += $allocated;
            $companySpent += $spent;
            $budgetOverview[] = [
                'branch_name' => $r['branch_name'],
                'allocated'   => $allocated,
                'spent'       => $spent,
                'remaining'   => $allocated - $spent,
            ];
        }

        $this->view('dashboard/index', [
            'kpis'             => $kpis,
            'groups'           => $groups,
            'byBranch'         => $byBranch,
            'byMonth'          => $byMonth,
            'recent'           => $recent,
            'budgetOverview'   => $budgetOverview,
            'companyAllocated' => $companyAllocated,
            'companySpent'     => $companySpent,
            'paymentsDueToday' => $paymentsDueToday,
        ]);
    }
}
