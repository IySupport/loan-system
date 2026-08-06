<?php

class LoanController extends Controller
{
    private function lookups(): array
    {
        return [
            'branches'          => (new Branch())->activeBranches(),
            'loanStatuses'      => (new LoanStatus())->all('id ASC'),
            'repaymentStatuses' => (new RepaymentStatus())->all('id ASC'),
        ];
    }

    public function captureForm(): void
    {
        Auth::requireLogin();
        $this->view('loans/capture', array_merge($this->lookups(), [
            'csrf' => $this->csrfToken(),
        ]));
    }

    public function store(): void
    {
        Auth::requireLogin();
        if (!$this->verifyCsrf()) {
            $this->json(['success' => false, 'message' => 'Invalid session token, please refresh and try again.'], 419);
        }

        $errors = $this->validate($_POST);
        if (!empty($errors)) {
            $this->json(['success' => false, 'errors' => $errors], 422);
        }

        try {
            $result = (new Loan())->create([
                'name'                => trim($_POST['name']),
                'surname'             => trim($_POST['surname']),
                'id_number'           => trim($_POST['id_number']),
                'account_number'      => trim($_POST['account_number'] ?? ''),
                'phone'               => trim($_POST['phone'] ?? ''),
                'branch_id'           => (int) $_POST['branch_id'],
                'loan_status_id'      => (int) $_POST['loan_status_id'],
                'repayment_status_id' => (int) $_POST['repayment_status_id'],
                'amount'              => (float) $_POST['amount'],
                'workplace_name'      => trim($_POST['workplace_name'] ?? ''),
                'work_contact'        => trim($_POST['work_contact'] ?? ''),
                'action_date'         => $_POST['action_date'],
                'date_loaded'         => $_POST['date_loaded'],
                'notes'               => trim($_POST['notes'] ?? ''),
                'created_by'          => Auth::user()['id'],
            ]);
            $this->json(['success' => true, 'loan' => $result]);
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => 'Could not save loan: ' . $e->getMessage()], 500);
        }
    }

    private function validate(array $d): array
    {
        $errors = [];
        if (empty(trim($d['id_number'] ?? '')))         $errors['id_number'] = 'ID Number is required.';
        if (empty(trim($d['name'] ?? '')))               $errors['name'] = 'Name is required.';
        if (empty(trim($d['surname'] ?? '')))            $errors['surname'] = 'Surname is required.';
        if (!isset($d['amount']) || $d['amount'] === '' || !is_numeric($d['amount']) || (float) $d['amount'] < 0) {
            $errors['amount'] = 'A valid, non-negative amount is required.';
        }
        if (empty($d['branch_id']))              $errors['branch_id'] = 'Branch is required.';
        if (empty($d['loan_status_id']))         $errors['loan_status_id'] = 'Loan Status is required.';
        if (empty($d['repayment_status_id']))    $errors['repayment_status_id'] = 'Repayment Status is required.';
        if (empty($d['action_date']))            $errors['action_date'] = 'Action Date is required.';
        if (empty($d['date_loaded']))            $errors['date_loaded'] = 'Date Loaded is required.';
        return $errors;
    }

    public function registerView(): void
    {
        Auth::requireLogin();
        $this->view('loans/register', array_merge($this->lookups(), [
            'csrf' => $this->csrfToken(),
        ]));
    }

    private function filtersFromRequest(): array
    {
        return [
            'search'              => trim($this->input('search', '')),
            'branch_id'           => $this->input('branch_id', ''),
            'loan_group'          => $this->input('loan_group', ''),
            'loan_status_id'      => $this->input('loan_status_id', ''),
            'repayment_status_id' => $this->input('repayment_status_id', ''),
            'workplace'           => trim($this->input('workplace', '')),
            'date_loaded_from'    => $this->input('date_loaded_from', ''),
            'date_loaded_to'      => $this->input('date_loaded_to', ''),
            'action_date_from'    => $this->input('action_date_from', ''),
            'action_date_to'      => $this->input('action_date_to', ''),
            'amount_min'          => $this->input('amount_min', ''),
            'amount_max'          => $this->input('amount_max', ''),
            'loan_count_min'      => $this->input('loan_count_min', ''),
            'loan_count_max'      => $this->input('loan_count_max', ''),
        ];
    }

    // AJAX server-side data source for DataTables
    public function listData(): void
    {
        Auth::requireLogin();
        $draw   = (int) $this->input('draw', 1);
        $start  = (int) $this->input('start', 0);
        $length = (int) $this->input('length', 25);
        $length = $length > 0 ? $length : 25;

        $orderCol = $this->input('order_col', 'date_loaded');
        $orderDir = $this->input('order_dir', 'DESC');

        $filters = $this->filtersFromRequest();
        $loan = new Loan();
        $result = $loan->registerList($filters, $orderCol, $orderDir, $length, $start);

        $this->json([
            'draw'            => $draw,
            'recordsTotal'    => $result['total'],
            'recordsFiltered' => $result['total'],
            'data'            => $result['data'],
        ]);
    }

    public function editForm(string $id): void
    {
        Auth::requireLogin();
        $loan = (new Loan())->findFull((int) $id);
        if (!$loan) $this->json(['success' => false, 'message' => 'Loan not found'], 404);
        $this->json(['success' => true, 'loan' => $loan]);
    }

    public function update(string $id): void
    {
        Auth::requireLogin();
        if (!$this->verifyCsrf()) {
            $this->json(['success' => false, 'message' => 'Invalid session token.'], 419);
        }
        $errors = [];
        if (!isset($_POST['amount']) || $_POST['amount'] === '' || (float) $_POST['amount'] < 0) $errors['amount'] = 'A valid, non-negative amount is required.';
        if (empty($_POST['branch_id']))              $errors['branch_id'] = 'Branch is required.';
        if (empty($_POST['loan_status_id']))         $errors['loan_status_id'] = 'Loan Status is required.';
        if (empty($_POST['repayment_status_id']))    $errors['repayment_status_id'] = 'Repayment Status is required.';
        if (empty($_POST['action_date']))            $errors['action_date'] = 'Action Date is required.';
        if (empty($_POST['date_loaded']))            $errors['date_loaded'] = 'Date Loaded is required.';
        if (!empty($errors)) $this->json(['success' => false, 'errors' => $errors], 422);

        $ok = (new Loan())->update((int) $id, [
            'branch_id'           => (int) $_POST['branch_id'],
            'loan_status_id'      => (int) $_POST['loan_status_id'],
            'repayment_status_id' => (int) $_POST['repayment_status_id'],
            'amount'              => (float) $_POST['amount'],
            'workplace_name'      => trim($_POST['workplace_name'] ?? ''),
            'work_contact'        => trim($_POST['work_contact'] ?? ''),
            'action_date'         => $_POST['action_date'],
            'date_loaded'         => $_POST['date_loaded'],
            'notes'               => trim($_POST['notes'] ?? ''),
        ]);
        $this->json(['success' => $ok]);
    }

    private function idsFromRequest(): array
    {
        $ids = $_POST['ids'] ?? [];
        if (is_string($ids)) $ids = json_decode($ids, true) ?: [];
        return array_map('intval', array_filter((array) $ids));
    }

    public function bulkStatus(): void
    {
        Auth::requireLogin();
        if (!$this->verifyCsrf()) $this->json(['success' => false, 'message' => 'Invalid session token.'], 419);
        $ids = $this->idsFromRequest();
        $statusId = (int) $this->input('loan_status_id');
        if (empty($ids) || !$statusId) $this->json(['success' => false, 'message' => 'Select rows and a status.'], 422);
        $count = (new Loan())->bulkUpdateStatus($ids, $statusId);
        $this->json(['success' => true, 'updated' => $count]);
    }

    public function bulkRepaymentStatus(): void
    {
        Auth::requireLogin();
        if (!$this->verifyCsrf()) $this->json(['success' => false, 'message' => 'Invalid session token.'], 419);
        $ids = $this->idsFromRequest();
        $statusId = (int) $this->input('repayment_status_id');
        if (empty($ids) || !$statusId) $this->json(['success' => false, 'message' => 'Select rows and a repayment status.'], 422);
        $count = (new Loan())->bulkUpdateRepaymentStatus($ids, $statusId);
        $this->json(['success' => true, 'updated' => $count]);
    }

    public function bulkDelete(): void
    {
        Auth::requireLogin();
        if (!$this->verifyCsrf()) $this->json(['success' => false, 'message' => 'Invalid session token.'], 419);
        $ids = $this->idsFromRequest();
        if (empty($ids)) $this->json(['success' => false, 'message' => 'Select rows to delete.'], 422);
        $count = (new Loan())->bulkDelete($ids);
        $this->json(['success' => true, 'deleted' => $count]);
    }
}
