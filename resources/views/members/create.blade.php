<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Tambah Anggota</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @include('partials.alerts')

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('members.store') }}" class="space-y-4">
                    @csrf

                    <div>
                        <x-input-label for="name" value="Nama" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required />
                    </div>

                    <div>
                        <x-input-label for="email" value="Email" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" required />
                    </div>

                    <div>
                        <x-input-label for="phone" value="Telepon" />
                        <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone')" />
                    </div>

                    <div>
                        <x-input-label for="address" value="Alamat" />
                        <textarea id="address" name="address" rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('address') }}</textarea>
                    </div>

                    <div>
                        <x-input-label value="Role" />
                        <div class="mt-1 space-y-1">
                            @foreach ($roles as $role)
                                <label class="flex items-center space-x-2">
                                    <input type="checkbox" name="role_ids[]" value="{{ $role->id }}" class="rounded border-gray-300" @checked(in_array($role->id, old('role_ids', [])))>
                                    <span class="text-sm text-gray-700">{{ $role->name }} (maks. {{ $role->max_loans }} buku, {{ $role->loan_days }} hari)</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <x-input-label for="months" value="Masa keanggotaan (bulan)" />
                        <x-text-input id="months" name="months" type="number" min="1" max="36" class="mt-1 block w-32" :value="old('months', 12)" />
                    </div>

                    <div class="flex items-center space-x-3">
                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Simpan</button>
                        <a href="{{ route('members.index') }}" class="text-gray-600 hover:text-gray-900">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
