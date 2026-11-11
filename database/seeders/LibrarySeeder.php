<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Event;
use Modules\Catalog\Models\Author;
use Modules\Catalog\Models\Book;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Publisher;
use Modules\Circulation\Events\BookBorrowed;
use Modules\Circulation\Services\LoanService;
use Modules\Membership\Models\Member;
use Modules\Membership\Models\Role;
use Modules\Membership\Services\MemberService;

class LibrarySeeder extends Seeder
{
    public function run(LoanService $loans, MemberService $members): void
    {
        Event::fake([BookBorrowed::class]);

        $roles = collect([
            ['name' => 'Mahasiswa', 'slug' => 'mahasiswa', 'max_loans' => 3, 'loan_days' => 7],
            ['name' => 'Dosen', 'slug' => 'dosen', 'max_loans' => 5, 'loan_days' => 14],
            ['name' => 'Umum', 'slug' => 'umum', 'max_loans' => 2, 'loan_days' => 7],
        ])->map(fn (array $role) => Role::create($role));

        $tree = [
            'Teknologi' => ['Pemrograman', 'Jaringan', 'Keamanan Siber'],
            'Sains' => ['Matematika', 'Fisika'],
            'Sastra' => ['Novel', 'Puisi'],
        ];

        $leafCategories = collect();

        foreach ($tree as $parentName => $children) {
            $parent = Category::factory()->create([
                'name' => $parentName,
                'slug' => str($parentName)->slug(),
            ]);

            foreach ($children as $childName) {
                $leafCategories->push(Category::factory()->create([
                    'name' => $childName,
                    'slug' => str($childName)->slug(),
                    'parent_id' => $parent->id,
                ]));
            }
        }

        $publishers = collect([
            ['Cakrawala Ilmu', 'Yogyakarta'],
            ['Nusa Pustaka', 'Jakarta'],
            ['Sinar Pelita', 'Bandung'],
            ['Mitra Edukasi', 'Surabaya'],
            ['Karya Mandiri', 'Semarang'],
        ])->map(fn (array $row) => Publisher::create(['name' => $row[0], 'city' => $row[1]]));

        $authors = collect([
            'Rina Kartika', 'Agus Setiawan', 'Maya Anggraini', 'Hadi Wibowo',
            'Lestari Putri', 'Teguh Prakoso', 'Nadia Safitri', 'Rizal Firdaus',
            'Intan Maharani', 'Yoga Pratama', 'Wulan Sari', 'Dedi Kurniawan',
        ])->map(fn (string $name) => Author::create(['name' => $name, 'bio' => "Penulis {$name}."]));

        $catalog = [
            ['Pemrograman Web dengan Laravel', 'Pemrograman', [0, 1], 2025, 5],
            ['Dasar-Dasar Pemrograman PHP', 'Pemrograman', [1], 2022, 4],
            ['Algoritma dan Struktur Data', 'Pemrograman', [2, 3], 2021, 6],
            ['Basis Data Relasional dan SQL', 'Pemrograman', [3], 2023, 3],
            ['Pemrograman Berorientasi Objek', 'Pemrograman', [0], 2020, 4],
            ['Domain-Driven Design untuk Pemula', 'Pemrograman', [1, 4], 2024, 2],
            ['Pengantar Jaringan Komputer', 'Jaringan', [5], 2022, 5],
            ['Konfigurasi Jaringan Nirkabel', 'Jaringan', [5, 6], 2023, 3],
            ['Administrasi Server Linux', 'Jaringan', [7], 2021, 4],
            ['Routing dan Switching Dasar', 'Jaringan', [6], 2020, 2],
            ['Keamanan Siber Praktis', 'Keamanan Siber', [7, 8], 2024, 1],
            ['Dasar Kriptografi Modern', 'Keamanan Siber', [8], 2022, 3],
            ['Pengujian Penetrasi Aplikasi Web', 'Keamanan Siber', [9], 2025, 2],
            ['Forensik Digital untuk Pemula', 'Keamanan Siber', [9, 10], 2021, 4],
            ['Matematika Diskrit', 'Matematika', [10], 2020, 6],
            ['Kalkulus Dasar', 'Matematika', [11], 2019, 5],
            ['Aljabar Linear Terapan', 'Matematika', [11, 0], 2022, 3],
            ['Statistika untuk Informatika', 'Matematika', [2], 2023, 4],
            ['Logika Matematika', 'Matematika', [3], 2018, 2],
            ['Fisika Dasar Jilid 1', 'Fisika', [4], 2019, 5],
            ['Fisika Dasar Jilid 2', 'Fisika', [4], 2019, 4],
            ['Elektronika Digital', 'Fisika', [5, 6], 2021, 3],
            ['Senja di Kota Tua', 'Novel', [6], 2020, 4],
            ['Pulang ke Lereng Merapi', 'Novel', [7], 2022, 3],
            ['Hujan Terakhir di Bulan Juni', 'Novel', [8], 2023, 5],
            ['Perahu Kertas di Muara', 'Novel', [9], 2018, 2],
            ['Surat dari Pulau Seberang', 'Novel', [10], 2024, 4],
            ['Kumpulan Puisi Malam Sunyi', 'Puisi', [11], 2021, 3],
            ['Embun di Ujung Daun', 'Puisi', [0], 2019, 2],
            ['Rindu yang Tak Bernama', 'Puisi', [1], 2023, 4],
        ];

        foreach ($catalog as $index => [$title, $categoryName, $authorIndexes, $year, $stock]) {
            $book = Book::create([
                'publisher_id' => $publishers[$index % $publishers->count()]->id,
                'title' => $title,
                'slug' => str($title)->slug(),
                'isbn' => $this->isbn13('978602'.str_pad((string) (100000 + $index * 137), 6, '0', STR_PAD_LEFT)),
                'description' => "Buku {$title} untuk koleksi perpustakaan.",
                'published_year' => $year,
                'stock' => $stock,
                'low_stock_threshold' => 2,
                'is_active' => true,
            ]);

            $book->authors()->attach(collect($authorIndexes)->map(fn (int $i) => $authors[$i]->id)->all());
            $book->categories()->attach($leafCategories->firstWhere('name', $categoryName)->id);
        }

        $people = [
            ['Aulia Rahmawati', 'mahasiswa'],
            ['Bima Prasetyo', 'mahasiswa'],
            ['Citra Lestari', 'mahasiswa'],
            ['Dimas Saputra', 'mahasiswa'],
            ['Eka Wulandari', 'dosen'],
            ['Fajar Nugroho', 'dosen'],
            ['Gita Permata', 'umum'],
            ['Hendra Wijaya', 'umum'],
        ];

        $registered = collect($people)->map(function (array $person, int $index) use ($members, $roles) {
            [$name, $roleSlug] = $person;

            return $members->register(
                [
                    'name' => $name,
                    'email' => str($name)->slug('.').'@perpustakaan.test',
                    'phone' => '0812345678'.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                ],
                [$roles->firstWhere('slug', $roleSlug)->id],
            );
        });

        $stocked = Book::query()->where('stock', '>', 2)->orderBy('id')->get();

        $loans->borrow($registered[0]->id, [
            ['book_id' => $stocked[0]->id, 'quantity' => 1],
            ['book_id' => $stocked[1]->id, 'quantity' => 1],
        ], now()->subDays(12));

        $loans->borrow($registered[1]->id, [
            ['book_id' => $stocked[2]->id, 'quantity' => 1],
        ], now()->subDays(2));

        $returned = $loans->borrow($registered[4]->id, [
            ['book_id' => $stocked[3]->id, 'quantity' => 1],
            ['book_id' => $stocked[4]->id, 'quantity' => 1],
        ], now()->subDays(20));

        $loans->returnLoan($returned, now()->subDays(2));

        Member::query()->whereKey($registered[7]->id)->update(['status' => Member::STATUS_SUSPENDED]);
    }

    private function isbn13(string $first12): string
    {
        $sum = 0;

        foreach (str_split($first12) as $index => $digit) {
            $sum += (int) $digit * ($index % 2 === 0 ? 1 : 3);
        }

        return $first12.((10 - ($sum % 10)) % 10);
    }
}
