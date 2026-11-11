<?php

use App\Models\User;
use Illuminate\Support\Facades\Event;
use Modules\Orders\Events\OrderCreated;
use Modules\Orders\Events\OrderStatusChanged;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderItem;
use Modules\Orders\Models\OrderStatusHistory;
use Modules\Orders\Services\OrderService;
use Modules\Products\Models\Product;
use Modules\Shared\Contracts\ProductServiceInterface;
use Modules\Shared\Exceptions\InsufficientStockException;

function orderService(): OrderService
{
    return app(OrderService::class);
}

it('membuat order: menghitung total, menyimpan snapshot, mengurangi stok, dan mencatat riwayat', function () {
    Event::fake([OrderCreated::class]);

    $user = User::factory()->create();
    $laptop = Product::factory()->create(['price' => 5000000, 'stock' => 10]);
    $mouse = Product::factory()->create(['price' => 150000, 'stock' => 5]);

    $order = orderService()->createOrder($user->id, [
        ['product_id' => $laptop->id, 'quantity' => 2],
        ['product_id' => $mouse->id, 'quantity' => 3],
    ], 'Yogyakarta');

    expect((float) $order->total_amount)->toBe(10450000.0)
        ->and($order->items)->toHaveCount(2)
        ->and($order->statusHistories)->toHaveCount(1)
        ->and($order->statusHistories->first()->status)->toBe('pending')
        ->and($order->calculateTotal())->toBe(10450000.0)
        ->and($laptop->fresh()->stock)->toBe(8)
        ->and($mouse->fresh()->stock)->toBe(2);

    $item = $order->items->firstWhere('product_id', $laptop->id);

    expect($item->product_name)->toBe($laptop->name)
        ->and((float) $item->price_at_purchase)->toBe(5000000.0)
        ->and((float) $item->subtotal)->toBe(10000000.0);

    Event::assertDispatched(OrderCreated::class, fn (OrderCreated $event) => $event->order->is($order));
});

it('rollback seluruh transaksi ketika stok salah satu produk tidak mencukupi', function () {
    Event::fake([OrderCreated::class]);

    $user = User::factory()->create();
    $productA = Product::factory()->create(['stock' => 5]);
    $productB = Product::factory()->create(['stock' => 1]);

    expect(fn () => orderService()->createOrder($user->id, [
        ['product_id' => $productA->id, 'quantity' => 2],
        ['product_id' => $productB->id, 'quantity' => 3],
    ]))->toThrow(InsufficientStockException::class);

    expect($productA->fresh()->stock)->toBe(5)
        ->and($productB->fresh()->stock)->toBe(1)
        ->and(Order::count())->toBe(0)
        ->and(OrderItem::count())->toBe(0)
        ->and(OrderStatusHistory::count())->toBe(0);

    Event::assertNotDispatched(OrderCreated::class);
});

it('rollback ketika produk tidak ditemukan', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['stock' => 5]);

    expect(fn () => orderService()->createOrder($user->id, [
        ['product_id' => $product->id, 'quantity' => 1],
        ['product_id' => 999999, 'quantity' => 1],
    ]))->toThrow(Exception::class, 'Produk tidak ditemukan');

    expect(Order::count())->toBe(0)->and($product->fresh()->stock)->toBe(5);
});

it('mengubah status order, mencatat riwayat, dan menembakkan OrderStatusChanged', function () {
    Event::fake([OrderCreated::class, OrderStatusChanged::class]);

    $user = User::factory()->create();
    $admin = makeAdmin();
    $product = Product::factory()->create(['stock' => 5]);
    $order = orderService()->createOrder($user->id, [['product_id' => $product->id, 'quantity' => 1]]);

    $updated = orderService()->updateStatus($order, 'shipped', $admin->id, 'Dikirim via kurir');

    expect($updated->status)->toBe('shipped')
        ->and($updated->statusHistories)->toHaveCount(2)
        ->and($updated->statusHistories->last()->notes)->toBe('Dikirim via kurir')
        ->and($updated->statusHistories->last()->changedBy->is($admin))->toBeTrue();

    Event::assertDispatched(OrderStatusChanged::class, fn (OrderStatusChanged $event) => $event->oldStatus === 'pending' && $event->newStatus === 'shipped');
});

it('menghilangkan N+1 pada daftar order dengan eager loading', function () {
    Event::fake([OrderCreated::class]);

    $users = User::factory()->count(3)->create();
    $product = Product::factory()->create(['stock' => 100]);

    foreach ($users as $user) {
        orderService()->createOrder($user->id, [['product_id' => $product->id, 'quantity' => 1]]);
        orderService()->createOrder($user->id, [['product_id' => $product->id, 'quantity' => 2]]);
    }

    $render = fn (Order $order) => [$order->items->count(), $order->user->name, $order->statusHistories->count()];

    $lazy = countQueries(fn () => Order::all()->each($render));
    $eager = countQueries(fn () => Order::with(['items', 'user', 'statusHistories'])->get()->each($render));

    expect($lazy)->toBe(1 + 6 * 3)->and($eager)->toBe(4);
});

it('mendukung query scope dan soft delete pada order', function () {
    Event::fake([OrderCreated::class]);

    $user = User::factory()->create();
    $product = Product::factory()->create(['stock' => 10]);
    $order = orderService()->createOrder($user->id, [['product_id' => $product->id, 'quantity' => 1]]);

    expect(Order::byStatus('pending')->count())->toBe(1)
        ->and(Order::byStatus('shipped')->count())->toBe(0)
        ->and(Order::recent(7)->count())->toBe(1);

    $order->delete();

    expect(Order::count())->toBe(0)->and(Order::withTrashed()->count())->toBe(1);

    $order->restore();

    expect(Order::count())->toBe(1);
});

it('contract ProductServiceInterface tidak mengurangi stok melebihi yang tersedia', function () {
    $service = app(ProductServiceInterface::class);
    $product = Product::factory()->create(['stock' => 2]);

    expect($service->reduceStock($product->id, 3))->toBeFalse()
        ->and($product->fresh()->stock)->toBe(2)
        ->and($service->reduceStock($product->id, 2))->toBeTrue()
        ->and($product->fresh()->stock)->toBe(0)
        ->and($service->isStockAvailable($product->id, 1))->toBeFalse()
        ->and($service->getProductForOrder(999999))->toBeNull()
        ->and($service->getProductForOrder($product->id))->toMatchArray(['id' => $product->id, 'stock' => 0]);
});
