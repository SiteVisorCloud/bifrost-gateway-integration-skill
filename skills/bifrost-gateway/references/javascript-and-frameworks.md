# JavaScript / TypeScript and frameworks

Same idea as SKILL.md: point the client at `https://your-gateway.example.com/v1`,
use `provider/model`, and pass the `sk-bf-...` key. Prefer the Responses API; fall
back to Chat Completions only where Responses isn't supported. Only the wiring
differs.

## JavaScript / TypeScript (OpenAI SDK)

**Prefer the Responses API:**

```ts
import OpenAI from "openai";

const client = new OpenAI({
  baseURL: process.env.BIFROST_BASE_URL ?? "https://your-gateway.example.com/v1",
  apiKey: process.env.BIFROST_API_KEY, // sk-bf-...
});

const resp = await client.responses.create({
  model: "openai/gpt-4o-mini",           // provider/model
  input: "Hello!",
});
console.log(resp.output_text);
```

**Fallback — Chat Completions:**

```ts
const resp = await client.chat.completions.create({
  model: "anthropic/claude-3-5-sonnet-20241022",
  messages: [{ role: "user", content: "Hello!" }],
});
console.log(resp.choices[0].message.content);
```

## JavaScript (fetch, no SDK)

Responses API:

```js
const resp = await fetch(`${process.env.BIFROST_BASE_URL}/responses`, {
  method: "POST",
  headers: {
    Authorization: `Bearer ${process.env.BIFROST_API_KEY}`, // sk-bf-...
    "Content-Type": "application/json",
  },
  body: JSON.stringify({
    model: "openai/gpt-4o-mini",
    input: "Hello!",
  }),
});
const data = await resp.json();
console.log(data.output_text);
```

For Chat Completions, change the path to `/chat/completions` and the body to
`{ model, messages: [...] }`; the answer is in `data.choices[0].message.content`.

## Vercel AI SDK

The `@ai-sdk/openai` provider factory supports the Responses API via
`.responses(...)` — that's the preferred path; `openai(...)` without `.responses`
goes through Chat Completions.

```ts
import { createOpenAI } from "@ai-sdk/openai";
import { generateText } from "ai";

const gateway = createOpenAI({
  baseURL: process.env.BIFROST_BASE_URL ?? "https://your-gateway.example.com/v1",
  apiKey: process.env.BIFROST_API_KEY, // sk-bf-...
});

const { text } = await generateText({
  model: gateway.responses("openai/gpt-4o-mini"), // provider/model, Responses API
  prompt: "Hello!",
});
```

## LangChain (Python)

`ChatOpenAI` speaks the OpenAI protocol, so it works through the gateway for any
provider — set `base_url` and use `provider/model`. To enable the preferred
Responses API add `use_responses_api=True` (in recent `langchain-openai`); without
it, it uses Chat Completions — an acceptable fallback.

```python
import os
from langchain_openai import ChatOpenAI

llm = ChatOpenAI(
    base_url=os.environ.get("BIFROST_BASE_URL", "https://your-gateway.example.com/v1"),
    api_key=os.environ["BIFROST_API_KEY"],   # sk-bf-...
    model="anthropic/claude-3-5-sonnet-20241022",
    use_responses_api=True,                  # prefer the Responses API
)
print(llm.invoke("Hello!").content)
```

Prefer `ChatOpenAI` pointed at the gateway over provider-specific classes like
`ChatAnthropic`: `ChatAnthropic` would send traffic straight to Anthropic and
bypass logging. Running Claude (or any model) through `ChatOpenAI` + the gateway
is what keeps every call logged.

## LlamaIndex (Python)

`OpenAILike` goes through Chat Completions (fallback) — a reliable option for any
`provider/model` through the gateway:

```python
import os
from llama_index.llms.openai_like import OpenAILike

llm = OpenAILike(
    api_base=os.environ.get("BIFROST_BASE_URL", "https://your-gateway.example.com/v1"),
    api_key=os.environ["BIFROST_API_KEY"],   # sk-bf-...
    model="openai/gpt-4o-mini",
    is_chat_model=True,
)
print(llm.complete("Hello!"))
```

If you specifically need the Responses API, recent `llama-index-llms-openai` has
an `OpenAIResponses` class — point its `api_base` at the same
`https://your-gateway.example.com/v1`.
