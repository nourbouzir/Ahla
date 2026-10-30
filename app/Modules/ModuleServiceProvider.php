<?php

namespace App\Modules;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use ReflectionClass;

/**
 * Base class for every module's provider. A module only has to put files in
 * its own folder; nothing outside the folder needs editing:
 *
 *   Database/Migrations/   run by `php artisan migrate`
 *   Views/                 view('<module>::name')
 *   lang/{en,fr,ar}/       __('<module>::file.key')
 *   routes/web.php         loaded under the "web" middleware group
 *   routes/api.php         loaded under the "api" group, prefixed with /api
 *
 * <module> is the lower-cased folder name, e.g. "booking". This is what keeps
 * two people from editing the same routes, language or migration file.
 */
abstract class ModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom($this->modulePath('Database/Migrations'));
        $this->loadViewsFrom($this->modulePath('Views'), $this->moduleNamespace());
        $this->loadTranslationsFrom($this->modulePath('lang'), $this->moduleNamespace());
        $this->loadModuleRoutes();
    }

    protected function moduleNamespace(): string
    {
        return strtolower(basename($this->modulePath()));
    }

    protected function modulePath(string $relative = ''): string
    {
        $dir = dirname((new ReflectionClass($this))->getFileName());

        return $relative === ''
            ? $dir
            : $dir.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
    }

    private function loadModuleRoutes(): void
    {
        // A cached route table already contains every module's routes.
        if ($this->app->routesAreCached()) {
            return;
        }

        if (is_file($web = $this->modulePath('routes/web.php'))) {
            Route::middleware('web')->group($web);
        }

        if (is_file($api = $this->modulePath('routes/api.php'))) {
            Route::middleware('api')->prefix('api')->group($api);
        }
    }
}
