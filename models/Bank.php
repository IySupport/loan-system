<?php

class Bank extends Model
{
    protected string $table = 'banks';

    public function all(string $orderBy = 'bank_name ASC'): array
    {
        return $this->query("SELECT * FROM banks ORDER BY {$orderBy}")->fetchAll();
    }

    public function find(int $id): ?array
    {
        $row = $this->query("SELECT * FROM banks WHERE id = :id", ['id' => $id])->fetch();
        return $row ?: null;
    }
}
