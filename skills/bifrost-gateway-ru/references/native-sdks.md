# Нативные SDK провайдеров (drop-in)

Используй это, когда существующий код завязан на «родной» SDK провайдера
(специфичные поля Anthropic, Google GenAI и т.п.) и переписывать его на формат
OpenAI/Responses не имеет смысла. Меняется **только** базовый URL, а ключ
`sk-bf-...` передаётся туда, где SDK обычно ждёт свой ключ. Всё остальное в SDK
остаётся как было.

Для нового кода предпочитай единый эндпоинт `https://your-gateway.example.com/v1` с
Responses API (см. SKILL.md) — одна интеграция под всех провайдеров.

> Важно: нативные SDK Anthropic и Google работают в своём формате (`messages` /
> `generate_content`). Это не Responses API. Если хочешь именно приоритетный
> Responses API — используй Способ 1 из SKILL.md, а не эти drop-in.

## OpenAI SDK → `https://your-gateway.example.com/openai`

На этом drop-in можно использовать обычные имена моделей OpenAI (`gpt-4o-mini`);
передай `провайдер/модель`, чтобы уйти к другому провайдеру. Свежий OpenAI SDK
здесь тоже умеет Responses API (`client.responses.create`) — предпочитай его
вместо `chat.completions`.

```python
import os
from openai import OpenAI

client = OpenAI(
    base_url="https://your-gateway.example.com/openai",
    api_key=os.environ["BIFROST_API_KEY"],   # sk-bf-...
)
resp = client.responses.create(          # приоритет — Responses API
    model="gpt-4o-mini",
    input="Привет!",
)
print(resp.output_text)
```

## Anthropic SDK → `https://your-gateway.example.com/anthropic`

Anthropic SDK сам дописывает `/v1/messages`, поэтому в `base_url` укажи голый
префикс `/anthropic`. Ключ по-прежнему кладётся в `api_key` и это `sk-bf-...`
(НЕ ключ `sk-ant-`).

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
    messages=[{"role": "user", "content": "Привет!"}],
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
  messages: [{ role: "user", content: "Привет!" }],
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
    contents="Привет!",
)
print(resp.text)
```

Способ переопределить эндпоинт немного отличается между версиями GenAI SDK
(`http_options={"base_url": ...}` против `client_options`); если один вариант не
принимается — посмотри опции клиента у установленной версии SDK. Цель одна: чтобы
запросы шли на `your-gateway.example.com/genai`, а не напрямую в Google.
