<?php

use Modules\Products\Models\Category;
use Modules\Products\Models\Product;

beforeEach(function () {
    $this->actingAs(makeRegularUser());
});

it('merender halaman index, create, show, dan edit produk', function () {
    $product = Product::factory()->create();

    $this->get(route('products.index'))->assertOk()->assertSee($product->name);
    $this->get(route('products.create'))->assertOk();
    $this->get(route('products.show', $product))->assertOk()->assertSee($product->name);
    $this->get(route('products.edit', $product))->assertOk()->assertSee($product->slug);
});

it('menyimpan produk beserta kategorinya', function () {
    $categories = Category::factory()->count(2)->create();

    $this->post(route('products.store'), [
        'name' => 'Monitor 24 Inci',
        'slug' => 'monitor-24-inci',
        'price' => 1500000,
        'stock' => 7,
        'category_ids' => $categories->pluck('id')->all(),
    ])->assertRedirect(route('products.index'));

    $product = Product::with('categories')->where('slug', 'monitor-24-inci')->firstOrFail();

    expect($product->categories)->toHaveCount(2);
});

it('menolak kategori yang tidak valid', function () {
    $this->post(route('products.store'), [
        'name' => 'Monitor',
        'slug' => 'monitor',
        'price' => 1000,
        'stock' => 1,
        'category_ids' => [999999],
    ])->assertSessionHasErrors('category_ids.0');

    expect(Product::count())->toBe(0);
});

it('memperbarui produk dan mengganti kategorinya, termasuk melepas semua kategori', function () {
    $product = Product::factory()->create();
    $categories = Category::factory()->count(2)->create();
    $product->categories()->attach($categories->first());

    $this->put(route('products.update', $product), [
        'name' => 'Nama Baru',
        'slug' => $product->slug,
        'price' => 2000,
        'stock' => 3,
        'category_ids' => [$categories->last()->id],
    ])->assertRedirect(route('products.index'));

    expect($product->fresh()->name)->toBe('Nama Baru')
        ->and($product->categories()->pluck('categories.id')->all())->toBe([$categories->last()->id]);

    $this->put(route('products.update', $product), [
        'name' => 'Nama Baru',
        'slug' => $product->slug,
        'price' => 2000,
        'stock' => 3,
        'category_ids' => [''],
    ])->assertRedirect(route('products.index'));

    expect($product->categories()->count())->toBe(0);
});

it('menghapus produk secara soft delete', function () {
    $product = Product::factory()->create();

    $this->delete(route('products.destroy', $product))->assertRedirect(route('products.index'));

    expect(Product::count())->toBe(0)->and(Product::onlyTrashed()->count())->toBe(1);
});

it('menjaga jumlah query halaman produk tetap konstan walau datanya bertambah', function () {
    Product::factory()->count(2)->create();
    $few = countQueries(fn () => $this->get(route('products.index'))->assertOk());

    Product::factory()->count(6)->create();
    $many = countQueries(fn () => $this->get(route('products.index'))->assertOk());

    expect($many)->toBe($few);
});
