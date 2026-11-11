<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $member->name }}</h2>
            <a href="{{ route('members.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">Kembali</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('partials.alerts')

            <div class="bg-white shadow-sm sm:rounded-lg p-6 text-gray-700 text-sm">
                <dl class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <div><dt class="font-semibold">Nomor anggota</dt><dd>{{ $member->member_number }}</dd></div>
                    <div><dt class="font-semibold">Email</dt><dd>{{ $member->email }}</dd></div>
                    <div><dt class="font-semibold">Telepon</dt><dd>{{ $member->phone ?? '-' }}</dd></div>
                    <div><dt class="font-semibold">Status</dt><dd>{{ $member->status }}</dd></div>
                    <div><dt class="font-semibold">Buku sedang dipinjam</dt><dd>{{ $member->active_loans }}</dd></div>
                    <div><dt class="font-semibold">Role</dt><dd>{{ $member->roles->pluck('name')->join(', ') }}</dd></div>
                    <div class="md:col-span-2"><dt class="font-semibold">Alamat</dt><dd>{{ $member->address ?? '-' }}</dd></div>
                </dl>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-3">Riwayat Keanggotaan</h3>
                <table class="min-w-full border border-gray-200 text-sm text-gray-600">
                    <thead>
                        <tr class="bg-gray-100 uppercase">
                            <th class="py-2 px-4 text-left">Mulai</th>
                            <th class="py-2 px-4 text-left">Berakhir</th>
                            <th class="py-2 px-4 text-left">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($member->memberships as $membership)
                            <tr class="border-t border-gray-200">
                                <td class="py-2 px-4">{{ $membership->starts_at->format('d-m-Y') }}</td>
                                <td class="py-2 px-4">{{ $membership->expires_at->format('d-m-Y') }}</td>
                                <td class="py-2 px-4">{{ $membership->status }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
