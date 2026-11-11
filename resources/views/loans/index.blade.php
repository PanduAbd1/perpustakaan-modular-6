<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Peminjaman</h2>
            <div class="flex space-x-2">
                <a href="{{ route('loans.trashed') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">Arsip</a>
                <a href="{{ route('loans.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">+ Peminjaman Baru</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @include('partials.alerts')

            <form method="GET" action="{{ route('loans.index') }}" class="bg-white shadow-sm sm:rounded-lg p-4 mb-4 flex flex-wrap items-end gap-4">
                <div>
                    <label class="block text-sm text-gray-600">Status</label>
                    <select name="status" class="border-gray-300 rounded-md shadow-sm">
                        <option value="">Semua</option>
                        <option value="dipinjam" @selected(request('status') === 'dipinjam')>Dipinjam</option>
                        <option value="dikembalikan" @selected(request('status') === 'dikembalikan')>Dikembalikan</option>
                    </select>
                </div>
                <label class="inline-flex items-center space-x-2">
                    <input type="checkbox" name="overdue" value="1" @checked(request()->boolean('overdue')) class="rounded border-gray-300">
                    <span class="text-sm text-gray-600">Hanya yang terlambat</span>
                </label>
                <button type="submit" class="bg-gray-800 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">Filter</button>
            </form>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 overflow-x-auto">
                    <table class="min-w-full bg-white border border-gray-200">
                        <thead>
                            <tr class="bg-gray-100 text-gray-600 uppercase text-sm leading-normal">
                                <th class="py-3 px-4 text-left">Nomor</th>
                                <th class="py-3 px-4 text-left">Anggota</th>
                                <th class="py-3 px-4 text-left">Buku</th>
                                <th class="py-3 px-4 text-left">Dipinjam</th>
                                <th class="py-3 px-4 text-left">Jatuh tempo</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-right">Denda</th>
                                <th class="py-3 px-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 text-sm">
                            @forelse ($loans as $loan)
                                <tr class="border-b border-gray-200 hover:bg-gray-50">
                                    <td class="py-3 px-4">{{ $loan->loan_number }}</td>
                                    <td class="py-3 px-4 font-medium">{{ $loan->member_name }}</td>
                                    <td class="py-3 px-4">{{ $loan->items->pluck('book_title')->join(', ') }}</td>
                                    <td class="py-3 px-4">{{ $loan->borrowed_at->format('d-m-Y') }}</td>
                                    <td class="py-3 px-4">{{ $loan->due_at->format('d-m-Y') }}</td>
                                    <td class="py-3 px-4 text-center">
                                        {{ $loan->isOverdue() ? 'terlambat' : $loan->status }}
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        Rp {{ number_format($loan->isReturned() ? (float) $loan->total_fine : $loan->estimatedFine()->getAmount()->getAmount(), 0, ',', '.') }}
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <div class="flex items-center justify-center space-x-2">
                                            <a href="{{ route('loans.show', $loan) }}" class="text-blue-500 hover:text-blue-700 font-semibold">Detail</a>
                                            @if ($loan->isReturned())
                                                <form action="{{ route('loans.destroy', $loan) }}" method="POST" onsubmit="return confirm('Pindahkan peminjaman ini ke arsip?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-500 hover:text-red-700 font-semibold">Hapus</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-4 px-4 text-center text-gray-500">Belum ada data peminjaman.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-4">{{ $loans->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
