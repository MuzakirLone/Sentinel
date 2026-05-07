<?php

/**
 * Sentinel Performance Validation Benchmark Suite
 * Measures strictly the Ingestion API endpoint performance.
 */

if (php_sapi_name() !== 'cli') {
    exit("This script must be run via CLI.\n");
}

$requests = (int) ($argv[1] ?? 500);
$url = getenv('APP_URL') ?: 'http://localhost:8585';
$apiUrl = rtrim($url, '/') . '/api/v1/events';

// Default mock dev API key
$apiKey = 'sk_test_api_key';

echo "╔══════════════════════════════════════════════╗\n";
echo "║      Sentinel RPS Benchmarking Suite         ║\n";
echo "╚══════════════════════════════════════════════╝\n";
echo "Targeting: {$apiUrl}\n";
echo "Payloads to fire: {$requests}\n";
echo "------------------------------------------------\n";

$payload = json_encode([
    'event_type' => 'login_attempt',
    'user_id'    => 'user_12345',
    'ip'         => '192.168.1.100',
    'timestamp'  => time(),
    'metadata'   => ['benchmark' => true],
]);

$mh = curl_multi_init();
$curls = [];

$startTotal = microtime(true);

for ($i = 0; $i < $requests; $i++) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // Explicit header to bypass potential IP-based rate limiting for benchmarks
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-API-Key: ' . $apiKey,
        'X-Benchmark-Bypass: true' // RateLimitMiddleware handles this natively
    ]);
    
    curl_multi_add_handle($mh, $ch);
    $curls[] = $ch;
}

echo "Executing async cURL batch...\n";

$running = null;
do {
    curl_multi_exec($mh, $running);
    curl_multi_select($mh);
} while ($running > 0);

$endTotal = microtime(true);
$duration = $endTotal - $startTotal;

$successStatus = 0;
$failStatus = 0;

foreach ($curls as $ch) {
    if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == 200 || curl_getinfo($ch, CURLINFO_HTTP_CODE) == 201) {
        $successStatus++;
    } else {
        $failStatus++;
    }
    curl_multi_remove_handle($mh, $ch);
    curl_close($ch);
}

curl_multi_close($mh);

echo "\n====== Benchmark Results ======\n";
echo sprintf("Total Time:     %.4f seconds\n", $duration);
echo sprintf("Throughput:     %.2f Requests / Second\n", $requests / $duration);
echo sprintf("Success Count:  %d\n", $successStatus);
echo sprintf("Fail/Block:     %d\n", $failStatus);
echo "===============================\n\n";
