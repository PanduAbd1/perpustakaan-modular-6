<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Peminjaman Baru</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @include('partials.alerts')

            <form method="POST" action="{{ route('loans.store') }}" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-6">
                @csrf

                <div>
                    <x-input-label for="member_id" value="Anggota" />
                    <select id="member_id" name="member_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                        <option value="">Pilih anggota</option>
                        @foreach ($members as $member)
                            <option value="{{ $member['id'] }}" @selected(old('member_id') == $member['id'])>
                                {{ $member['member_number'] }} - {{ $member['name'] }} (dipinjam {{ $member['active_loans'] }} / kuota {{ $member['quota'] }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label value="Buku yang dipinjam" />
                    <div class="mt-1 overflow-x-auto border border-gray-200 rounded-md">
                        <table class="min-w-full text-sm text-gray-600">
                            <thead>
                                <tr class="bg-gray-100 uppercase">
                                    <th class="py-2 px-4 text-left">Pilih</th>
                                    <th class="py-2 px-4 text-left">Judul</th>
                                    <th class="py-2 px-4 text-left">ISBN</th>
                                    <th class="py-2 px-4 text-center">Stok</th>
                                    <th class="py-2 px-4 text-center">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($books as $index => $book)
                                    <tr x-data="{ checked: false }" class="border-t border-gray-200">
                                        <td class="py-2 px-4">
                                            <input type="checkbox" x-model="checked" :name="checked ? 'items[{{ $index }}][book_id]' : null" value="{{ $book['id'] }}" class="rounded border-gray-300">
                                        </td>
                                        <td class="py-2 px-4 font-medium">{{ $book['title'] }}</td>
                                        <td class="py-2 px-4">{{ $book['isbn'] }}</td>
                                        <td class="py-2 px-4 text-center">{{ $book['stock'] }}</td>
                                        <td class="py-2 px-4 text-center">
                                            <input type="number" name="items[{{ $index }}][quantity]" value="1" min="1" max="5" :disabled="!checked" class="w-20 border-gray-300 rounded-md shadow-sm">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div>
                    <x-input-label for="notes" value="Catatan" />
                    <textarea id="notes" name="notes" rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('notes') }}</textarea>
                </div>

                <div class="flex items-center space-x-3">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Pinjamkan</button>
                    <a href="{{ route('loans.index') }}" class="text-gray-600 hover:text-gray-900">Batal</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
