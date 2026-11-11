<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $loan->loan_number }}</h2>
            <a href="{{ route('loans.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">Kembali</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('partials.alerts')

            <div class="bg-white shadow-sm sm:rounded-lg p-6 text-gray-700 text-sm">
                <dl class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <div><dt class="font-semibold">Anggota</dt><dd>{{ $loan->member_name }}</dd></div>
                    <div><dt class="font-semibold">Status</dt><dd>{{ $loan->isOverdue() ? 'terlambat' : $loan->status }}</dd></div>
                    <div><dt class="font-semibold">Dipinjam</dt><dd>{{ $loan->borrowed_at->format('d-m-Y H:i') }}</dd></div>
                    <div><dt class="font-semibold">Jatuh tempo</dt><dd>{{ $loan->due_at->format('d-m-Y') }}</dd></div>
                    <div><dt class="font-semibold">Dikembalikan</dt><dd>{{ $loan->returned_at?->format('d-m-Y H:i') ?? '-' }}</dd></div>
                    <div>
                        <dt class="font-semibold">{{ $loan->isReturned() ? 'Total denda' : 'Estimasi denda saat ini' }}</dt>
                        <dd>Rp {{ number_format($loan->isReturned() ? (float) $loan->total_fine : $loan->estimatedFine()->getAmount()->getAmount(), 0, ',', '.') }}</dd>
                    </div>
                    @if ($loan->notes)
                        <div class="md:col-span-2"><dt class="font-semibold">Catatan</dt><dd>{{ $loan->notes }}</dd></div>
                    @endif
                </dl>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-3">Detail Peminjaman</h3>
                <table class="min-w-full border border-gray-200 text-sm text-gray-600">
                    <thead>
                        <tr class="bg-gray-100 uppercase">
                            <th class="py-2 px-4 text-left">Judul</th>
                            <th class="py-2 px-4 text-left">ISBN</th>
                            <th class="py-2 px-4 text-center">Jumlah</th>
                            <th class="py-2 px-4 text-left">Dikembalikan</th>
                            <th class="py-2 px-4 text-right">Denda</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($loan->items as $item)
                            <tr class="border-t border-gray-200">
                                <td class="py-2 px-4 font-medium">{{ $item->book_title }}</td>
                                <td class="py-2 px-4">{{ $item->book_isbn }}</td>
                                <td class="py-2 px-4 text-center">{{ $item->quantity }}</td>
                                <td class="py-2 px-4">{{ $item->returned_at?->format('d-m-Y') ?? '-' }}</td>
                                <td class="py-2 px-4 text-right">Rp {{ number_format((float) $item->fine_amount, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                @unless ($loan->isReturned())
                    <form action="{{ route('loans.return', $loan) }}" method="POST" class="mt-4" onsubmit="return confirm('Proses pengembalian semua buku pada peminjaman ini?');">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">Kembalikan Buku</button>
                    </form>
                @endunless
            </div>
        </div>
    </div>
</x-app-layout>
