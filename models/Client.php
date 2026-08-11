<?php

class Client extends Model
{
    protected string $table = 'clients';

    public function findByIdNumber(string $idNumber): ?array
    {
        $row = $this->query("SELECT * FROM clients WHERE id_number = :i", ['i' => $idNumber])->fetch();
        return $row ?: null;
    }

    public function loanCount(int $clientId): int
    {
        return (int) $this->query(
            "SELECT COUNT(*) c FROM loans WHERE client_id = :id",
            ['id' => $clientId]
        )->fetch()['c'];
    }

    public static function groupForCount(int $count): string
    {
        if ($count <= 3)  return 'Group 1';
        if ($count <= 8)  return 'Group 2';
        return 'Group 3';
    }

    public function create(array $d): int
{
    $stmt = $this->query(
        "INSERT INTO clients
            (name, surname, id_number, account_number, phone)
         VALUES
            (:name, :surname, :id_number, :account_number, :phone)
         RETURNING id",
        [
            'name'           => $d['name'],
            'surname'        => $d['surname'],
            'id_number'      => $d['id_number'],
            'account_number' => $d['account_number'] ?: null,
            'phone'          => $d['phone'] ?: null,
        ]
    );

    return (int) $stmt->fetchColumn();
}
    // public function create(array $d): int
    // {
    //     $this->query(
    //         "INSERT INTO clients (name, surname, id_number, account_number, phone)
    //          VALUES (:name, :surname, :id_number, :account_number, :phone)",
    //         [
    //             'name'           => $d['name'],
    //             'surname'        => $d['surname'],
    //             'id_number'      => $d['id_number'],
    //             'account_number' => $d['account_number'] ?? null,
    //             'phone'          => $d['phone'] ?? null,
    //         ]
    //     );
    //     return (int) $this->db->lastInsertId('clients_id_seq');
    // }

    public function update(int $id, array $d): bool
    {
        return $this->query(
            "UPDATE clients SET name = :name, surname = :surname, id_number = :id_number,
                account_number = :account_number, phone = :phone
             WHERE id = :id",
            [
                'name' => $d['name'], 'surname' => $d['surname'], 'id_number' => $d['id_number'],
                'account_number' => $d['account_number'] ?? null, 'phone' => $d['phone'] ?? null,
                'id' => $id,
            ]
        )->rowCount() >= 0;
    }

    public function idNumberExists(string $idNumber, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) c FROM clients WHERE id_number = :i";
        $params = ['i' => $idNumber];
        if ($excludeId) { $sql .= " AND id != :id"; $params['id'] = $excludeId; }
        return (int) $this->query($sql, $params)->fetch()['c'] > 0;
    }

    // -------------------------------------------------------------
    // Admin: Clients search/list page (server-side, used by DataTables)
    // -------------------------------------------------------------
    public function searchList(array $filters, string $orderBy = 'created_at', string $orderDir = 'DESC', int $limit = 25, int $offset = 0): array
    {
        $where  = [];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = "(c.name ILIKE :search OR c.surname ILIKE :search OR c.id_number ILIKE :search
                         OR c.account_number ILIKE :search OR c.phone ILIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $sortable = ['name', 'surname', 'id_number', 'account_number', 'phone', 'loan_count', 'created_at'];
        $orderBy  = in_array($orderBy, $sortable, true) ? $orderBy : 'created_at';
        $orderDir = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';

        $baseSql = "FROM clients c
                     LEFT JOIN (SELECT client_id, COUNT(*) AS loan_count FROM loans GROUP BY client_id) lc
                       ON lc.client_id = c.id
                     {$whereSql}";

        $total = (int) $this->query("SELECT COUNT(*) c {$baseSql}", $params)->fetch()['c'];

        $sql = "SELECT c.id, c.name, c.surname, c.id_number, c.account_number, c.phone, c.created_at,
                        COALESCE(lc.loan_count, 0) AS loan_count
                 {$baseSql}
                 ORDER BY {$orderBy} {$orderDir}
                 LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) { $stmt->bindValue(":$k", $v); }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return ['data' => $stmt->fetchAll(), 'total' => $total];
    }

    /**
     * A client can only be deleted if nothing in the database still
     * references it (currently: loans.client_id). Returns the number of
     * referencing loans - 0 means it's safe to delete.
     */
    public function referencingLoanCount(int $clientId): int
    {
        return $this->loanCount($clientId);
    }

    /**
     * Find existing client by ID number, or create a new one.
     * Returns the client's row plus loan_count (before the new loan is added) and group.
     */
    // public function findOrCreate(array $d): array
    // {
    //     $existing = $this->findByIdNumber($d['id_number']);
    //     if ($existing) {
    //         return $existing;
    //     }
    //     $newId = $this->create($d);
    //     return $this->find($newId);
    // }
    public function findOrCreate(array $d): array
{
    $stmt = $this->query(
        "INSERT INTO clients
            (name, surname, id_number, account_number, phone)
         VALUES
            (:name, :surname, :id_number, :account_number, :phone)
         ON CONFLICT (id_number)
         DO UPDATE SET
            name = EXCLUDED.name,
            surname = EXCLUDED.surname,
            account_number = EXCLUDED.account_number,
            phone = EXCLUDED.phone
         RETURNING *",
        [
            'name'           => $d['name'],
            'surname'        => $d['surname'],
            'id_number'      => $d['id_number'],
            'account_number' => $d['account_number'] ?: null,
            'phone'          => $d['phone'] ?: null,
        ]
    );

    return $stmt->fetch(PDO::FETCH_ASSOC);
}
}
