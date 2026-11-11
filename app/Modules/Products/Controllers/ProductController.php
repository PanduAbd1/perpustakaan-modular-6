<?php

namespace Modules\Products\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Arr;
use Modules\Products\Models\Category;
use Modules\Products\Models\Product;
use Modules\Products\Requests\StoreProductRequest;
use Modules\Products\Requests\UpdateProductRequest;
use Modules\Shared\Exceptions\InsufficientStockException;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('categories')->paginate(10);

        return view('products.index', compact('products'));
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();

        return view('products.create', compact('categories'));
    }

    public function store(StoreProductRequest $request)
    {
        $data = $request->validated();

        $product = Product::create(Arr::except($data, 'category_ids'));
        $product->categories()->sync(array_filter($data['category_ids'] ?? []));

        return redirect()
            ->route('products.index')
            ->with('success', 'Produk berhasil ditambahkan.');
    }

    public function show(Product $product)
    {
        $product->load('categories');

        return view('products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $product->load('categories');
        $categories = Category::orderBy('name')->get();

        return view('products.edit', compact('product', 'categories'));
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $data = $request->validated();

        $product->update(Arr::except($data, 'category_ids'));

        if (array_key_exists('category_ids', $data)) {
            $product->categories()->sync(array_filter($data['category_ids'] ?? []));
        }

        return redirect()
            ->route('products.index')
            ->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()
            ->route('products.index')
            ->with('success', 'Produk berhasil dihapus.');
    }

    public function reduceStock(Product $product, int $quantity)
    {
        if ($product->stock < $quantity) {
            throw new InsufficientStockException(
                $product->name,
                $product->stock,
                $quantity
            );
        }

        $product->decrement('stock', $quantity);

        return redirect()
            ->route('products.index')
            ->with('success', 'Stok berhasil dikurangi.');
    }
}
