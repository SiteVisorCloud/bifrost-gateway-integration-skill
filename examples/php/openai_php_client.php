<?php
/**
 * openai-php/client pointed at the Bifrost gateway.
 *
 *   composer require openai-php/client
 *   export BIFROST_BASE_URL=https://your-gateway.example.com/v1
 *   export BIFROST_API_KEY=sk-bf-...
 *   php openai_php_client.php
 *
 * ->withBaseUri() accepts host+path with or without a scheme (https:// is
 * prepended if omitted). The default is api.openai.com/v1, so overriding it is
 * mandatory or the request bypasses the gateway.
 */

require __DIR__ . '/vendor/autoload.php';

// BIFROST_BASE_URL includes the scheme; withBaseUri keeps it as-is.
$baseUri = getenv('BIFROST_BASE_URL') ?: 'https://your-gateway.example.com/v1';

$client = OpenAI::factory()
    ->withApiKey(getenv('BIFROST_API_KEY'))   // sk-bf-...
    ->withBaseUri($baseUri)
    ->make();

// Preferred: Responses API (requires openai-php/client >= v0.13.0)
$response = $client->responses()->create([
    'model' => 'openai/gpt-4o-mini',          // provider/model
    'input' => 'Say hello in one short sentence.',
]);
echo $response->outputText . PHP_EOL;

// Fallback: Chat Completions (any provider via the provider/ prefix)
// $response = $client->chat()->create([
//     'model' => 'anthropic/claude-3-5-sonnet-20241022',
//     'messages' => [['role' => 'user', 'content' => 'Say hello.']],
// ]);
// echo $response->choices[0]->message->content . PHP_EOL;
