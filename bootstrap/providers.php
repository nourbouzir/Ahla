<?php

use App\Modules\Modules;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    // One provider per module; the list lives in App\Modules\Modules.
    ...Modules::providers(),
];
