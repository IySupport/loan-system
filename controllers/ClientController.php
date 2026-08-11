<?php

class ClientController extends Controller
{
    // AJAX: GET /clients/lookup?id_number=xxxx
    // public function lookup(): void
    // {
    //     Auth::requireLogin();
    //     $idNumber = trim((string) $this->input('id_number', ''));
    //     if ($idNumber === '') {
    //         $this->json(['found' => false]);
    //     }

    //     $clientModel = new Client();
    //     $client = $clientModel->findByIdNumber($idNumber);

    //     if (!$client) {
    //         $this->json(['found' => false]);
    //     }

    //     $loanCount = $clientModel->loanCount($client['id']);
    //     $this->json([
    //         'found'  => true,
    //         'client' => [
    //             'id'             => $client['id'],
    //             'name'           => $client['name'],
    //             'surname'        => $client['surname'],
    //             'id_number'      => $client['id_number'],
    //             'account_number' => $client['account_number'],
    //             'phone'          => $client['phone'],
    //         ],
    //         'previous_loan_count' => $loanCount,
    //         'next_loan_count'     => $loanCount + 1,
    //         'group'               => Client::groupForCount($loanCount + 1),
    //     ]);
    // }
// AJAX: GET /clients/lookup?id_number=xxxx
    public function lookup(): void
    {
        Auth::requireLogin();
        $idNumber = trim((string) $this->input('id_number', ''));
        if ($idNumber === '') {
            $this->json(['found' => false]);
        }

        $clientModel = new Client();
        $client = $clientModel->findByIdNumber($idNumber);

        if (!$client) {
            $this->json(['found' => false]);
        }

        $loanCount = $clientModel->loanCount($client['id']);

        // Prefill Workplace Name/Contact from the client's most recent
        // loan as a convenience - it's still an editable field on the
        // form since employment can change between loans.
        $lastLoan = (new Loan())->forClient($client['id'])[0] ?? null;

        $this->json([
            'found'  => true,
            'client' => [
                'id'             => $client['id'],
                'name'           => $client['name'],
                'surname'        => $client['surname'],
                'id_number'      => $client['id_number'],
                'account_number' => $client['account_number'],
                'phone'          => $client['phone'],
            ],
            'previous_loan_count' => $loanCount,
            'next_loan_count'     => $loanCount + 1,
            'group'               => Client::groupForCount($loanCount + 1),
            'last_workplace_name' => $lastLoan['workplace_name'] ?? '',
            'last_work_contact'   => $lastLoan['work_contact'] ?? '',
        ]);
    }
    // ---------------------------------------------------------------
    // Admin: Clients page (search, view, update, delete)
    // ---------------------------------------------------------------
    public function index(): void
    {
        Auth::requireLogin();
        $this->view('clients/index', [
            'csrf' => $this->csrfToken(),
        ]);
    }

    // AJAX server-side data source for the Clients DataTable
    public function listData(): void
    {
        Auth::requireLogin();
        $draw   = (int) $this->input('draw', 1);
        $start  = (int) $this->input('start', 0);
        $length = (int) $this->input('length', 25);
        $length = $length > 0 ? $length : 25;

        $orderCol = $this->input('order_col', 'created_at');
        $orderDir = $this->input('order_dir', 'DESC');
        $search   = trim($this->input('search', ''));

        $result = (new Client())->searchList(['search' => $search], $orderCol, $orderDir, $length, $start);

        $this->json([
            'draw'            => $draw,
            'recordsTotal'    => $result['total'],
            'recordsFiltered' => $result['total'],
            'data'            => $result['data'],
        ]);
    }

    // AJAX: GET /clients/{id} - client details + loan history, for the View modal
    public function show(string $id): void
    {
        Auth::requireLogin();
        $clientModel = new Client();
        $client = $clientModel->find((int) $id);
        if (!$client) {
            $this->json(['success' => false, 'message' => 'Client not found.'], 404);
        }

        $loans = (new Loan())->forClient((int) $id);

        $this->json([
            'success' => true,
            'client'  => $client,
            'loans'   => $loans,
            'loan_count' => count($loans),
        ]);
    }

    public function update(string $id): void
    {
        Auth::requireLogin();
        if (!$this->verifyCsrf()) {
            $this->json(['success' => false, 'message' => 'Invalid session token.'], 419);
        }

        $clientModel = new Client();
        $existing = $clientModel->find((int) $id);
        if (!$existing) {
            $this->json(['success' => false, 'message' => 'Client not found.'], 404);
        }

        $name          = trim($_POST['name'] ?? '');
        $surname       = trim($_POST['surname'] ?? '');
        $idNumber      = trim($_POST['id_number'] ?? '');
        $accountNumber = trim($_POST['account_number'] ?? '');
        $phone         = trim($_POST['phone'] ?? '');

        $errors = [];
        if ($name === '')     $errors['name'] = 'Name is required.';
        if ($surname === '')  $errors['surname'] = 'Surname is required.';
        if ($idNumber === '') $errors['id_number'] = 'ID Number is required.';
        if ($idNumber !== '' && $clientModel->idNumberExists($idNumber, (int) $id)) {
            $errors['id_number'] = 'Another client already uses this ID Number.';
        }
        if (!empty($errors)) {
            $this->json(['success' => false, 'errors' => $errors], 422);
        }

        $ok = $clientModel->update((int) $id, [
            'name'           => $name,
            'surname'        => $surname,
            'id_number'      => $idNumber,
            'account_number' => $accountNumber,
            'phone'          => $phone,
        ]);
        $this->json(['success' => $ok]);
    }

    // A client can only be deleted while nothing in the database still
    // references it (currently: loans.client_id). We check this
    // explicitly first (clear message to the admin), and additionally
    // rely on the loans.client_id foreign key as a hard backstop in case
    // a loan is created between the check and the delete.
    public function delete(string $id): void
    {
        Auth::requireLogin();
        if (!$this->verifyCsrf()) {
            $this->json(['success' => false, 'message' => 'Invalid session token.'], 419);
        }

        $clientModel = new Client();
        $client = $clientModel->find((int) $id);
        if (!$client) {
            $this->json(['success' => false, 'message' => 'Client not found.'], 404);
        }

        $refCount = $clientModel->referencingLoanCount((int) $id);
        if ($refCount > 0) {
            $this->json([
                'success' => false,
                'message' => 'This client cannot be deleted: ' . $refCount . ' loan record' . ($refCount === 1 ? '' : 's') . ' still reference' . ($refCount === 1 ? 's' : '') . ' them. Delete or reassign those loans first.',
            ], 422);
        }

        try {
            $ok = $clientModel->delete((int) $id);
            $this->json(['success' => $ok]);
        } catch (Exception $e) {
            // Backstop: a foreign key violation means something started
            // referencing this client after our check above.
            $this->json(['success' => false, 'message' => 'This client cannot be deleted because other records still reference it.'], 422);
        }
    }
}
