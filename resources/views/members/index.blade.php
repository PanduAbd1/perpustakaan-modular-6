<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Daftar Anggota</h2>
            <div class="flex space-x-2">
                <a href="{{ route('members.trashed') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">Arsip</a>
                <a href="{{ route('members.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">+ Tambah Anggota</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @include('partials.alerts')

            <form method="GET" action="{{ route('members.index') }}" class="bg-white shadow-sm sm:rounded-lg p-4 mb-4 flex items-end gap-4">
                <div>
                    <label class="block text-sm text-gray-600">Cari nama / email / nomor</label>
                    <input type="text" name="q" value="{{ request('q') }}" class="border-gray-300 rounded-md shadow-sm">
                </div>
                <button type="submit" class="bg-gray-800 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">Cari</button>
            </form>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 overflow-x-auto">
                    <table class="min-w-full bg-white border border-gray-200">
                        <thead>
                            <tr class="bg-gray-100 text-gray-600 uppercase text-sm leading-normal">
                                <th class="py-3 px-4 text-left">Nomor</th>
                                <th class="py-3 px-4 text-left">Nama</th>
                                <th class="py-3 px-4 text-left">Email</th>
                                <th class="py-3 px-4 text-left">Role</th>
                                <th class="py-3 px-4 text-left">Berlaku s/d</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-center">Dipinjam</th>
                                <th class="py-3 px-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 text-sm">
                            @forelse ($members as $member)
                                <tr class="border-b border-gray-200 hover:bg-gray-50">
                                    <td class="py-3 px-4">{{ $member->member_number }}</td>
                                    <td class="py-3 px-4 font-medium">{{ $member->name }}</td>
                                    <td class="py-3 px-4">{{ $member->email }}</td>
                                    <td class="py-3 px-4">{{ $member->roles->pluck('name')->join(', ') }}</td>
                                    <td class="py-3 px-4">{{ $member->currentMembership?->expires_at->format('d-m-Y') }}</td>
                                    <td class="py-3 px-4 text-center">{{ $member->status }}</td>
                                    <td class="py-3 px-4 text-center">{{ $member->active_loans }}</td>
                                    <td class="py-3 px-4 text-center">
                                        <div class="flex items-center justify-center space-x-2">
                                            <a href="{{ route('members.show', $member) }}" class="text-blue-500 hover:text-blue-700 font-semibold">Detail</a>
                                            <form action="{{ route('members.destroy', $member) }}" method="POST" onsubmit="return confirm('Pindahkan anggota ini ke arsip?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-500 hover:text-red-700 font-semibold">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-4 px-4 text-center text-gray-500">Belum ada anggota.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-4">{{ $members->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
