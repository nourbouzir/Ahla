<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Tests\Fixtures\SampleModule\SampleModuleServiceProvider;
use Tests\TestCase;

class ModuleLoadingTest extends TestCase
{
    public function test_a_module_provider_loads_its_web_and_api_routes(): void
    {
        $this->app->register(SampleModuleServiceProvider::class);

        $this->get('/_sample-module')->assertOk()->assertSee('web ok');
        $this->getJson('/api/sample-module')->assertOk()->assertJson(['module' => 'sample']);
    }

    public function test_module_routes_get_the_right_middleware_group(): void
    {
        $this->app->register(SampleModuleServiceProvider::class);
        $routes = $this->app['router']->getRoutes();

        $this->assertContains('web', $routes->match(Request::create('/_sample-module'))->gatherMiddleware());
        $this->assertContains('api', $routes->match(Request::create('/api/sample-module'))->gatherMiddleware());
    }
}
