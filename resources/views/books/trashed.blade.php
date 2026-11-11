<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Arsip Buku</h2>
            <a href="{{ route('books.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">Kembali</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @include('partials.alerts')

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 overflow-x-auto">
                    <table class="min-w-full bg-white border border-gray-200">
                        <thead>
                            <tr class="bg-gray-100 text-gray-600 uppercase text-sm leading-normal">
                                <th class="py-3 px-4 text-left">Judul</th>
                                <th class="py-3 px-4 text-left">ISBN</th>
                                <th class="py-3 px-4 text-left">Penulis</th>
                                <th class="py-3 px-4 text-left">Dihapus pada</th>
                                <th class="py-3 px-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 text-sm">
                            @forelse ($books as $book)
                                <tr class="border-b border-gray-200 hover:bg-gray-50">
                                    <td class="py-3 px-4 font-medium">{{ $book->title }}</td>
                                    <td class="py-3 px-4">{{ $book->isbn->getValue() }}</td>
                                    <td class="py-3 px-4">{{ $book->authors->pluck('name')->join(', ') }}</td>
                                    <td class="py-3 px-4">{{ $book->deleted_at->format('d-m-Y H:i') }}</td>
                                    <td class="py-3 px-4 text-center">
                                        <form action="{{ route('books.restore', $book) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-green-600 hover:text-green-800 font-semibold">Pulihkan</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-4 px-4 text-center text-gray-500">Arsip kosong.</td>
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
