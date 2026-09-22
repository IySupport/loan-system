<?php

class User extends Model
{
    protected string $table = 'users';

    public function findByUsername(string $username): ?array
    {
        $row = $this->query(
            "SELECT u.*, b.branch_name
             FROM users u
             LEFT JOIN branches b ON b.id = u.branch_id
             WHERE u.username = :u",
            ['u' => $username]
        )->fetch();
        return $row ?: null;
    }

    /**
     * All users with their assigned branch name (for Branch-role accounts;
     * NULL branch_name for Administrator/Operator). Used by the User
     * Management screen.
     */
    public function allWithBranchName(): array
    {
        return $this->query(
            "SELECT u.*, b.branch_name
             FROM users u
             LEFT JOIN branches b ON b.id = u.branch_id
             ORDER BY u.full_name ASC"
        )->fetchAll();
    }

    public function create(array $d): int
    {
        $this->query(
            "INSERT INTO users (full_name, username, password_hash, role, branch_id, status)
             VALUES (:full_name, :username, :password_hash, :role, :branch_id, :status)",
            [
                'full_name'     => $d['full_name'],
                'username'      => $d['username'],
                'password_hash' => password_hash($d['password'], PASSWORD_BCRYPT),
                'role'          => $d['role'],
                'branch_id'     => $d['role'] === 'Branch' ? $d['branch_id'] : null,
                'status'        => $d['status'] ?? 'Active',
            ]
        );
        return (int) $this->db->lastInsertId('users_id_seq');
    }

    public function update(int $id, array $d): bool
    {
        return $this->query(
            "UPDATE users SET full_name = :full_name, username = :username, role = :role,
                branch_id = :branch_id, status = :status
             WHERE id = :id",
            [
                'full_name' => $d['full_name'],
                'username'  => $d['username'],
                'role'      => $d['role'],
                'branch_id' => $d['role'] === 'Branch' ? $d['branch_id'] : null,
                'status'    => $d['status'],
                'id'        => $id,
            ]
        )->rowCount() >= 0;
    }

    public function resetPassword(int $id, string $newPassword): bool
    {
        return $this->query(
            "UPDATE users SET password_hash = :hash WHERE id = :id",
            ['hash' => password_hash($newPassword, PASSWORD_BCRYPT), 'id' => $id]
        )->rowCount() > 0;
    }

    public function setStatus(int $id, string $status): bool
    {
        return $this->query("UPDATE users SET status = :s WHERE id = :id", ['s' => $status, 'id' => $id])->rowCount() > 0;
    }

    public function usernameExists(string $username, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) c FROM users WHERE username = :u";
        $params = ['u' => $username];
        if ($excludeId) {
            $sql .= " AND id != :id";
            $params['id'] = $excludeId;
        }
        return (int) $this->query($sql, $params)->fetch()['c'] > 0;
    }
}
