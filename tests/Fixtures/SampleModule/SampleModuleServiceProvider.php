<?php

namespace Tests\Fixtures\SampleModule;

use App\Modules\ModuleServiceProvider;

// Throwaway module used only by ModuleLoadingTest, so the loading mechanism is
// tested without putting fake routes in the real modules.
class SampleModuleServiceProvider extends ModuleServiceProvider {}
