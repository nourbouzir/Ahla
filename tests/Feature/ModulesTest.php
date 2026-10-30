<?php

namespace Tests\Feature;

use App\Modules\Modules;
use Tests\TestCase;

class ModulesTest extends TestCase
{
    public function test_every_module_folder_is_listed_in_the_registry(): void
    {
        $folders = array_map('basename', glob(app_path('Modules/*'), GLOB_ONLYDIR));
        $names = Modules::NAMES;
        sort($folders);
        sort($names);

        // Fails when someone adds a module folder but forgets App\Modules\Modules.
        $this->assertSame($names, $folders);
    }

    public function test_every_module_registers_its_provider_and_resources(): void
    {
        $slashes = fn (string $path) => str_replace('\\', '/', $path);
        $migrationPaths = array_map($slashes, $this->app['migrator']->paths());
        $viewHints = $this->app['view']->getFinder()->getHints();
        $langHints = $this->app['translator']->getLoader()->namespaces();

        foreach (Modules::NAMES as $name) {
            $namespace = strtolower($name);

            $this->assertNotEmpty($this->app->getProviders(Modules::provider($name)), "$name provider is not registered");
            $this->assertContains($slashes(app_path("Modules/$name/Database/Migrations")), $migrationPaths, "$name migrations");
            $this->assertArrayHasKey($namespace, $viewHints, "$name views");
            $this->assertArrayHasKey($namespace, $langHints, "$name translations");
            $this->assertTrue(class_exists(Modules::seeder($name)), "$name seeder");
            $this->assertFileExists(app_path("Modules/$name/routes/web.php"));
            $this->assertFileExists(app_path("Modules/$name/routes/api.php"));
        }
    }

    public function test_the_root_seeder_runs_every_module_seeder(): void
    {
        // seed() throws if any module seeder class cannot be resolved.
        $this->expectNotToPerformAssertions();

        $this->seed();
    }
}
