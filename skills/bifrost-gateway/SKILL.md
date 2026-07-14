---
name: bifrost-gateway
description: >-
  Routes EVERY LLM / AI-provider request through a Bifrost (OpenAI-compatible)
  gateway so that all calls are logged and tracked in one place. Use this skill
  WHENEVER you write, configure, or debug any code that calls a large language
  model or AI provider — OpenAI/GPT, Anthropic/Claude, Google Gemini, DeepSeek,
  xAI/Grok, Perplexity, or any other — in ANY language (Python, PHP,
  JavaScript/TypeScript, Go, curl) or framework (Laravel AI SDK, LangChain,
  LlamaIndex, Vercel AI SDK). This covers new integrations, editing existing
  client setup, base_url / api_key configuration, chat completions, the
  Responses API, embeddings, and streaming. Trigger even when the user only says
  "call gpt-4o", "add Claude to this script", "sk-bf-...", "generate text with
  an LLM", or names a provider SDK without mentioning the gateway. NEVER call
  provider APIs directly — the whole point of the gateway is that no request
  goes around it.
---

# Bifrost AI Gateway

There is a **Bifrost** LLM gateway (OpenAI-compatible) at
`https://your-gateway.example.com`. Every call to every AI provider must go
through it, so that all requests are logged and tracked in one place. Hitting
`api.openai.com`, `api.anthropic.com`, `generativelanguage.googleapis.com`, etc.
directly defeats the whole purpose: those requests would be invisible to the
gateway's logging.

So the job of this skill is simple: **the moment you touch code that talks to an
LLM, point it at the gateway instead of the provider.** In practice that's almost
always a one-line change (the `base_url`) plus using the gateway key.

> Replace `your-gateway.example.com` with your own gateway host (set it once via
> the `BIFROST_BASE_URL` env var). Everything else stays identical.

## No gateway yet? — Bifrost quick start (one-time)

If no gateway is running, stand one up with Bifrost (the open-source LLM gateway):

```bash
docker run -p 8080:8080 -v "$(pwd)/data:/app/data" maximhq/bifrost
# or, without Docker:  npx -y @maximhq/bifrost
```

Open `http://localhost:8080`, add your providers' API keys, and create a virtual
key under Governance (that key — commonly prefixed `sk-bf-` — is your
`BIFROST_API_KEY`). Then `BIFROST_BASE_URL` is `http://localhost:8080/v1` (or your
deployed host + `/v1`). Full steps:
[references/install-bifrost.md](references/install-bifrost.md).

## The three things you always need

1. **Base URL:** `https://your-gateway.example.com/v1`
2. **Model string:** `provider/model` — e.g. `openai/gpt-4o-mini`,
   `anthropic/claude-3-5-sonnet-20241022`
3. **Auth:** `Authorization: Bearer <key>`, where the key **always starts with
   `sk-bf-`**

Because the gateway speaks the OpenAI protocol, any tool, SDK, or language that
can talk to OpenAI can talk to the gateway — only the base URL and the model
string change. That's the general rule for **any** language, even ones not shown
below.

## The API key

- The key is provided **in the chat**. It always starts with `sk-bf-` (that's how
  you recognize it). If you see a string like `sk-bf-abc123...` in the
  conversation, that's the gateway key.
- **Never hard-code the key in source files.** In real code read it from an
  environment variable — default to `BIFROST_API_KEY` (fall back to
  `OPENAI_API_KEY` if a tool/SDK only reads that name).
- If there's no key yet but you need runnable code, use the env var and note that
  it must be set: `export BIFROST_API_KEY=sk-bf-...`. Don't invent a fake
  `sk-bf-` key that looks real.

## The model string

On the unified endpoint the model is **always** `provider/model`. The prefix tells
the gateway which provider to route to; the suffix is that provider's own model
name.

**Provider prefixes are the canonical Bifrost keys, not the everyday product
names.** It's especially easy to get Claude and Grok wrong:

| Provider (common name) | prefix | example model string |
|---|---|---|
| OpenAI | `openai` | `openai/gpt-4o-mini`, `openai/gpt-4o` |
| Claude (Anthropic) | `anthropic` | `anthropic/claude-3-5-sonnet-20241022` |
| Gemini (Google) | `gemini` | `gemini/gemini-1.5-pro`, `gemini/gemini-2.0-flash` |
| DeepSeek | `deepseek` | `deepseek/deepseek-chat`, `deepseek/deepseek-reasoner` |
| Grok (xAI) | `xai` | `xai/grok-2-latest` |
| Perplexity | `perplexity` | `perplexity/sonar-pro` |

So Claude is `anthropic/...` (NOT `claude/...`) and Grok is `xai/...` (NOT
`grok/...`). Which providers are available depends on your gateway's config; the
model names (the part after the slash) and the current list come from the
datasheet — see below.

Exact model names, supported parameters, context windows, and **pricing** for
each model live in the Bifrost datasheet (the source of truth — prices change):
- Model parameters: <https://getbifrost.ai/datasheet/model-parameters>
- Models, limits, pricing: <https://getbifrost.ai/datasheet>

A summary of request parameters and what differs between providers is in
[references/models-and-parameters.md](references/models-and-parameters.md). If
you're unsure of an exact model name or prefix, keep the `provider/model` shape,
fall back to an example from the table, and ask the user rather than guessing.

## Which endpoint: Responses API → Chat Completions

**Default to the Responses API** (`/v1/responses`) — it's OpenAI's newer, current
format. The gateway accepts `/v1/responses` with any `provider/model` and
translates the request for the target provider, so the Responses-first rule works
for every provider, not just OpenAI.

**Fall back to Chat Completions** (`/v1/chat/completions`) only when the client,
SDK, or framework can't do the Responses API (or you're unsure it can). Both
endpoints use the same `sk-bf-...` key and the same `provider/model` format.

Rough guide to who supports the Responses API:

| Supports Responses API (prefer) | Chat Completions only (fallback) |
|---|---|
| Recent OpenAI SDK: `client.responses.create` (Python/Node) | Older OpenAI SDK versions |
| Vercel AI SDK: `openai.responses("model")` | LlamaIndex `OpenAILike`, many "OpenAI-compatible" clients |
| Direct HTTP / curl to `/v1/responses` | `openai-php` if its version lacks `->responses()` |
| `langchain-openai` with `use_responses_api=True` | Any client with no explicit Responses support |

The native Anthropic and Google SDKs use their own formats (`messages` /
`generate_content`) — neither Responses nor Chat Completions; see
[references/native-sdks.md](references/native-sdks.md).

## Approach 1 (default): unified endpoint, Responses-first

### Responses API — the preferred path

**curl:**

```bash
curl https://your-gateway.example.com/v1/responses \
  -H "Authorization: Bearer $BIFROST_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "model": "openai/gpt-4o-mini",
    "input": "Hello!"
  }'
```

The answer is in `output` / `output_text`, not `choices[].message`.

**Python (OpenAI SDK):**

```python
import os
from openai import OpenAI

client = OpenAI(
    base_url=os.environ.get("BIFROST_BASE_URL", "https://your-gateway.example.com/v1"),
    api_key=os.environ["BIFROST_API_KEY"],   # sk-bf-...
)

resp = client.responses.create(
    model="openai/gpt-4o-mini",               # provider/model
    input="Hello!",
)
print(resp.output_text)

# Multi-turn: chain with previous_response_id
follow_up = client.responses.create(
    model="openai/gpt-4o-mini",
    input="Now make it funnier.",
    previous_response_id=resp.id,
)
print(follow_up.output_text)
```

**PHP (raw cURL — works on any PHP version):**

```php
<?php
$ch = curl_init(getenv('BIFROST_BASE_URL') . '/responses');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . getenv('BIFROST_API_KEY'),  // sk-bf-...
        'Content-Type: application/json',
    ],
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        'model' => 'openai/gpt-4o-mini',                        // provider/model
        'input' => 'Hello!',
    ], JSON_UNESCAPED_UNICODE),
]);
$data = json_decode(curl_exec($ch), true);
curl_close($ch);
echo $data['output_text'] ?? json_encode($data['output'], JSON_UNESCAPED_UNICODE);
```

### Chat Completions — fallback (when Responses isn't supported)

**curl:**

```bash
curl https://your-gateway.example.com/v1/chat/completions \
  -H "Authorization: Bearer $BIFROST_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "model": "anthropic/claude-3-5-sonnet-20241022",
    "messages": [{"role": "user", "content": "Hello!"}]
  }'
```

**Python (OpenAI SDK):**

```python
import os
from openai import OpenAI

client = OpenAI(
    base_url=os.environ.get("BIFROST_BASE_URL", "https://your-gateway.example.com/v1"),
    api_key=os.environ["BIFROST_API_KEY"],   # sk-bf-...
)
resp = client.chat.completions.create(
    model="anthropic/claude-3-5-sonnet-20241022",
    messages=[{"role": "user", "content": "Hello!"}],
)
print(resp.choices[0].message.content)
```

More examples:
- **PHP** (`openai-php/client`) and **Laravel** (Laravel AI SDK, `laravel/ai`) —
  in [references/php-laravel.md](references/php-laravel.md).
- **JavaScript / TypeScript** and frameworks (LangChain, LlamaIndex, Vercel AI
  SDK) — in [references/javascript-and-frameworks.md](references/javascript-and-frameworks.md).

## Approach 2: native provider SDK drop-in — when the code must keep a provider's own SDK

Sometimes rewriting to the OpenAI/Responses shape isn't worth it — e.g. code
already uses the Anthropic SDK with its provider-specific fields, or the Google
GenAI SDK. Bifrost exposes provider-native endpoints so you keep the SDK and
change **only** the base URL and the key (still the `sk-bf-...` key):

| SDK | base URL to set | model naming |
|---|---|---|
| OpenAI SDK | `https://your-gateway.example.com/openai` | `gpt-4o-mini` (or `provider/model` to cross-route) |
| Anthropic SDK | `https://your-gateway.example.com/anthropic` | `claude-3-5-sonnet-20241022` |
| Google GenAI SDK | `https://your-gateway.example.com/genai` | `gemini-1.5-pro` |

Detailed examples are in [references/native-sdks.md](references/native-sdks.md).
For new code prefer Approach 1 (unified endpoint, Responses API); use Approach 2
only to avoid rewriting working provider-specific code.

## Before you finish — quick self-check

After writing or editing LLM code, confirm all four:

- [ ] The base URL / host points at your gateway (not `api.openai.com`,
      `api.anthropic.com`, `generativelanguage.googleapis.com`, or a bare
      provider host).
- [ ] The request format is the Responses API (`/v1/responses`), with Chat
      Completions (`/v1/chat/completions`) only where the client can't do
      Responses.
- [ ] The model string is `provider/model` on the `/v1` endpoint (or the
      provider's native name on a `/openai` `/anthropic` `/genai` drop-in).
- [ ] The key is the `sk-bf-...` gateway key, read from an env var, not
      hard-coded.
- [ ] No execution path reaches a provider directly — including SDK defaults you
      didn't override. If a client's base URL isn't overridden, it will silently
      bypass the gateway, so set it explicitly.

If any code still reaches a provider directly, fix it: a bypassed request is
invisible to the gateway, which is exactly what this setup exists to prevent.
