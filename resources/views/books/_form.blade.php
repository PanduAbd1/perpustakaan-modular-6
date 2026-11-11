@php($editing = isset($book))

<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <div class="md:col-span-2">
        <x-input-label for="title" value="Judul" />
        <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title', $editing ? $book->title : '')" required />
    </div>

    <div>
        <x-input-label for="isbn" value="ISBN (10 atau 13 digit)" />
        <x-text-input id="isbn" name="isbn" type="text" class="mt-1 block w-full" :value="old('isbn', $editing ? $book->isbn->getValue() : '')" required />
    </div>

    <div>
        <x-input-label for="slug" value="Slug" />
        <x-text-input id="slug" name="slug" type="text" class="mt-1 block w-full" :value="old('slug', $editing ? $book->slug : '')" :required="$editing" />
    </div>

    <div>
        <x-input-label for="publisher_id" value="Penerbit" />
        <select id="publisher_id" name="publisher_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            <option value="">-</option>
            @foreach ($publishers as $publisher)
                <option value="{{ $publisher->id }}" @selected(old('publisher_id', $editing ? $book->publisher_id : null) == $publisher->id)>{{ $publisher->name }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <x-input-label for="published_year" value="Tahun terbit" />
        <x-text-input id="published_year" name="published_year" type="number" class="mt-1 block w-full" :value="old('published_year', $editing ? $book->published_year : '')" />
    </div>

    <div>
        <x-input-label for="stock" value="Stok" />
        <x-text-input id="stock" name="stock" type="number" min="0" class="mt-1 block w-full" :value="old('stock', $editing ? $book->stock : 1)" required />
    </div>

    <div class="flex items-end">
        <input type="hidden" name="is_active" value="0">
        <label class="inline-flex items-center space-x-2">
            <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300" @checked(old('is_active', $editing ? $book->is_active : true))>
            <span class="text-sm text-gray-600">Aktif (boleh dipinjam)</span>
        </label>
    </div>

    <div class="md:col-span-2">
        <x-input-label for="description" value="Deskripsi" />
        <textarea id="description" name="description" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('description', $editing ? $book->description : '') }}</textarea>
    </div>

    <div>
        <x-input-label for="author_ids" value="Penulis" />
        <select id="author_ids" name="author_ids[]" multiple size="6" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            @foreach ($authors as $author)
                <option value="{{ $author->id }}" @selected(in_array($author->id, old('author_ids', $editing ? $book->authors->pluck('id')->all() : [])))>{{ $author->name }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <x-input-label for="category_ids" value="Kategori" />
        <select id="category_ids" name="category_ids[]" multiple size="6" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected(in_array($category->id, old('category_ids', $editing ? $book->categories->pluck('id')->all() : [])))>
                    {{ $category->parent ? $category->parent->name.' › ' : '' }}{{ $category->name }}
                </option>
            @endforeach
        </select>
    </div>
</div>

<div class="mt-6 flex items-center space-x-3">
    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Simpan</button>
    <a href="{{ route('books.index') }}" class="text-gray-600 hover:text-gray-900">Batal</a>
</div>
