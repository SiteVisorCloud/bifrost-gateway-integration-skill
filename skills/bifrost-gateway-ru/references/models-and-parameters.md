# Модели, параметры и стоимость

Источник истины по конкретным моделям, их параметрам, лимитам и **ценам** —
даташит Bifrost. Он живой и меняется, поэтому не хардкодь цифры цен в код и не
заучивай их — при необходимости сверяйся:

- Параметры моделей: <https://getbifrost.ai/datasheet/model-parameters>
- Модели, контекст, лимиты, стоимость: <https://getbifrost.ai/datasheet>

Ниже — стабильная часть: какие параметры вообще бывают, как они называются, и что
различается между провайдерами шлюза (`openai`, `anthropic`, `gemini`,
`deepseek`, `xai`, `perplexity`).

## Как передавать параметры

Параметры кладутся в тело запроса рядом с `model` — одинаково через curl, SDK и
фреймворки. Шлюз пробрасывает их провайдеру. Если параметр не поддерживается
конкретным провайдером, он либо игнорируется, либо вернётся ошибка — смотри
даташит по конкретной модели.

## Общие параметры

### Chat Completions (`/v1/chat/completions`)

| Параметр | Тип | Смысл |
|---|---|---|
| `model` | string | `провайдер/модель`, обязателен |
| `messages` | array | история сообщений (`role` + `content`) |
| `temperature` | number 0–2 | случайность; 0 — детерминированнее, выше — разнообразнее |
| `max_tokens` | int | максимум токенов в ответе |
| `top_p` | number 0–1 | nucleus-сэмплинг (альтернатива temperature) |
| `frequency_penalty` | number −2…2 | штраф за повторы по частоте |
| `presence_penalty` | number −2…2 | штраф за повторное упоминание темы |
| `stop` | string/array | стоп-последовательности |
| `stream` | bool | потоковый ответ (SSE) |
| `seed` | int | попытка воспроизводимости |
| `n` | int | сколько вариантов ответа сгенерировать |
| `response_format` | object | напр. `{"type":"json_object"}` для JSON |
| `tools` / `tool_choice` | array/… | вызов функций (function calling) |

### Responses API (`/v1/responses`) — приоритетный формат

| Параметр | Тип | Смысл |
|---|---|---|
| `model` | string | `провайдер/модель`, обязателен |
| `input` | string/array | ввод (вместо `messages`) |
| `instructions` | string | системная инструкция |
| `max_output_tokens` | int | максимум токенов в ответе |
| `temperature`, `top_p` | number | как выше |
| `stream` | bool | потоковый ответ |
| `previous_response_id` | string | цепочка многоходового диалога |
| `reasoning` | object | напр. `{"effort":"medium"}` для reasoning-моделей |
| `tools` / `tool_choice` | array/… | вызов функций |
| `text` | object | формат вывода, напр. структурированный JSON |

## Reasoning-модели (o-серия OpenAI, `deepseek-reasoner`, reasoning-режимы Grok)

- Управляются `reasoning_effort` (Chat Completions) или `reasoning.effort`
  (Responses): `low` / `medium` / `high`.
- Часто **игнорируют `temperature`/`top_p`** — не полагайся на них для таких моделей.
- В OpenAI reasoning-моделях лимит ответа задаётся `max_completion_tokens`
  (Chat Completions) или `max_output_tokens` (Responses), а не `max_tokens`.
- У `deepseek-reasoner` в ответе есть отдельное поле рассуждений
  (`reasoning_content`) помимо основного `content`.

## Особенности провайдеров шлюза

- **`openai`** — полный набор параметров. Reasoning-модели (o3/o4-mini, gpt-5.x)
  используют `reasoning_effort` и `max_completion_tokens`/`max_output_tokens`.
- **`anthropic` (Claude)** — на нативном эндпоинте `/anthropic` поле `max_tokens`
  **обязательно**; на едином `/v1` шлюз подставит дефолт, но лучше задавать явно.
  Поддерживает `temperature` (0–1), `top_p`, `top_k`, `stop_sequences`.
- **`gemini` (Google)** — `temperature`, `top_p`, `top_k`, `max_output_tokens`,
  настройки безопасности. На нативном `/genai` формат тела иной (`contents`).
- **`deepseek`** — `deepseek-chat` (обычная) и `deepseek-reasoner` (reasoning,
  см. выше).
- **`xai` (Grok)** — OpenAI-совместимые параметры; часть моделей поддерживает
  reasoning.
- **`perplexity`** — модели `sonar`/`sonar-pro` дополнены веб-поиском. Есть
  доп. параметры: `search_domain_filter`, `search_recency_filter`,
  `return_citations`; в ответе приходят ссылки-источники (`citations`).

## Стоимость

В даташите у каждой модели указаны поля вида `input_cost_per_token`,
`output_cost_per_token`, `max_input_tokens`, `max_output_tokens`. Цена считается
за токен (умножь на 1 000 000, чтобы получить цену за 1M токенов).

Пример формата (иллюстрация, НЕ фиксируй как факт — бери актуальное из даташита):
`input_cost_per_token: 0.000002` означает $2 за 1M входных токенов.

Если пользователю нужна конкретная цена или лимит модели — открой
<https://getbifrost.ai/datasheet> и найди строку этой модели, а не оценивай по
памяти.
