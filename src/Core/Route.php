<?php
declare(strict_types=1);
namespace App\Core;

use Attribute;

/**
 * Attribute routing — annotate controller methods, e.g.
 *   #[Route('GET', '/products/{id}/purchase', name: 'products.purchase')]
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class Route
{
    public function __construct(
        public string $method,
        public string $path,
        public array $middleware = [],
        public ?string $name = null,
    ) {}
}
