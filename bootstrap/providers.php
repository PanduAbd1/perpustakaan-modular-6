<?php

use App\Providers\AppServiceProvider;
use Modules\Auth\Providers\AuthServiceProvider;
use Modules\Catalog\Providers\CatalogServiceProvider;
use Modules\Circulation\Providers\CirculationServiceProvider;
use Modules\Membership\Providers\MembershipServiceProvider;
use Modules\Orders\Providers\OrderServiceProvider;
use Modules\Products\Providers\ProductServiceProvider;

return [
    AppServiceProvider::class,
    AuthServiceProvider::class,
    ProductServiceProvider::class,
    OrderServiceProvider::class,
    CatalogServiceProvider::class,
    MembershipServiceProvider::class,
    CirculationServiceProvider::class,
];
