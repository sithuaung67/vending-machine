<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;
use PDO;
use RuntimeException;

/**
 * Handles the purchase process atomically: locks the product row, verifies stock,
 * decrements quantity and logs the transaction — all inside one DB transaction.
 * Prevents overselling / double-decrement under concurrency.
 */
class PurchaseService
{
    public function __construct(private ?PDO $db = null)
    {
        $this->db ??= Database::connection();
    }

    /** @return array{transaction_id:int,product_id:int,quantity:int,unit_price:string,total_price:string,remaining:int} */
    public function execute(int $userId, int $productId, int $quantity): array
    {
        if ($quantity <= 0) {
            throw new RuntimeException('Quantity must be at least 1.');
        }

        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        $lock = $driver === 'mysql' ? ' FOR UPDATE' : '';

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT * FROM products WHERE id = :id{$lock}");
            $stmt->execute([':id' => $productId]);
            $product = $stmt->fetch();

            if (!$product) {
                throw new RuntimeException('Product not found.');
            }
            if ((int) $product['quantity_available'] < $quantity) {
                throw new RuntimeException('Insufficient stock. Only ' . $product['quantity_available'] . ' left.');
            }

            // Guarded decrement — the WHERE clause is a second safety net against overselling.
            $upd = $this->db->prepare(
                'UPDATE products SET quantity_available = quantity_available - :q
                 WHERE id = :id AND quantity_available >= :q'
            );
            $upd->execute([':q' => $quantity, ':id' => $productId]);
            if ($upd->rowCount() === 0) {
                throw new RuntimeException('Purchase failed — stock changed, please retry.');
            }

            $unit = (string) $product['price'];
            $total = number_format((float) $unit * $quantity, 3, '.', '');

            $ins = $this->db->prepare(
                'INSERT INTO transactions (user_id, product_id, quantity, unit_price, total_price)
                 VALUES (:user_id, :product_id, :quantity, :unit_price, :total_price)'
            );
            $ins->execute([
                ':user_id' => $userId,
                ':product_id' => $productId,
                ':quantity' => $quantity,
                ':unit_price' => $unit,
                ':total_price' => $total,
            ]);
            $txnId = (int) $this->db->lastInsertId();

            $this->db->commit();

            return [
                'transaction_id' => $txnId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'unit_price' => $unit,
                'total_price' => $total,
                'remaining' => (int) $product['quantity_available'] - $quantity,
            ];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}
