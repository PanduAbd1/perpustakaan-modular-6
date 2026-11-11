<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Katalog Buku</h2>
            @if (auth()->user()->role === 'admin')
                <div class="flex space-x-2">
                    <a href="{{ route('books.trashed') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">Arsip</a>
                    <a href="{{ route('books.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">+ Tambah Buku</a>
                </div>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @include('partials.alerts')

            <form method="GET" action="{{ route('books.index') }}" class="bg-white shadow-sm sm:rounded-lg p-4 mb-4 flex flex-wrap items-end gap-4">
                <div>
                    <label class="block text-sm text-gray-600">Cari judul / penulis</label>
                    <input type="text" name="q" value="{{ request('q') }}" class="border-gray-300 rounded-md shadow-sm">
                </div>
                <div>
                    <label class="block text-sm text-gray-600">Kategori</label>
                    <select name="category" class="border-gray-300 rounded-md shadow-sm">
                        <option value="">Semua kategori</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(request('category') == $category->id)>
                                {{ $category->parent ? $category->parent->name.' › ' : '' }}{{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <label class="inline-flex items-center space-x-2">
                    <input type="checkbox" name="available" value="1" @checked(request()->boolean('available')) class="rounded border-gray-300">
                    <span class="text-sm text-gray-600">Hanya yang tersedia</span>
                </label>
                <button type="submit" class="bg-gray-800 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">Filter</button>
            </form>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 overflow-x-auto">
                    <table class="min-w-full bg-white border border-gray-200">
                        <thead>
                            <tr class="bg-gray-100 text-gray-600 uppercase text-sm leading-normal">
                                <th class="py-3 px-4 text-left">No</th>
                                <th class="py-3 px-4 text-left">Judul</th>
                                <th class="py-3 px-4 text-left">ISBN</th>
                                <th class="py-3 px-4 text-left">Penulis</th>
                                <th class="py-3 px-4 text-left">Penerbit</th>
                                <th class="py-3 px-4 text-left">Kategori</th>
                                <th class="py-3 px-4 text-center">Stok</th>
                                @if (auth()->user()->role === 'admin')
                                    <th class="py-3 px-4 text-center">Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 text-sm">
                            @forelse ($books as $index => $book)
                                <tr class="border-b border-gray-200 hover:bg-gray-50">
                                    <td class="py-3 px-4">{{ $books->firstItem() + $index }}</td>
                                    <td class="py-3 px-4 font-medium">{{ $book->title }}</td>
                                    <td class="py-3 px-4">{{ $book->isbn->getValue() }}</td>
                                    <td class="py-3 px-4">{{ $book->authors->pluck('name')->join(', ') }}</td>
                                    <td class="py-3 px-4">{{ $book->publisher?->name }}</td>
                                    <td class="py-3 px-4">{{ $book->categories->pluck('name')->join(', ') }}</td>
                                    <td class="py-3 px-4 text-center">{{ $book->stock }}</td>
                                    @if (auth()->user()->role === 'admin')
                                        <td class="py-3 px-4 text-center">
                                            <div class="flex items-center justify-center space-x-2">
                                                <a href="{{ route('books.edit', $book) }}" class="text-blue-500 hover:text-blue-700 font-semibold">Edit</a>
                                                <form action="{{ route('books.destroy', $book) }}" method="POST" onsubmit="return confirm('Pindahkan buku ini ke arsip?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-500 hover:text-red-700 font-semibold">Hapus</button>
                                                </form>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-4 px-4 text-center text-gray-500">Tidak ada buku yang cocok.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-4">{{ $books->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
