# JavaScript / TypeScript и фреймворки

Идея та же, что в SKILL.md: направляй клиент на `https://your-gateway.example.com/v1`,
используй `провайдер/модель` и ключ `sk-bf-...`. По умолчанию — Responses API,
на Chat Completions падай только там, где Responses не поддерживается. Отличается
лишь обвязка.

## JavaScript / TypeScript (OpenAI SDK)

**Приоритет — Responses API:**

```ts
import OpenAI from "openai";

const client = new OpenAI({
  baseURL: "https://your-gateway.example.com/v1",
  apiKey: process.env.BIFROST_API_KEY, // sk-bf-...
});

const resp = await client.responses.create({
  model: "openai/gpt-4o-mini",           // провайдер/модель
  input: "Привет!",
});
console.log(resp.output_text);
```

**Fallback — Chat Completions:**

```ts
const resp = await client.chat.completions.create({
  model: "anthropic/claude-3-5-sonnet-20241022",
  messages: [{ role: "user", content: "Привет!" }],
});
console.log(resp.choices[0].message.content);
```

## JavaScript (fetch, без SDK)

Responses API:

```js
const resp = await fetch("https://your-gateway.example.com/v1/responses", {
  method: "POST",
  headers: {
    Authorization: `Bearer ${process.env.BIFROST_API_KEY}`, // sk-bf-...
    "Content-Type": "application/json",
  },
  body: JSON.stringify({
    model: "openai/gpt-4o-mini",
    input: "Привет!",
  }),
});
const data = await resp.json();
console.log(data.output_text);
```

Для Chat Completions поменяй путь на `/v1/chat/completions`, а тело — на
`{ model, messages: [...] }`; ответ будет в `data.choices[0].message.content`.

## Vercel AI SDK

Провайдер-фабрика `@ai-sdk/openai` умеет Responses API через `.responses(...)` —
это приоритет; `openai(...)` без `.responses` идёт по Chat Completions.

```ts
import { createOpenAI } from "@ai-sdk/openai";
import { generateText } from "ai";

const gateway = createOpenAI({
  baseURL: "https://your-gateway.example.com/v1",
  apiKey: process.env.BIFROST_API_KEY, // sk-bf-...
});

const { text } = await generateText({
  model: gateway.responses("openai/gpt-4o-mini"), // провайдер/модель, Responses API
  prompt: "Привет!",
});
```

## LangChain (Python)

`ChatOpenAI` говорит на протоколе OpenAI, поэтому работает через шлюз для любого
провайдера — задай `base_url` и `провайдер/модель`. Чтобы включить приоритетный
Responses API, добавь `use_responses_api=True` (в свежих версиях `langchain-openai`);
без него пойдёт Chat Completions — это допустимый fallback.

```python
import os
from langchain_openai import ChatOpenAI

llm = ChatOpenAI(
    base_url="https://your-gateway.example.com/v1",
    api_key=os.environ["BIFROST_API_KEY"],   # sk-bf-...
    model="anthropic/claude-3-5-sonnet-20241022",
    use_responses_api=True,                    # приоритет Responses API
)
print(llm.invoke("Привет!").content)
```

Предпочитай `ChatOpenAI`, направленный на шлюз, а не провайдер-специфичные классы
вроде `ChatAnthropic`: `ChatAnthropic` отправит трафик прямо в Anthropic и обойдёт
логирование. Прогон Claude (и любой модели) через `ChatOpenAI` + шлюз — это и есть
то, что держит все вызовы залогированными.

## LlamaIndex (Python)

`OpenAILike` идёт по Chat Completions (fallback) — это надёжный вариант для
любого `провайдер/модель` через шлюз:

```python
import os
from llama_index.llms.openai_like import OpenAILike

llm = OpenAILike(
    api_base="https://your-gateway.example.com/v1",
    api_key=os.environ["BIFROST_API_KEY"],   # sk-bf-...
    model="openai/gpt-4o-mini",
    is_chat_model=True,
)
print(llm.complete("Привет!"))
```

Если нужен именно Responses API, в свежем `llama-index-llms-openai` есть класс
`OpenAIResponses` — направь его `api_base` на тот же `https://your-gateway.example.com/v1`.
