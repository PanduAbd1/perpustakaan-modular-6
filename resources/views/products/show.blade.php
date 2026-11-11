<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $product->name }}
            </h2>
            <a href="{{ route('products.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                Kembali
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-gray-700 text-sm">
                <dl class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <div><dt class="font-semibold">Slug</dt><dd>{{ $product->slug }}</dd></div>
                    <div><dt class="font-semibold">Harga</dt><dd>Rp {{ number_format($product->price, 2, ',', '.') }}</dd></div>
                    <div><dt class="font-semibold">Stok</dt><dd>{{ $product->stock }}</dd></div>
                    <div><dt class="font-semibold">Status</dt><dd>{{ $product->is_active ? 'Aktif' : 'Nonaktif' }}</dd></div>
                    <div class="md:col-span-2"><dt class="font-semibold">Kategori</dt><dd>{{ $product->categories->pluck('name')->join(', ') ?: '-' }}</dd></div>
                    <div class="md:col-span-2"><dt class="font-semibold">Deskripsi</dt><dd>{{ $product->description ?? '-' }}</dd></div>
                </dl>

                <div class="mt-6">
                    <a href="{{ route('products.edit', $product) }}" class="text-blue-500 hover:text-blue-700 font-semibold">Edit</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
