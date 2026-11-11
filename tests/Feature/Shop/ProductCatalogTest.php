<?php

use Modules\Products\Models\Category;
use Modules\Products\Models\Product;

it('menyimpan pivot added_at pada relasi many-to-many produk dan kategori', function () {
    $product = Product::factory()->create();
    $categories = Category::factory()->count(2)->create();

    $product->categories()->attach($categories->pluck('id')->all(), ['added_at' => now()]);

    $loaded = Product::with('categories')->find($product->id);

    expect($loaded->categories)->toHaveCount(2)
        ->and($loaded->categories->first()->pivot->added_at)->not->toBeNull();
});

it('mendukung attach tanpa menyebut added_at karena pivot memakai default waktu sekarang', function () {
    $product = Product::factory()->create();
    $category = Category::factory()->create();

    $product->categories()->attach($category);

    expect($category->products()->first()->pivot->added_at)->not->toBeNull();
});

it('mendukung kategori self-referencing', function () {
    $parent = Category::factory()->create();
    $child = $parent->children()->create(['name' => 'Sub Category', 'slug' => 'sub-category']);

    expect($child->parent->is($parent))->toBeTrue()
        ->and($parent->children)->toHaveCount(1)
        ->and(Category::whereNull('parent_id')->count())->toBe(1);
});

it('menyaring produk dengan query scope', function () {
    Product::factory()->create(['name' => 'Laptop Murah', 'price' => 400000, 'stock' => 5]);
    Product::factory()->create(['name' => 'Laptop Mahal', 'price' => 900000, 'stock' => 5]);
    Product::factory()->create(['name' => 'Laptop Habis', 'price' => 100000, 'stock' => 0]);
    Product::factory()->create(['name' => 'Mouse Nonaktif', 'price' => 50000, 'stock' => 9, 'is_active' => false]);
    Product::factory()->create(['name' => 'Kabel', 'price' => 20000, 'stock' => 3, 'low_stock_threshold' => 5]);

    expect(Product::available()->priceBelow(500000)->pluck('name')->all())->toContain('Laptop Murah', 'Kabel')
        ->not->toContain('Laptop Mahal', 'Laptop Habis', 'Mouse Nonaktif')
        ->and(Product::search('Laptop')->count())->toBe(3)
        ->and(Product::lowStock()->pluck('name')->all())->toContain('Laptop Habis', 'Kabel');
});

it('soft delete dan restore pada produk', function () {
    $product = Product::factory()->create();
    $product->delete();

    expect(Product::count())->toBe(0)
        ->and(Product::withTrashed()->count())->toBe(1)
        ->and(Product::onlyTrashed()->count())->toBe(1);

    $product->restore();

    expect(Product::count())->toBe(1);
});

it('soft delete dan restore pada kategori', function () {
    $category = Category::factory()->create();
    $category->delete();

    expect(Category::count())->toBe(0)->and(Category::withTrashed()->count())->toBe(1);

    $category->restore();

    expect(Category::count())->toBe(1);
});

it('membuat slug otomatis ketika produk dibuat tanpa slug', function () {
    $product = Product::create(['name' => 'Keyboard Mekanik', 'price' => 350000, 'stock' => 4]);

    expect($product->slug)->toStartWith('keyboard-mekanik-');
});

it('menghilangkan N+1 pada daftar produk dengan eager loading kategori', function () {
    $categories = Category::factory()->count(3)->create();

    Product::factory()->count(10)->create()->each(
        fn (Product $product) => $product->categories()->attach($categories->random(2)->pluck('id')->all())
    );

    $lazy = countQueries(fn () => Product::all()->each(fn ($p) => $p->categories->count()));
    $eager = countQueries(fn () => Product::with('categories')->get()->each(fn ($p) => $p->categories->count()));

    expect($lazy)->toBe(11)->and($eager)->toBe(2);
});
