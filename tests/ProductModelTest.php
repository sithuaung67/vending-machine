<?php
declare(strict_types=1);
namespace Tests;

use App\Models\Product;
use PHPUnit\Framework\TestCase;

/** CRUD + pagination tests for the Product model (DI with in-memory SQLite). */
final class ProductModelTest extends TestCase
{
    public function test_create_find_update_delete(): void
    {
        $pdo = Support::pdo();
        $model = new Product($pdo);

        $id = $model->create(['name' => 'Coke', 'price' => 3.99, 'quantity_available' => 10]);
        $this->assertGreaterThan(0, $id);

        $found = $model->find($id);
        $this->assertSame('Coke', $found['name']);

        $model->update($id, ['name' => 'Coke Zero', 'price' => 4.0, 'quantity_available' => 7]);
        $this->assertSame('Coke Zero', $model->find($id)['name']);

        $model->delete($id);
        $this->assertNull($model->find($id));
    }

    public function test_paginate_sorts_and_limits(): void
    {
        $pdo = Support::pdo();
        $model = new Product($pdo);
        foreach ([['A', 1.0, 1], ['B', 2.0, 2], ['C', 3.0, 3]] as $p) {
            $model->create(['name' => $p[0], 'price' => $p[1], 'quantity_available' => $p[2]]);
        }

        $page = $model->paginate(page: 1, perPage: 2, sort: 'name', dir: 'asc');
        $this->assertCount(2, $page['data']);
        $this->assertSame('A', $page['data'][0]['name']);
        $this->assertSame(3, $page['total']);
        $this->assertSame(2, $page['pages']);
    }
}
