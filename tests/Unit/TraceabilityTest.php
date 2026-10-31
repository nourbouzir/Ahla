<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/** docs/traceability.md must list every Must/Should FR and point only at tests that exist. */
class TraceabilityTest extends TestCase
{
    private function doc(): string
    {
        return file_get_contents(dirname(__DIR__, 2).'/docs/traceability.md');
    }

    public function test_every_functional_requirement_up_to_fr_20_is_listed(): void
    {
        foreach (range(1, 20) as $n) {
            $id = sprintf('FR-%02d', $n);
            $this->assertMatchesRegularExpression("/^\\| $id \\|/m", $this->doc(), "$id is missing from docs/traceability.md");
        }
    }

    public function test_every_named_test_exists(): void
    {
        preg_match_all('/`(\w+Test)::(test_\w+)`/', $this->doc(), $matches, PREG_SET_ORDER);
        $this->assertNotEmpty($matches);

        foreach ($matches as [, $class, $method]) {
            $file = glob(dirname(__DIR__)."/*/$class.php")[0] ?? null;
            $this->assertNotNull($file, "Test class $class not found");
            $this->assertStringContainsString("function $method(", file_get_contents($file), "$class::$method does not exist");
        }
    }

    public function test_every_must_requirement_row_names_at_least_one_test(): void
    {
        foreach (explode("\n", $this->doc()) as $line) {
            if (preg_match('/^\| (N?FR-\d+) /', $line, $m) && ! str_starts_with($m[1], 'NFR')) {
                $this->assertMatchesRegularExpression('/`\w+Test::test_\w+`/', $line, "{$m[1]} has no test");
            }
        }
    }
}
