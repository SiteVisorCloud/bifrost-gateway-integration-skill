# Laravel example — Laravel AI SDK through the gateway

Uses the official [Laravel AI SDK](https://laravel.com/docs/13.x/ai-sdk)
(`laravel/ai`). The gateway is added as an `openai-compatible` provider, so all
traffic flows through Bifrost while you keep idiomatic Laravel code.

## 1. Install

```bash
composer require laravel/ai
php artisan vendor:publish --provider="Laravel\Ai\AiServiceProvider"
php artisan migrate
```

## 2. Configure the gateway provider

Merge the `providers` entry from [`config-ai.php`](config-ai.php) into your
published `config/ai.php`, and set the env vars from [`.env.example`](.env.example):

```ini
BIFROST_BASE_URL=https://your-gateway.example.com/v1
BIFROST_API_KEY=sk-bf-...
BIFROST_MODEL=openai/gpt-4o-mini
```

## 3. Use it

- [`SchemaAgent.php`](SchemaAgent.php) — an agent class with `#[Provider('bifrost')]`
  and `#[Model('openai/gpt-4o-mini')]` (put it in `app/Ai/Agents/`).
- [`usage.php`](usage.php) — calling an agent, and the anonymous `agent()` function
  with per-call `provider:` / `model:` overrides.

> Two "provider" layers: the Laravel AI SDK provider (`bifrost`) is the gateway;
> the prefix inside the model string (`openai/`, `anthropic/`, …) tells Bifrost
> where to route. So `model` is always `provider/model`.
