<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Database;
use PDO;

class Product
{
    private const SORTABLE = ['id', 'name', 'price', 'quantity_available'];

    /** PDO is injectable → enables mocking / DI in tests (task 15). */
    public function __construct(private ?PDO $db = null)
    {
        $this->db ??= Database::connection();
    }

    public function all(): array
    {
        return $this->db->query('SELECT * FROM products ORDER BY id DESC')->fetchAll();
    }

    /** Paginated + sorted list. @return array{data:array,total:int,page:int,per_page:int,pages:int,sort:string,dir:string} */
    public function paginate(int $page = 1, int $perPage = 5, string $sort = 'id', string $dir = 'desc'): array
    {
        $sort = in_array($sort, self::SORTABLE, true) ? $sort : 'id';
        $dir  = strtolower($dir) === 'asc' ? 'ASC' : 'DESC';
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $total = (int) $this->db->query('SELECT COUNT(*) FROM products')->fetchColumn();
        $stmt = $this->db->prepare("SELECT * FROM products ORDER BY {$sort} {$dir} LIMIT :l OFFSET :o");
        $stmt->bindValue(':l', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':o', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'pages' => (int) max(1, ceil($total / $perPage)),
            'sort' => $sort,
            'dir' => strtolower($dir),
        ];
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM products WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO products (name, price, quantity_available) VALUES (:name, :price, :qty)'
        );
        $stmt->execute([
            ':name' => $data['name'],
            ':price' => $data['price'],
            ':qty' => $data['quantity_available'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE products SET name = :name, price = :price, quantity_available = :qty WHERE id = :id'
        );
        return $stmt->execute([
            ':name' => $data['name'],
            ':price' => $data['price'],
            ':qty' => $data['quantity_available'],
            ':id' => $id,
        ]);
    }

    /** True when the product appears in purchase history (it must then be kept for the records). */
    public function hasTransactions(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM transactions WHERE product_id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        return (bool) $stmt->fetchColumn();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM products WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }
}
