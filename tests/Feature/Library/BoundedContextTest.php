<?php

use Modules\Catalog\Services\BookService;
use Modules\Membership\Services\MemberService;
use Modules\Products\Services\ProductService;
use Modules\Shared\Contracts\BookServiceInterface;
use Modules\Shared\Contracts\MemberServiceInterface;
use Modules\Shared\Contracts\ProductServiceInterface;

function phpFilesIn(string $module): array
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path("Modules/{$module}")));

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }

    return $files;
}

it('tidak membiarkan bounded context saling mengimpor model, service, atau tabel context lain', function (string $module, array $forbidden) {
    $pattern = '/Modules\\\\('.implode('|', $forbidden).')\\\\/';

    $violations = collect(phpFilesIn($module))
        ->filter(fn (string $path) => preg_match($pattern, file_get_contents($path)) === 1)
        ->map(fn (string $path) => str_replace(app_path('Modules/'), '', $path))
        ->values()
        ->all();

    expect($violations)->toBe([]);
})->with([
    'Catalog' => ['Catalog', ['Membership', 'Circulation', 'Products', 'Orders', 'Auth']],
    'Membership' => ['Membership', ['Catalog', 'Circulation', 'Products', 'Orders', 'Auth']],
    'Circulation' => ['Circulation', ['Catalog', 'Membership', 'Products', 'Orders', 'Auth']],
    'Products' => ['Products', ['Orders', 'Catalog', 'Membership', 'Circulation', 'Auth']],
    'Orders' => ['Orders', ['Products', 'Catalog', 'Membership', 'Circulation', 'Auth']],
    'Auth' => ['Auth', ['Products', 'Orders', 'Catalog', 'Membership', 'Circulation']],
    'Shared Kernel' => ['Shared', ['Catalog', 'Membership', 'Circulation', 'Products', 'Orders', 'Auth']],
]);

it('mengikat contract ke implementasi di container', function () {
    expect(app(ProductServiceInterface::class))->toBeInstanceOf(ProductService::class)
        ->and(app(BookServiceInterface::class))->toBeInstanceOf(BookService::class)
        ->and(app(MemberServiceInterface::class))->toBeInstanceOf(MemberService::class);
});
