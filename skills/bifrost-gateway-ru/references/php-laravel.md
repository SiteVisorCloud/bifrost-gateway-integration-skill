# PHP и Laravel

Всё та же идея: направляй клиент на `https://your-gateway.example.com/v1`, используй
`провайдер/модель` и ключ `sk-bf-...` из окружения. Ниже — три способа: пакет
`openai-php/client`, сырой cURL и **Laravel AI SDK**.

## PHP: пакет `openai-php/client`

`->withBaseUri()` принимает адрес **со схемой или без** (без схемы SDK сам
подставит `https://`). Значение по умолчанию — `api.openai.com/v1`, поэтому его
обязательно нужно переопределить на шлюз, иначе запрос уйдёт мимо.

**Responses API** доступен как `->responses()` начиная с `openai-php/client`
v0.13.0 — это приоритетный вариант; `->chat()` есть всегда и работает как fallback.

```php
<?php
use OpenAI;

$client = OpenAI::factory()
    ->withApiKey(getenv('BIFROST_API_KEY'))     // sk-bf-...
    ->withBaseUri('your-gateway.example.com/v1')        // без схемы; https:// подставится сам
    ->make();

// Приоритет — Responses API (v0.13.0+)
$response = $client->responses()->create([
    'model' => 'openai/gpt-4o-mini',              // провайдер/модель
    'input' => 'Привет!',
]);
echo $response->outputText;
```

Fallback на Chat Completions (старый пакет или если `responses()` недоступен):

```php
$response = $client->chat()->create([
    'model' => 'anthropic/claude-3-5-sonnet-20241022',
    'messages' => [['role' => 'user', 'content' => 'Привет!']],
]);
echo $response->choices[0]->message->content;
```

## PHP: сырой cURL без библиотек

См. готовые примеры в SKILL.md (разделы Responses API и Chat Completions) —
`curl_init` на `https://your-gateway.example.com/v1/responses` (или `/chat/completions`),
заголовок `Authorization: Bearer $BIFROST_API_KEY`, модель `провайдер/модель`.

## Laravel: Laravel AI SDK (`laravel/ai`)

Официальный SDK Laravel. Он умеет ходить в кастомный OpenAI-совместимый шлюз через
драйвер `openai-compatible` — это ровно наш случай. Формат запроса (Responses или
chat) SDK берёт на себя; тебе достаточно направить провайдер на шлюз.

### 1. Установка

```shell
composer require laravel/ai
php artisan vendor:publish --provider="Laravel\Ai\AiServiceProvider"
php artisan migrate
```

### 2. Провайдер-шлюз в `config/ai.php`

Заводим отдельный провайдер (назовём `bifrost`) с драйвером `openai-compatible`.
`url` обязателен и указывает на шлюз; `key` уходит как `Bearer` — это ключ `sk-bf-`.

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

> Два уровня «провайдера» не путать: провайдер Laravel AI SDK (`bifrost`) — это
> сам шлюз, а префикс в строке модели (`openai/`, `anthropic/`, `gemini/`, …) —
> это то, куда шлюз Bifrost маршрутизирует дальше. Поэтому `model` всегда в форме
> `провайдер/модель`, напр. `openai/gpt-4o-mini` или `anthropic/claude-3-5-sonnet-20241022`.

### 3. Вызов — указываем наш провайдер и модель

Анонимная функция `agent()`:

```php
use function Laravel\Ai\{agent};

$response = agent(instructions: 'Ты — эксперт по микроразметке Schema.org.')
    ->prompt(
        'Оцени качество разметки этой страницы: ...',
        provider: 'bifrost',              // наш провайдер-шлюз из config/ai.php
        model: 'anthropic/claude-3-5-sonnet-20241022',  // провайдер/модель для Bifrost
    );

return (string) $response;
```

Класс-агент (идиоматичный Laravel):

```php
<?php
namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;

#[Provider('bifrost')]                 // провайдер-шлюз
#[Model('openai/gpt-4o-mini')]           // провайдер/модель
class SchemaCoach implements Agent
{
    use Promptable;

    public function instructions(): string
    {
        return 'Ты оцениваешь качество микроразметки Schema.org на странице.';
    }
}

// вызов
$response = (new SchemaCoach)->prompt('Проверь эту страницу: ...');
return (string) $response;
```

Стриминг (можно возвращать прямо из роута):

```php
return agent(instructions: '...')
    ->stream('Длинный ответ...', provider: 'bifrost', model: 'openai/gpt-4o');
```

### Альтернатива: переопределить встроенный драйвер `openai`

Если не хочешь заводить отдельный провайдер, можно направить встроенный драйвер
`openai` на шлюз через его поле `url` (env `OPENAI_URL`) и класть `sk-bf-` в
`OPENAI_API_KEY`. Тогда провайдер по умолчанию пойдёт через шлюз. Именованный
провайдер `openai-compatible` (выше) семантически чище — предпочитай его.
