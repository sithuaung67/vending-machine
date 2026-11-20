<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Database;
use PDO;

class Transaction
{
    public function __construct(private ?PDO $db = null)
    {
        $this->db ??= Database::connection();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO transactions (user_id, product_id, quantity, unit_price, total_price)
             VALUES (:user_id, :product_id, :quantity, :unit_price, :total_price)'
        );
        $stmt->execute([
            ':user_id' => $data['user_id'],
            ':product_id' => $data['product_id'],
            ':quantity' => $data['quantity'],
            ':unit_price' => $data['unit_price'],
            ':total_price' => $data['total_price'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function all(): array
    {
        $sql = 'SELECT t.*, p.name AS product_name, u.name AS user_name
                FROM transactions t
                LEFT JOIN products p ON p.id = t.product_id
                LEFT JOIN users u ON u.id = t.user_id
                ORDER BY t.id DESC';
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Paginated purchase history with product + buyer details.
     * Pass a user id to limit the history to that user's own purchases.
     * @return array{data:array,total:int,page:int,per_page:int,pages:int}
     */
    public function paginate(?int $userId = null, int $page = 1, int $perPage = 10): array
    {
        $page = max(1, $page);
        $where = $userId !== null ? 'WHERE t.user_id = :uid' : '';

        $count = $this->db->prepare("SELECT COUNT(*) FROM transactions t {$where}");
        if ($userId !== null) $count->bindValue(':uid', $userId, PDO::PARAM_INT);
        $count->execute();
        $total = (int) $count->fetchColumn();

        $stmt = $this->db->prepare(
            "SELECT t.*, p.name AS product_name, u.name AS user_name, u.email AS user_email
             FROM transactions t
             LEFT JOIN products p ON p.id = t.product_id
             LEFT JOIN users u ON u.id = t.user_id
             {$where}
             ORDER BY t.id DESC
             LIMIT :l OFFSET :o"
        );
        if ($userId !== null) $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':l', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':o', ($page - 1) * $perPage, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'pages' => (int) max(1, ceil($total / $perPage)),
        ];
    }

    /** Totals for the history page: number of purchases, units sold, revenue. */
    public function summary(?int $userId = null): array
    {
        $where = $userId !== null ? 'WHERE user_id = :uid' : '';
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS purchases, COALESCE(SUM(quantity), 0) AS units, COALESCE(SUM(total_price), 0) AS revenue
             FROM transactions {$where}"
        );
        if ($userId !== null) $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch() ?: [];
        return [
            'purchases' => (int) ($row['purchases'] ?? 0),
            'units' => (int) ($row['units'] ?? 0),
            'revenue' => (float) ($row['revenue'] ?? 0),
        ];
    }

    public function forUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT t.*, p.name AS product_name FROM transactions t
             JOIN products p ON p.id = t.product_id
             WHERE t.user_id = :uid ORDER BY t.id DESC'
        );
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll();
    }
}
