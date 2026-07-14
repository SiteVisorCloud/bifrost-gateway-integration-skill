# Native provider SDK drop-in

Use this when existing code depends on a provider's own SDK (Anthropic-specific
fields, Google GenAI, etc.) and rewriting to the OpenAI/Responses shape isn't
worth it. Change **only** the base URL, and pass the `sk-bf-...` gateway key where
the SDK expects its key. Everything else about the SDK stays the same.

For new code, prefer the unified `https://your-gateway.example.com/v1` endpoint
with the Responses API (see SKILL.md) — one integration for every provider.

> Note: the native Anthropic and Google SDKs work in their own format
> (`messages` / `generate_content`). That is NOT the Responses API. If you
> specifically want the Responses-first behavior, use Approach 1 from SKILL.md
> instead of these drop-ins.

## OpenAI SDK → `https://your-gateway.example.com/openai`

On this drop-in you can use plain OpenAI model names (`gpt-4o-mini`); pass
`provider/model` to route to a different provider. A recent OpenAI SDK also
supports the Responses API here (`client.responses.create`) — prefer it over
`chat.completions`.

```python
import os
from openai import OpenAI

client = OpenAI(
    base_url="https://your-gateway.example.com/openai",
    api_key=os.environ["BIFROST_API_KEY"],   # sk-bf-...
)
resp = client.responses.create(          # prefer the Responses API
    model="gpt-4o-mini",
    input="Hello!",
)
print(resp.output_text)
```

## Anthropic SDK → `https://your-gateway.example.com/anthropic`

The Anthropic SDK appends `/v1/messages` itself, so set `base_url` to the bare
`/anthropic` prefix. The key still goes in `api_key` and is the `sk-bf-...` key
(NOT an `sk-ant-` key).

```python
import os
from anthropic import Anthropic

client = Anthropic(
    base_url="https://your-gateway.example.com/anthropic",
    api_key=os.environ["BIFROST_API_KEY"],   # sk-bf-...
)
msg = client.messages.create(
    model="claude-3-5-sonnet-20241022",
    max_tokens=1024,
    messages=[{"role": "user", "content": "Hello!"}],
)
print(msg.content[0].text)
```

TypeScript:

```ts
import Anthropic from "@anthropic-ai/sdk";

const client = new Anthropic({
  baseURL: "https://your-gateway.example.com/anthropic",
  apiKey: process.env.BIFROST_API_KEY, // sk-bf-...
});

const msg = await client.messages.create({
  model: "claude-3-5-sonnet-20241022",
  max_tokens: 1024,
  messages: [{ role: "user", content: "Hello!" }],
});
```

## Google GenAI SDK → `https://your-gateway.example.com/genai`

```python
import os
from google import genai

client = genai.Client(
    api_key=os.environ["BIFROST_API_KEY"],   # sk-bf-...
    http_options={"base_url": "https://your-gateway.example.com/genai"},
)
resp = client.models.generate_content(
    model="gemini-1.5-pro",
    contents="Hello!",
)
print(resp.text)
```

The exact way to override the endpoint varies slightly across GenAI SDK versions
(`http_options={"base_url": ...}` vs `client_options`); if one form is rejected,
check the installed version's client options. The goal is simply that requests hit
`your-gateway.example.com/genai` instead of Google directly.
