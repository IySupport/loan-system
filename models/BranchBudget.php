<?php

class BranchBudget extends Model
{
    protected string $table = 'branch_budgets';

    // public function find(int $branchId, string $month): ?array
    // {
    public function findBudget(int $branchId, string $month): ?array
{
        $row = $this->query(
            "SELECT * FROM branch_budgets WHERE branch_id = :b AND budget_month = :m",
            ['b' => $branchId, 'm' => $month]
        )->fetch();
        return $row ?: null;
    }

    /**
     * Set (create or update) the allocated amount for a branch for a given month.
     */
    public function upsert(int $branchId, string $month, float $amount, ?int $userId): bool
    {
        return $this->query(
            "INSERT INTO branch_budgets (branch_id, budget_month, allocated_amount, created_by)
             VALUES (:b, :m, :a, :u)
             ON CONFLICT (branch_id, budget_month)
             DO UPDATE SET allocated_amount = EXCLUDED.allocated_amount, updated_at = NOW()",
            ['b' => $branchId, 'm' => $month, 'a' => $amount, 'u' => $userId]
        )->rowCount() >= 0;
    }

    /**
     * All active branches with their allocated amount for a given month (0 if not yet set).
     */
    public function listForMonth(string $month): array
    {
        return $this->query(
            "SELECT b.id AS branch_id, b.branch_name, COALESCE(bb.allocated_amount, 0) AS allocated_amount, bb.id AS budget_id
             FROM branches b
             LEFT JOIN branch_budgets bb ON bb.branch_id = b.id AND bb.budget_month = :m
             WHERE b.status = 'Active'
             ORDER BY b.branch_name",
            ['m' => $month]
        )->fetchAll();
    }

    public function totalAllocatedForMonth(string $month): float
    {
        return (float) $this->query(
            "SELECT COALESCE(SUM(allocated_amount), 0) AS total FROM branch_budgets WHERE budget_month = :m",
            ['m' => $month]
        )->fetch()['total'];
    }
}
