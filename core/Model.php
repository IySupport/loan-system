<?php

abstract class Model
{
    protected PDO $db;
    protected string $table = '';

    public function __construct()
    {
        $this->db = Database::connect();
    }

    protected function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function find(int $id): ?array
    {
        $row = $this->query("SELECT * FROM {$this->table} WHERE id = :id", ['id' => $id])->fetch();
        return $row ?: null;
    }

    public function all(string $order = 'id ASC'): array
    {
        return $this->query("SELECT * FROM {$this->table} ORDER BY {$order}")->fetchAll();
    }

    public function delete(int $id): bool
    {
        return $this->query("DELETE FROM {$this->table} WHERE id = :id", ['id' => $id])->rowCount() > 0;
    }
}
