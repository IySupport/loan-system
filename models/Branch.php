<?php

class Branch extends Model
{
    protected string $table = 'branches';

    public function activeBranches(): array
    {
        return $this->query("SELECT * FROM branches WHERE status = 'Active' ORDER BY branch_name ASC")->fetchAll();
    }

    public function create(array $d): int
    {
        $this->query("INSERT INTO branches (branch_name, status) VALUES (:n, :s)", [
            'n' => $d['branch_name'], 's' => $d['status'] ?? 'Active',
        ]);
        return (int) $this->db->lastInsertId('branches_id_seq');
    }

    public function update(int $id, array $d): bool
    {
        return $this->query("UPDATE branches SET branch_name = :n, status = :s WHERE id = :id", [
            'n' => $d['branch_name'], 's' => $d['status'], 'id' => $id,
        ])->rowCount() >= 0;
    }

    public function nameExists(string $name, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) c FROM branches WHERE LOWER(branch_name) = LOWER(:n)";
        $params = ['n' => $name];
        if ($excludeId) { $sql .= " AND id != :id"; $params['id'] = $excludeId; }
        return (int) $this->query($sql, $params)->fetch()['c'] > 0;
    }
}
