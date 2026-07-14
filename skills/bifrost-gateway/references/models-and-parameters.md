# Models, parameters, and pricing

The source of truth for specific models, their parameters, limits, and **pricing**
is the Bifrost datasheet. It's live and changes, so don't hard-code prices or
memorize them — check it when needed:

- Model parameters: <https://getbifrost.ai/datasheet/model-parameters>
- Models, context, limits, pricing: <https://getbifrost.ai/datasheet>

Below is the stable part: which parameters exist, what they're called, and what
differs between the gateway's providers (`openai`, `anthropic`, `gemini`,
`deepseek`, `xai`, `perplexity`).

## How to pass parameters

Parameters go into the request body alongside `model` — the same across curl,
SDKs, and frameworks. The gateway forwards them to the provider. If a provider
doesn't support a parameter it's either ignored or returns an error — check the
datasheet for that model.

## Common parameters

### Chat Completions (`/v1/chat/completions`)

| Parameter | Type | Meaning |
|---|---|---|
| `model` | string | `provider/model`, required |
| `messages` | array | conversation history (`role` + `content`) |
| `temperature` | number 0–2 | randomness; 0 = more deterministic, higher = more varied |
| `max_tokens` | int | max tokens in the response |
| `top_p` | number 0–1 | nucleus sampling (alternative to temperature) |
| `frequency_penalty` | number −2…2 | penalize repetition by frequency |
| `presence_penalty` | number −2…2 | penalize re-raising a topic |
| `stop` | string/array | stop sequences |
| `stream` | bool | streamed response (SSE) |
| `seed` | int | best-effort reproducibility |
| `n` | int | how many completions to generate |
| `response_format` | object | e.g. `{"type":"json_object"}` for JSON |
| `tools` / `tool_choice` | array/… | function calling |

### Responses API (`/v1/responses`) — the preferred format

| Parameter | Type | Meaning |
|---|---|---|
| `model` | string | `provider/model`, required |
| `input` | string/array | the input (instead of `messages`) |
| `instructions` | string | system instruction |
| `max_output_tokens` | int | max tokens in the response |
| `temperature`, `top_p` | number | as above |
| `stream` | bool | streamed response |
| `previous_response_id` | string | chain a multi-turn conversation |
| `reasoning` | object | e.g. `{"effort":"medium"}` for reasoning models |
| `tools` / `tool_choice` | array/… | function calling |
| `text` | object | output format, e.g. structured JSON |

## Reasoning models (OpenAI o-series, `deepseek-reasoner`, Grok reasoning modes)

- Controlled by `reasoning_effort` (Chat Completions) or `reasoning.effort`
  (Responses): `low` / `medium` / `high`.
- They often **ignore `temperature` / `top_p`** — don't rely on those for such
  models.
- In OpenAI reasoning models the response cap is `max_completion_tokens` (Chat
  Completions) or `max_output_tokens` (Responses), not `max_tokens`.
- `deepseek-reasoner` returns a separate reasoning field (`reasoning_content`)
  alongside the main `content`.

## Provider-specific notes

- **`openai`** — full parameter set. Reasoning models (o3/o4-mini, gpt-5.x) use
  `reasoning_effort` and `max_completion_tokens` / `max_output_tokens`.
- **`anthropic` (Claude)** — on the native `/anthropic` endpoint `max_tokens` is
  **required**; on the unified `/v1` endpoint the gateway supplies a default, but
  it's best to set it explicitly. Supports `temperature` (0–1), `top_p`, `top_k`,
  `stop_sequences`.
- **`gemini` (Google)** — `temperature`, `top_p`, `top_k`, `max_output_tokens`,
  safety settings. On the native `/genai` endpoint the body shape differs
  (`contents`).
- **`deepseek`** — `deepseek-chat` (standard) and `deepseek-reasoner` (reasoning,
  see above).
- **`xai` (Grok)** — OpenAI-compatible parameters; some models support reasoning.
- **`perplexity`** — the `sonar` / `sonar-pro` models are web-search-augmented.
  Extra parameters: `search_domain_filter`, `search_recency_filter`,
  `return_citations`; the response includes source `citations`.

## Pricing

In the datasheet each model has fields like `input_cost_per_token`,
`output_cost_per_token`, `max_input_tokens`, `max_output_tokens`. Cost is per
token (multiply by 1,000,000 for the per-1M-tokens price).

Format example (illustrative — do NOT treat as fact; pull the current value from
the datasheet): `input_cost_per_token: 0.000002` means $2 per 1M input tokens.

If the user needs a specific price or limit, open
<https://getbifrost.ai/datasheet> and find that model's row rather than
estimating from memory.
