<?php

namespace Tests\Feature;

use App\Contracts\Hosts\AlternativeHostFinder;
use App\Contracts\Hosts\AvailabilityChecker;
use App\Contracts\Hosts\HostEligibility;
use App\Contracts\Payments\PaymentGateway;
use App\Contracts\Translation\Translator;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * Real classes in production (and in development), fakes only under test (issue #37).
 * The production check starts a clean PHP process with APP_ENV=production and none of
 * the AHLA_* overrides PHPUnit injects, so it sees exactly what a server would see.
 */
class BindingsPerEnvironmentTest extends TestCase
{
    private const CONTRACTS = [
        PaymentGateway::class, Translator::class, HostEligibility::class, AvailabilityChecker::class, AlternativeHostFinder::class,
    ];

    /** @return array<string, string> contract => concrete class */
    private function resolvedIn(string $environment): array
    {
        $env = ['APP_ENV' => $environment, 'APP_KEY' => 'base64:'.base64_encode(str_repeat('k', 32)),
            'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => ':memory:', 'CACHE_STORE' => 'array', 'APP_DEBUG' => 'false'];

        // false = remove the variable from the child process.
        foreach (array_keys(array_merge(getenv(), $_ENV, $_SERVER)) as $key) {
            if (str_starts_with((string) $key, 'AHLA_')) {
                $env[$key] = false;
            }
        }

        $contracts = var_export(self::CONTRACTS, true);
        $code = '$app = require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();'
            ."echo json_encode(array_combine($contracts, array_map(fn (\$c) => get_class(app(\$c)), $contracts)));";

        $process = new Process([PHP_BINARY, '-d', 'display_errors=0', '-r', 'require "vendor/autoload.php"; '.$code], base_path(), $env);
        $process->mustRun();

        return json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
    }

    public function test_production_binds_only_real_implementations(): void
    {
        $bound = $this->resolvedIn('production');

        foreach (self::CONTRACTS as $contract) {
            $this->assertArrayHasKey($contract, $bound);
            $this->assertStringNotContainsString('Fakes', $bound[$contract], "$contract is bound to a fake in production: {$bound[$contract]}");
        }

        $this->assertSame([
            PaymentGateway::class => 'App\Modules\Payment\Gateways\SandboxPaymentGateway',
            Translator::class => 'App\Modules\Messaging\Services\LibreTranslateTranslator',
            HostEligibility::class => 'App\Modules\Verification\Services\VerifiedHostEligibility',
            AvailabilityChecker::class => 'App\Modules\Experience\Services\WeeklyAvailabilityChecker',
            AlternativeHostFinder::class => 'App\Modules\Matching\Services\MatchingAlternativeHostFinder',
        ], $bound);
    }

    public function test_local_development_uses_the_same_real_classes(): void
    {
        $this->assertSame($this->resolvedIn('production'), $this->resolvedIn('local'));
    }

    public function test_the_test_environment_uses_fakes(): void
    {
        foreach (self::CONTRACTS as $contract) {
            $this->assertStringContainsString('Fakes', get_class(app($contract)), "$contract should be a fake under test");
        }
    }

    public function test_a_misconfigured_binding_stops_the_application_at_boot(): void
    {
        $env = ['APP_ENV' => 'production', 'APP_KEY' => 'base64:'.base64_encode(str_repeat('k', 32)), 'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => ':memory:', 'CACHE_STORE' => 'array', 'AHLA_PAYMENT_GATEWAY' => 'Not\A\RealClass'];
        $process = new Process([PHP_BINARY, '-r', 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();'], base_path(), $env);
        $process->run();

        $this->assertNotSame(0, $process->getExitCode());
        $this->assertStringContainsString('must implement', $process->getOutput().$process->getErrorOutput());
    }
}
