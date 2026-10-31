<?php

// Small load generator used for docs/performance.md (the k6 scripts in tests/load/ do the same job
// where k6 is installed). Plain PHP + curl, so it runs anywhere the app does.
//
//   php scripts/loadcheck.php http://localhost:8000 20 200 / /search?city=Tunis "/api/search?language=fr"
//
// args: base-url, concurrency, requests per path, then one or more paths.
// Prints requests, errors, p50, p95, max (milliseconds) and requests per second for each path.

[$script, $base, $concurrency, $total] = array_pad($argv, 4, null);
$paths = array_slice($argv, 4);

if (! $base || ! $concurrency || ! $total || ! $paths) {
    fwrite(STDERR, "usage: php scripts/loadcheck.php <base-url> <concurrency> <requests-per-path> <path>...\n");
    exit(1);
}

$percentile = function (array $values, float $p): float {
    sort($values);

    return $values ? $values[(int) max(0, ceil($p / 100 * count($values)) - 1)] : 0.0;
};

foreach ($paths as $path) {
    $mh = curl_multi_init();
    $times = [];
    $errors = 0;
    $started = 0;
    $active = [];
    $wall = microtime(true);

    $launch = function () use (&$started, &$active, $mh, $base, $path, $total) {
        if ($started >= $total) {
            return;
        }
        $ch = curl_init(rtrim($base, '/').$path);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30, CURLOPT_HTTPHEADER => ['Accept-Language: en']]);
        curl_multi_add_handle($mh, $ch);
        $active[(int) $ch] = $ch;
        $started++;
    };

    for ($i = 0; $i < $concurrency; $i++) {
        $launch();
    }

    while ($active) {
        curl_multi_exec($mh, $running);
        curl_multi_select($mh, 0.05);

        while ($info = curl_multi_info_read($mh)) {
            $ch = $info['handle'];
            $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $times[] = curl_getinfo($ch, CURLINFO_TOTAL_TIME) * 1000;
            $errors += ($info['result'] !== CURLE_OK || $code >= 400) ? 1 : 0;
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
            unset($active[(int) $ch]);
            $launch();
        }
    }

    $elapsed = microtime(true) - $wall;
    printf("%-55s n=%d err=%d p50=%.0fms p95=%.0fms max=%.0fms rps=%.1f\n", $path, count($times), $errors,
        $percentile($times, 50), $percentile($times, 95), max($times), count($times) / $elapsed);
}
