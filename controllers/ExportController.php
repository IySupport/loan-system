<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ExportController extends Controller
{
    private const HEADERS = [
        'Reference Number', 'Name', 'Surname', 'ID Number', 'Account Number',
        'Amount', 'Branch', 'Loan Count', 'Group', 'Status', 'Report Status',
        'Action Date', 'Date Loaded',
    ];

    private function rows(array $filters): array
    {
        return (new Loan())->registerAll($filters);
    }

    private function stream(array $rows, string $filename): void
    {
        if (!class_exists(Spreadsheet::class)) {
            http_response_code(500);
            die('PhpSpreadsheet is not installed. Run "composer install" in the project root (see README.md).');
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Loan Register');

        $sheet->fromArray(self::HEADERS, null, 'A1');
        $headerRange = 'A1:' . chr(64 + count(self::HEADERS)) . '1';
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0F5C4C');
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $r = 2;
        foreach ($rows as $row) {
            $sheet->fromArray([
                $row['reference_number'],
                $row['name'],
                $row['surname'],
                $row['id_number'],
                $row['account_number'],
                (float) $row['amount'],
                $row['branch_name'],
                (int) $row['loan_count'],
                $row['loan_group'],
                $row['status'],
                $row['report_status'],
                $row['action_date'],
                date('Y-m-d', strtotime($row['date_loaded'])),
            ], null, "A{$r}");
            $r++;
        }

        foreach (range('A', chr(64 + count(self::HEADERS))) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->freezePane('A2');

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function exportSelected(): void
    {
        Auth::requireLogin();
        $ids = $_GET['ids'] ?? '';
        $ids = $ids !== '' ? array_map('intval', explode(',', $ids)) : [];
        $rows = $this->rows(['ids' => $ids]);
        $this->stream($rows, 'loans_selected_' . date('Ymd_His') . '.xlsx');
    }

    public function exportFiltered(): void
    {
        Auth::requireLogin();
        $filters = [
            'search'           => $_GET['search'] ?? '',
            'branch_id'        => $_GET['branch_id'] ?? '',
            'loan_group'       => $_GET['loan_group'] ?? '',
            'loan_status_id'   => $_GET['loan_status_id'] ?? '',
            'repayment_status_id' => $_GET['repayment_status_id'] ?? '',
            'workplace'        => $_GET['workplace'] ?? '',
            'date_loaded_from' => $_GET['date_loaded_from'] ?? '',
            'date_loaded_to'   => $_GET['date_loaded_to'] ?? '',
            'action_date_from' => $_GET['action_date_from'] ?? '',
            'action_date_to'   => $_GET['action_date_to'] ?? '',
            'amount_min'       => $_GET['amount_min'] ?? '',
            'amount_max'       => $_GET['amount_max'] ?? '',
            'loan_count_min'   => $_GET['loan_count_min'] ?? '',
            'loan_count_max'   => $_GET['loan_count_max'] ?? '',
        ];
        $rows = $this->rows($filters);
        $this->stream($rows, 'loans_filtered_' . date('Ymd_His') . '.xlsx');
    }

    public function exportAll(): void
    {
        Auth::requireLogin();
        $rows = $this->rows([]);
        $this->stream($rows, 'loans_all_' . date('Ymd_His') . '.xlsx');
    }

    public function exportByGroup(string $group): void
    {
        Auth::requireLogin();
        $label = 'Group ' . preg_replace('/\D/', '', $group);
        $rows = $this->rows(['loan_group' => $label]);
        $this->stream($rows, 'loans_' . str_replace(' ', '_', strtolower($label)) . '_' . date('Ymd_His') . '.xlsx');
    }

    public function exportByBranch(string $branchId): void
    {
        Auth::requireLogin();
        $rows = $this->rows(['branch_id' => (int) $branchId]);
        $this->stream($rows, 'loans_branch_' . $branchId . '_' . date('Ymd_His') . '.xlsx');
    }
}
