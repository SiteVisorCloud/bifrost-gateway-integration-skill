<?php
/**
 * No library — raw cURL to the Responses API through the Bifrost gateway.
 *
 *   export BIFROST_BASE_URL=https://your-gateway.example.com/v1
 *   export BIFROST_API_KEY=sk-bf-...
 *   php raw_curl.php
 */

$baseUrl = getenv('BIFROST_BASE_URL') ?: 'https://your-gateway.example.com/v1';
$apiKey  = getenv('BIFROST_API_KEY');   // sk-bf-...

$ch = curl_init($baseUrl . '/responses');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json',
    ],
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        'model' => 'openai/gpt-4o-mini',    // provider/model
        'input' => 'Say hello in one short sentence.',
    ], JSON_UNESCAPED_UNICODE),
    CURLOPT_TIMEOUT => 120,
]);

$raw = curl_exec($ch);
if ($raw === false) {
    fwrite(STDERR, 'cURL error: ' . curl_error($ch) . PHP_EOL);
    exit(1);
}
curl_close($ch);

$data = json_decode($raw, true);
echo ($data['output_text'] ?? json_encode($data['output'] ?? $data, JSON_UNESCAPED_UNICODE)) . PHP_EOL;
