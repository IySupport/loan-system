<?php

class Loan extends Model
{
    protected string $table = 'loans';
//findOrCreate()
    // -------------------------------------------------------------
    // Reference number generation: LN-YYYYMMDD-0001 (resets daily)
    //report_status
    // -------------------------------------------------------------
    public function nextReferenceNumber(): string
    {
        $today = date('Y-m-d');

        // $this->db->beginTransaction();
        // try {
        $ownTransaction = !$this->db->inTransaction();

        if ($ownTransaction) {
        $this->db->beginTransaction();
        }

        try {
            $this->query(
                "INSERT INTO daily_counters (counter_date, last_value) VALUES (:d, 0)
                 ON CONFLICT (counter_date) DO NOTHING",
                ['d' => $today]
            );
            $row = $this->query(
                "UPDATE daily_counters SET last_value = last_value + 1
                 WHERE counter_date = :d RETURNING last_value",
                ['d' => $today]
            )->fetch();
            // $this->db->commit();
            if ($ownTransaction) {
                    $this->db->commit();
                }
    } catch (Throwable $e) {
    if ($ownTransaction && $this->db->inTransaction()) {
        $this->db->rollBack();
    }

    throw $e;
}
        $seq = str_pad((string) $row['last_value'], 4, '0', STR_PAD_LEFT);
        return 'LN-' . date('Ymd') . '-' . $seq;
    }

    // -------------------------------------------------------------
    // Create / Update 
    // -------------------------------------------------------------
    public function create(array $d): array
    {
        $this->db->beginTransaction();
         error_log('[TEST 1] Transaction started');
        try {
            $clientModel = new Client();
            $client = $clientModel->findOrCreate([
                'name'           => $d['name'],
                'surname'        => $d['surname'],
                'id_number'      => $d['id_number'],
                'account_number' => $d['account_number'] ?? null,
                'phone'          => $d['phone'] ?? null,
            ]);
           error_log('[TEST 2] Client ready. ID: ' . $client['id']);
            $reference = $this->nextReferenceNumber();

            // $this->query(
            //     "INSERT INTO loans
            //         (reference_number, client_id, branch_id, loan_status_id, repayment_status_id,
            //          amount, action_date, notes, created_by)
            //      VALUES
            //         (:reference_number, :client_id, :branch_id, :loan_status_id, :repayment_status_id,
            //          :amount, :action_date, :notes, :created_by)",
            //     [
            //         'reference_number'    => $reference,
            //         'client_id'           => $client['id'],
            //         'branch_id'           => $d['branch_id'],
            //         'loan_status_id'      => $d['loan_status_id'],
            //         'repayment_status_id' => $d['repayment_status_id'],
            //         'amount'              => $d['amount'],
            //         'action_date'         => $d['action_date'],
            //         'notes'               => $d['notes'] ?? null,
            //         'created_by'          => $d['created_by'] ?? null,
            //     ]
            // );
 error_log('[TEST 3] Reference generated: ' . $reference);

            // date_loaded drives which month's branch budget this loan counts
            // against, and must be editable so older loans can be captured
            // retroactively. It is stored in loans.created_at (aliased as
            // date_loaded in loan_register_view). Falls back to "now" if not
            // supplied, so this stays backward compatible.
            $dateLoaded = !empty($d['date_loaded']) ? $d['date_loaded'] : date('Y-m-d');

            // interest_amount / amount_due are NOT inserted here: they are
            // generated columns computed by Postgres from `amount` (see
            // database/migration_workplace_repayment.sql), so the admin
            // can never set or override them.
            // $this->query(
            //     "INSERT INTO loans
            //         (reference_number, client_id, branch_id, loan_status_id, repayment_status_id,
            //          amount, workplace_name, work_contact, action_date, notes, created_by, created_at)
            //      VALUES
            //         (:reference_number, :client_id, :branch_id, :loan_status_id, :repayment_status_id,
            //          :amount, :workplace_name, :work_contact, :action_date, :notes, :created_by, :date_loaded)",
            //     [
            //         'reference_number'    => $reference,
            //         'client_id'           => $client['id'],
            //         'branch_id'           => $d['branch_id'],
            //         'loan_status_id'      => $d['loan_status_id'],
            //         'repayment_status_id' => $d['repayment_status_id'],
            //         'amount'              => $d['amount'],
            //         'workplace_name'      => $d['workplace_name'] ?? null,
            //         'work_contact'        => $d['work_contact'] ?? null,
            //         'action_date'         => $d['action_date'],
            //         'notes'               => $d['notes'] ?? null,
            //         'created_by'          => $d['created_by'] ?? null,
            //         'date_loaded'         => $dateLoaded,
            //     ]
            // );

            // $loanId = (int) $this->db->lastInsertId('loans_id_seq');
            $stmt = $this->query(
                "INSERT INTO loans
                    (reference_number, client_id, branch_id, loan_status_id,
                    repayment_status_id, amount, workplace_name, work_contact,
                    action_date, notes, created_by, created_at)
                VALUES
                    (:reference_number, :client_id, :branch_id, :loan_status_id,
                    :repayment_status_id, :amount, :workplace_name, :work_contact,
                    :action_date, :notes, :created_by, :date_loaded)
                RETURNING id",
                [
                    'reference_number'    => $reference,
                    'client_id'           => $client['id'],
                    'branch_id'           => $d['branch_id'],
                    'loan_status_id'      => $d['loan_status_id'],
                    'repayment_status_id' => $d['repayment_status_id'],
                    'amount'              => $d['amount'],
                    'workplace_name'      => $d['workplace_name'] ?: null,
                    'work_contact'        => $d['work_contact'] ?: null,
                    'action_date'         => $d['action_date'],
                    'notes'               => $d['notes'] ?: null,
                    'created_by'          => $d['created_by'] ?? null,
                    'date_loaded'         => $dateLoaded,
                ]
            );

            $loanId = (int) $stmt->fetchColumn();
             error_log('[TEST 4] Loan inserted. ID: ' . $loanId);
            $this->db->commit();
             error_log('[TEST 5] Transaction committed');
            $loanCount = (new Client())->loanCount($client['id']);

            return [
                'loan_id'          => $loanId,
                'reference_number' => $reference,
                'client'           => $client,
                'loan_count'       => $loanCount,
                'group'            => Client::groupForCount($loanCount),
            ];
        // } catch (Exception $e) {
        //     $this->db->rollBack();
        //     throw $e;
        // }
        } catch (Throwable $e) {
    if ($this->db->inTransaction()) {
        $this->db->rollBack();
    }

    error_log(
        'Loan::create original error: ' .
        $e->getMessage() .
        ' in ' . $e->getFile() .
        ':' . $e->getLine()
    );
    error_log('[LOAN ERROR] ' . $e->getMessage());
    throw $e;
}
    }

    // public function update(int $id, array $d): bool
    // {
    //     return $this->query(
    //         "UPDATE loans SET branch_id = :branch_id, loan_status_id = :loan_status_id,
    //             repayment_status_id = :repayment_status_id, amount = :amount, action_date = :action_date,
    //             notes = :notes, updated_at = NOW()
    //          WHERE id = :id",
    //         [
    //             'branch_id'           => $d['branch_id'],
    //             'loan_status_id'      => $d['loan_status_id'],
    //             'repayment_status_id' => $d['repayment_status_id'],
    //             'amount'              => $d['amount'],
    //             'action_date'         => $d['action_date'],
    //             'notes'               => $d['notes'] ?? null,
    //             'id'                  => $id,
    //         ]
    //     )->rowCount() >= 0;
    // }
public function update(int $id, array $d): bool
    {
        // interest_amount / amount_due are never written here - they are
        // generated columns and recompute automatically the moment
        // `amount` changes below.
        return $this->query(
            "UPDATE loans SET branch_id = :branch_id, loan_status_id = :loan_status_id,
                repayment_status_id = :repayment_status_id, amount = :amount,
                workplace_name = :workplace_name, work_contact = :work_contact,
                action_date = :action_date,
                notes = :notes, created_at = :date_loaded, updated_at = NOW()
             WHERE id = :id",
            [
                'branch_id'           => $d['branch_id'],
                'loan_status_id'      => $d['loan_status_id'],
                'repayment_status_id' => $d['repayment_status_id'],
                'amount'              => $d['amount'],
                'workplace_name'      => $d['workplace_name'] ?? null,
                'work_contact'        => $d['work_contact'] ?? null,
                'action_date'         => $d['action_date'],
                'notes'               => $d['notes'] ?? null,
                'date_loaded'         => $d['date_loaded'],
                'id'                  => $id,
            ]
        )->rowCount() >= 0;
    }
    
    public function findFull(int $id): ?array
    {
        $row = $this->query("SELECT * FROM loan_register_view WHERE id = :id", ['id' => $id])->fetch();
        return $row ?: null;
    }

    // -------------------------------------------------------------
    // Loans for a single client (used by the Clients admin page to show
    // loan history in the View Client modal)
    // -------------------------------------------------------------
    public function forClient(int $clientId): array
    {
        return $this->query(
            "SELECT * FROM loan_register_view WHERE client_id = :id ORDER BY date_loaded DESC",
            ['id' => $clientId]
        )->fetchAll();
    }

    // -------------------------------------------------------------
    // Register listing (server-side, used by DataTables + Reports + Export)
    // Builds WHERE clause from an associative $filters array.
    // -------------------------------------------------------------
    public function buildFilterClause(array $filters): array
    {
        $where  = [];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = "(name ILIKE :search OR surname ILIKE :search OR id_number ILIKE :search
                         OR account_number ILIKE :search OR reference_number ILIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['branch_id'])) {
            $where[] = "branch_id = :branch_id";
            $params['branch_id'] = $filters['branch_id'];
        }
        if (!empty($filters['loan_group'])) {
            $where[] = "loan_group = :loan_group";
            $params['loan_group'] = $filters['loan_group'];
        }
        if (!empty($filters['loan_status_id'])) {
            $where[] = "loan_status_id = :loan_status_id";
            $params['loan_status_id'] = $filters['loan_status_id'];
        }
        if (!empty($filters['repayment_status_id'])) {
            $where[] = "repayment_status_id = :repayment_status_id";
            $params['repayment_status_id'] = $filters['repayment_status_id'];
        }
        if (!empty($filters['workplace'])) {
            $where[] = "workplace_name ILIKE :workplace";
            $params['workplace'] = '%' . $filters['workplace'] . '%';
        }
        if (!empty($filters['date_loaded_from'])) {
            $where[] = "date_loaded::date >= :date_loaded_from";
            $params['date_loaded_from'] = $filters['date_loaded_from'];
        }
        if (!empty($filters['date_loaded_to'])) {
            $where[] = "date_loaded::date <= :date_loaded_to";
            $params['date_loaded_to'] = $filters['date_loaded_to'];
        }
        if (!empty($filters['action_date_from'])) {
            $where[] = "action_date >= :action_date_from";
            $params['action_date_from'] = $filters['action_date_from'];
        }
        if (!empty($filters['action_date_to'])) {
            $where[] = "action_date <= :action_date_to";
            $params['action_date_to'] = $filters['action_date_to'];
        }
        if (isset($filters['amount_min']) && $filters['amount_min'] !== '') {
            $where[] = "amount >= :amount_min";
            $params['amount_min'] = $filters['amount_min'];
        }
        if (isset($filters['amount_max']) && $filters['amount_max'] !== '') {
            $where[] = "amount <= :amount_max";
            $params['amount_max'] = $filters['amount_max'];
        }
        if (isset($filters['loan_count_min']) && $filters['loan_count_min'] !== '') {
            $where[] = "loan_count >= :loan_count_min";
            $params['loan_count_min'] = $filters['loan_count_min'];
        }
        if (isset($filters['loan_count_max']) && $filters['loan_count_max'] !== '') {
            $where[] = "loan_count <= :loan_count_max";
            $params['loan_count_max'] = $filters['loan_count_max'];
        }
        if (!empty($filters['ids']) && is_array($filters['ids'])) {
            $placeholders = [];
            foreach ($filters['ids'] as $i => $val) {
                $key = "id_{$i}";
                $placeholders[] = ":$key";
                $params[$key] = $val;
            }
            $where[] = "id IN (" . implode(',', $placeholders) . ")";
        }

        $sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        return [$sql, $params];
    }

    private const SORTABLE = [
        'reference_number', 'name', 'surname', 'id_number', 'account_number', 'amount',
        'branch_name', 'loan_count', 'loan_group', 'status', 'repayment_status', 'action_date', 'date_loaded',
        'workplace_name',
    ];

    public function registerList(array $filters, string $orderBy = 'date_loaded', string $orderDir = 'DESC', int $limit = 25, int $offset = 0): array
    {
        [$whereSql, $params] = $this->buildFilterClause($filters);
        $orderBy  = in_array($orderBy, self::SORTABLE, true) ? $orderBy : 'date_loaded';
        $orderDir = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';

        $total = (int) $this->query("SELECT COUNT(*) c FROM loan_register_view {$whereSql}", $params)->fetch()['c'];

        $sql = "SELECT * FROM loan_register_view {$whereSql} ORDER BY {$orderBy} {$orderDir} LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) { $stmt->bindValue(":$k", $v); }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return ['data' => $stmt->fetchAll(), 'total' => $total];
    }

    public function registerAll(array $filters): array
    {
        [$whereSql, $params] = $this->buildFilterClause($filters);
        return $this->query("SELECT * FROM loan_register_view {$whereSql} ORDER BY date_loaded DESC", $params)->fetchAll();
    }

    // -------------------------------------------------------------
    // Bulk operations
    // -------------------------------------------------------------
    public function bulkUpdateStatus(array $ids, int $statusId): int
    {
        return $this->bulkUpdate($ids, 'loan_status_id', $statusId);
    }

    public function bulkUpdateRepaymentStatus(array $ids, int $statusId): int
    {
        return $this->bulkUpdate($ids, 'repayment_status_id', $statusId);
    }

    private function bulkUpdate(array $ids, string $column, int $value): int
    {
        if (empty($ids)) return 0;
        $placeholders = [];
        $params = ['value' => $value];
        foreach ($ids as $i => $id) {
            $key = "id_{$i}";
            $placeholders[] = ":$key";
            $params[$key] = $id;
        }
        $sql = "UPDATE loans SET {$column} = :value, updated_at = NOW() WHERE id IN (" . implode(',', $placeholders) . ")";
        return $this->query($sql, $params)->rowCount();
    }

    public function bulkDelete(array $ids): int
    {
        if (empty($ids)) return 0;
        $placeholders = [];
        $params = [];
        foreach ($ids as $i => $id) {
            $key = "id_{$i}";
            $placeholders[] = ":$key";
            $params[$key] = $id;
        }
        $sql = "DELETE FROM loans WHERE id IN (" . implode(',', $placeholders) . ")";
        return $this->query($sql, $params)->rowCount();
    }

    // -------------------------------------------------------------
    // Dashboard aggregates
    // -------------------------------------------------------------
    public function kpis(): array
    {
        $row = $this->query(
            "SELECT
                COUNT(*)                                                          AS total_loans,
                COALESCE(SUM(l.amount), 0)                                        AS total_amount,
                COUNT(*) FILTER (WHERE ls.status_name NOT IN ('Closed','Rejected')) AS active_loans,
                COUNT(*) FILTER (WHERE ls.status_name = 'Closed')                 AS closed_loans,
                COUNT(*) FILTER (WHERE ls.status_name = 'Rejected')               AS rejected_loans,
                COUNT(*) FILTER (WHERE date_trunc('month', l.created_at) = date_trunc('month', CURRENT_DATE)) AS new_this_month
             FROM loans l JOIN loan_statuses ls ON ls.id = l.loan_status_id"
        )->fetch();
        return $row;
    }

    public function groupCounts(): array
    {
        return $this->query(
            "SELECT loan_group, COUNT(*) AS total FROM loan_register_view GROUP BY loan_group"
        )->fetchAll();
    }

    public function loansByBranch(): array
    {
        return $this->query(
            "SELECT b.branch_name, COUNT(l.id) AS total, COALESCE(SUM(l.amount),0) AS total_amount
             FROM branches b LEFT JOIN loans l ON l.branch_id = b.id
             WHERE b.status = 'Active'
             GROUP BY b.branch_name ORDER BY b.branch_name"
        )->fetchAll();
    }

    public function loansByMonth(int $months = 12): array
    {
        return $this->query(
            "SELECT to_char(date_trunc('month', created_at), 'YYYY-MM') AS ym,
                    to_char(date_trunc('month', created_at), 'Mon') AS label,
                    COUNT(*) AS total
             FROM loans
             WHERE created_at >= date_trunc('month', CURRENT_DATE) - (:months || ' months')::interval
             GROUP BY 1, 2 ORDER BY 1",
            ['months' => $months]
        )->fetchAll();
    }

    // public function recentActivity(int $limit = 8): array
    // {
    //     return $this->query(
    //         "SELECT reference_number, name, surname, status, amount, date_loaded
    //          FROM loan_register_view ORDER BY date_loaded DESC LIMIT :limit"
    //     )->fetchAll();
    // }
public function recentActivity(int $limit = 8): array
{
    return $this->query(
        "SELECT reference_number, name, surname, status,repayment_status, amount, date_loaded
         FROM loan_register_view
         ORDER BY date_loaded DESC
         LIMIT :limit",
        ['limit' => $limit]
    )->fetchAll();
}

  // -------------------------------------------------------------
    // Budget tracking (amount spent per branch for a given month)
    //
    // "Spent" = money that has actually left the branch. Under the loan
    // lifecycle (Pending Review -> Approved -> Disbursed -> Closed), funds
    // only leave the branch once a loan reaches Disbursed (or later,
    // Closed). A Pending Review or Approved loan hasn't paid out yet and
    // must NOT count against the branch's monthly budget; Rejected loans
    // never paid out at all.
    // -------------------------------------------------------------
    private const DISBURSED_STATUSES = ['Disbursed', 'Closed'];

    public function spentByBranchForMonth(string $month): array
    {
        return $this->query(
            "SELECT l.branch_id, COALESCE(SUM(l.amount), 0) AS spent
             FROM loans l
             JOIN loan_statuses ls ON ls.id = l.loan_status_id
             WHERE date_trunc('month', l.created_at) = date_trunc('month', :m::date)
               AND ls.status_name IN ('Disbursed', 'Closed')
             GROUP BY l.branch_id",
            ['m' => $month]
        )->fetchAll();
    }

    public function spentForBranchMonth(int $branchId, string $month): float
    {
        return (float) $this->query(
            "SELECT COALESCE(SUM(l.amount), 0) AS spent
             FROM loans l
             JOIN loan_statuses ls ON ls.id = l.loan_status_id
             WHERE l.branch_id = :b
               AND date_trunc('month', l.created_at) = date_trunc('month', :m::date)
               AND ls.status_name IN ('Disbursed', 'Closed')",
            ['b' => $branchId, 'm' => $month]
        )->fetch()['spent'];
    }

    // -------------------------------------------------------------
    // Payments Due Today (dashboard alert + reports breakdown)
    // "Due today" = action_date is today AND repayment status isn't
    // already 'Paid'.
    // -------------------------------------------------------------
    public function paymentsDueTodayCount(): int
    {
        return (int) $this->query(
            "SELECT COUNT(*) c FROM loan_register_view
             WHERE action_date = CURRENT_DATE AND repayment_status <> 'Paid'"
        )->fetch()['c'];
    }

    public function paymentsDueTodayByBranch(): array
    {
        $byBranch = $this->query(
            "SELECT branch_id, branch_name, COUNT(*) AS loan_count, COALESCE(SUM(amount_due), 0) AS total_due
             FROM loan_register_view
             WHERE action_date = CURRENT_DATE AND repayment_status <> 'Paid'
             GROUP BY branch_id, branch_name
             ORDER BY branch_name"
        )->fetchAll();

        $grandTotal = $this->query(
            "SELECT COUNT(*) AS loan_count, COALESCE(SUM(amount_due), 0) AS total_due
             FROM loan_register_view
             WHERE action_date = CURRENT_DATE AND repayment_status <> 'Paid'"
        )->fetch();

        return ['by_branch' => $byBranch, 'grand_total' => $grandTotal];
    }

    // -------------------------------------------------------------
    // Reports screen
    // -------------------------------------------------------------
      public function reportSummary(array $filters): array
    {
        [$whereSql, $params] = $this->buildFilterClause($filters);
        $totals = $this->query(
            "SELECT COUNT(*) AS total_loans, COALESCE(SUM(amount),0) AS total_amount,
                    COUNT(*) FILTER (WHERE status NOT IN ('Closed','Rejected'))    AS active_loans,
                    COUNT(*) FILTER (WHERE repayment_status = 'Paid')              AS paid_loans,
                    COUNT(*) FILTER (WHERE repayment_status = 'Defaulted')         AS overdue_loans
             FROM loan_register_view {$whereSql}", $params
        )->fetch();

        $byBranch = $this->query(
            "SELECT branch_name,
                    COUNT(*) AS total_loans,
                    COALESCE(SUM(amount),0) AS total_amount,
                    COUNT(*) FILTER (WHERE status NOT IN ('Closed','Rejected'))    AS active_loans,
                    COUNT(*) FILTER (WHERE repayment_status = 'Paid')              AS paid_loans,
                    COUNT(*) FILTER (WHERE repayment_status = 'Defaulted')         AS overdue_loans
             FROM loan_register_view {$whereSql}
             GROUP BY branch_name ORDER BY branch_name", $params
        )->fetchAll();

        return ['totals' => $totals, 'by_branch' => $byBranch];
    }
}
