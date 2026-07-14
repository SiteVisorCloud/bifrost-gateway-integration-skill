# PHP and Laravel

Same idea as always: point the client at `https://your-gateway.example.com/v1`,
use `provider/model`, and read the `sk-bf-...` key from the environment. Below are
three ways: the `openai-php/client` package, raw cURL, and the **Laravel AI SDK**.

## PHP: the `openai-php/client` package

`->withBaseUri()` accepts an address **with or without a scheme** (without a
scheme the SDK prepends `https://`). The default is `api.openai.com/v1`, so you
MUST override it to the gateway or the request goes around it.

The **Responses API** is available as `->responses()` starting with
`openai-php/client` v0.13.0 — that's the preferred path; `->chat()` always exists
and works as a fallback.

```php
<?php
use OpenAI;

$client = OpenAI::factory()
    ->withApiKey(getenv('BIFROST_API_KEY'))       // sk-bf-...
    ->withBaseUri('your-gateway.example.com/v1')  // no scheme; https:// is prepended
    ->make();

// Prefer the Responses API (v0.13.0+)
$response = $client->responses()->create([
    'model' => 'openai/gpt-4o-mini',              // provider/model
    'input' => 'Hello!',
]);
echo $response->outputText;
```

Fallback to Chat Completions (older package, or if `responses()` is unavailable):

```php
$response = $client->chat()->create([
    'model' => 'anthropic/claude-3-5-sonnet-20241022',
    'messages' => [['role' => 'user', 'content' => 'Hello!']],
]);
echo $response->choices[0]->message->content;
```

## PHP: raw cURL, no library

See the ready examples in SKILL.md (Responses API and Chat Completions sections):
`curl_init` against `https://your-gateway.example.com/v1/responses` (or
`/chat/completions`), header `Authorization: Bearer $BIFROST_API_KEY`, and a
`provider/model` model string.

## Laravel: the Laravel AI SDK (`laravel/ai`)

Laravel's official AI SDK. It can talk to a custom OpenAI-compatible gateway via
the `openai-compatible` driver — exactly our case. The SDK handles the wire format
(Responses vs chat) for you; you just point the provider at the gateway.

### 1. Install

```shell
composer require laravel/ai
php artisan vendor:publish --provider="Laravel\Ai\AiServiceProvider"
php artisan migrate
```

### 2. A gateway provider in `config/ai.php`

Define a dedicated provider (call it `bifrost`) with the `openai-compatible`
driver. `url` is required and points at the gateway; `key` is sent as a `Bearer`
token — that's the `sk-bf-` key.

```php
// config/ai.php
'providers' => [
    'bifrost' => [
        'driver' => 'openai-compatible',
        'url'    => env('BIFROST_BASE_URL', 'https://your-gateway.example.com/v1'),
        'key'    => env('BIFROST_API_KEY'),   // sk-bf-...
        'models' => [
            'text' => ['default' => env('BIFROST_MODEL', 'openai/gpt-4o-mini')],
        ],
    ],
],
```

```ini
# .env
BIFROST_BASE_URL=https://your-gateway.example.com/v1
BIFROST_API_KEY=sk-bf-...
BIFROST_MODEL=openai/gpt-4o-mini
```

> Don't confuse the two "provider" layers: the Laravel AI SDK provider
> (`bifrost`) is the gateway itself, while the prefix in the model string
> (`openai/`, `anthropic/`, `gemini/`, …) is where Bifrost routes next. So `model`
> is always `provider/model`, e.g. `openai/gpt-4o-mini` or
> `anthropic/claude-3-5-sonnet-20241022`.

### 3. Call it — pick our provider and model

Anonymous `agent()` function:

```php
use function Laravel\Ai\{agent};

$response = agent(instructions: 'You are an expert on Schema.org structured data.')
    ->prompt(
        'Assess the markup quality of this page: ...',
        provider: 'bifrost',                             // our gateway provider from config/ai.php
        model: 'anthropic/claude-3-5-sonnet-20241022',   // provider/model for Bifrost
    );

return (string) $response;
```

Agent class (idiomatic Laravel):

```php
<?php
namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;

#[Provider('bifrost')]                    // gateway provider
#[Model('openai/gpt-4o-mini')]           // provider/model
class SchemaCoach implements Agent
{
    use Promptable;

    public function instructions(): string
    {
        return 'You assess the quality of Schema.org markup on a page.';
    }
}

// call it
$response = (new SchemaCoach)->prompt('Check this page: ...');
return (string) $response;
```

Streaming (returnable straight from a route):

```php
return agent(instructions: '...')
    ->stream('A long answer...', provider: 'bifrost', model: 'openai/gpt-4o');
```

### Alternative: override the built-in `openai` driver

If you'd rather not define a separate provider, you can point the built-in
`openai` driver at the gateway via its `url` key (env `OPENAI_URL`) and put the
`sk-bf-` key in `OPENAI_API_KEY`. Then the default provider routes through the
gateway. The named `openai-compatible` provider (above) is semantically cleaner —
prefer it.
