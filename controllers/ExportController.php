<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExportController extends Controller
{
    private const MONEY_FORMAT = '"R" #,##0.00';
    private const DATE_FORMAT  = 'yyyy-mm-dd';

    /**
     * Column layout shared by every Excel export (selected / filtered / all /
     * by group / by branch / Reports page). Same order as the Loan Register
     * screen, plus Interest Amount, Amount Due and Work Contact.
     *
     * [header, key in loan_register_view, type]
     *   text  - written as an explicit string. Without this Excel shows a
     *           13-digit ID number as 9.00101E+12, drops leading zeros from
     *           account/phone numbers, and evaluates anything starting with
     *           "=" as a formula.
     *   money - number, formatted as R #,##0.00
     *   int   - whole number
     *   date  - real Excel date (sortable / filterable), shown as yyyy-mm-dd
     */
    private const COLUMNS = [
        ['Reference Number', 'reference_number', 'text'],
        ['Name',             'name',             'text'],
        ['Surname',          'surname',          'text'],
        ['ID Number',        'id_number',        'text'],
        ['Account Number',   'account_number',   'text'],
        ['Bank Name',        'bank_name',        'text'],
        ['Amount',           'amount',           'money'],
        ['Interest Amount',  'interest_amount',  'money'],
        ['Amount Due',       'amount_due',       'money'],
        ['Branch',           'branch_name',      'text'],
        ['Workplace',        'workplace_name',   'text'],
        ['Work Contact',     'work_contact',     'text'],
        ['Loan Count',       'loan_count',       'int'],
        ['Group',            'loan_group',       'text'],
        ['Loan Status',      'status',           'text'],
        ['Repayment Status', 'repayment_status', 'text'],
        ['Action Date',      'action_date',      'date'],
        ['Date Loaded',      'date_loaded',      'date'],
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

        $lastCol = Coordinate::stringFromColumnIndex(count(self::COLUMNS));

        foreach (self::COLUMNS as $i => [$header]) {
            $sheet->setCellValueExplicit(
                Coordinate::stringFromColumnIndex($i + 1) . '1',
                $header,
                DataType::TYPE_STRING
            );
        }
        $headerRange = "A1:{$lastCol}1";
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0F5C4C');
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $r = 2;
        foreach ($rows as $row) {
            foreach (self::COLUMNS as $i => [, $key, $type]) {
                $this->writeCell(
                    $sheet,
                    Coordinate::stringFromColumnIndex($i + 1) . $r,
                    $row[$key] ?? null,
                    $type
                );
            }
            $r++;
        }
        $lastRow = $r - 1;

        // Number formats are applied once per column range (not once per
        // cell) so large exports stay fast.
        if ($lastRow >= 2) {
            foreach (self::COLUMNS as $i => [, , $type]) {
                $col = Coordinate::stringFromColumnIndex($i + 1);
                if ($type === 'money') {
                    $sheet->getStyle("{$col}2:{$col}{$lastRow}")->getNumberFormat()->setFormatCode(self::MONEY_FORMAT);
                } elseif ($type === 'date') {
                    $sheet->getStyle("{$col}2:{$col}{$lastRow}")->getNumberFormat()->setFormatCode(self::DATE_FORMAT);
                }
            }
        }

        foreach (range(1, count(self::COLUMNS)) as $colIndex) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($colIndex))->setAutoSize(true);
        }
        $sheet->setAutoFilter("A1:{$lastCol}{$lastRow}");
        $sheet->freezePane('A2');

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    private function writeCell(Worksheet $sheet, string $coord, $value, string $type): void
    {
        if ($value === null || $value === '') {
            return; // leave the cell empty
        }

        switch ($type) {
            case 'money':
                $sheet->setCellValueExplicit($coord, (float) $value, DataType::TYPE_NUMERIC);
                return;
            case 'int':
                $sheet->setCellValueExplicit($coord, (int) $value, DataType::TYPE_NUMERIC);
                return;
            case 'date':
                try {
                    $serial = ExcelDate::PHPToExcel(new DateTime((string) $value));
                    $sheet->setCellValueExplicit($coord, $serial, DataType::TYPE_NUMERIC);
                } catch (Exception $e) {
                    $sheet->setCellValueExplicit($coord, (string) $value, DataType::TYPE_STRING);
                }
                return;
            default:
                $sheet->setCellValueExplicit($coord, (string) $value, DataType::TYPE_STRING);
        }
    }

    public function exportSelected(): void
    {
        Auth::requireStaff();
        $ids = $_GET['ids'] ?? '';
        $ids = $ids !== '' ? array_values(array_filter(array_map('intval', explode(',', $ids)))) : [];

        // An empty list must NOT fall through to the filter builder - with no
        // ids it adds no WHERE clause and would export the entire register.
        if (empty($ids)) {
            http_response_code(400);
            die('No rows selected for export.');
        }

        $rows = $this->rows(['ids' => $ids]);
        $this->stream($rows, 'loans_selected_' . date('Ymd_His') . '.xlsx');
    }

    public function exportFiltered(): void
    {
        Auth::requireStaff();
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
        Auth::requireStaff();
        $rows = $this->rows([]);
        $this->stream($rows, 'loans_all_' . date('Ymd_His') . '.xlsx');
    }

    public function exportByGroup(string $group): void
    {
        Auth::requireStaff();
        $label = 'Group ' . preg_replace('/\D/', '', $group);
        $rows = $this->rows(['loan_group' => $label]);
        $this->stream($rows, 'loans_' . str_replace(' ', '_', strtolower($label)) . '_' . date('Ymd_His') . '.xlsx');
    }

    public function exportByBranch(string $branchId): void
    {
        Auth::requireStaff();
        $rows = $this->rows(['branch_id' => (int) $branchId]);
        $this->stream($rows, 'loans_branch_' . $branchId . '_' . date('Ymd_His') . '.xlsx');
    }
}
