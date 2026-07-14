---
name: bifrost-gateway-ru
description: >-
  Направляет КАЖДЫЙ запрос к любой языковой модели / ИИ-провайдеру через шлюз
  Bifrost (https://your-gateway.example.com), чтобы все вызовы логировались и
  фиксировались в одном месте. Используй этот навык ВСЕГДА, когда пишешь,
  настраиваешь или чинишь любой код, который обращается к LLM или ИИ-провайдеру —
  OpenAI/GPT, Anthropic/Claude, Google Gemini, Mistral или любому другому — на
  ЛЮБОМ языке (Python, PHP, JavaScript/TypeScript, Go, curl) и в любом фреймворке
  (LangChain, LlamaIndex, Vercel AI SDK). Сюда входят новые интеграции,
  правка существующей настройки клиента, конфигурация base_url / api_key, вызовы
  chat completions, Responses API, embeddings и стриминг. Срабатывай, даже если
  пользователь просто говорит «вызови gpt-4o», «добавь Claude в этот скрипт»,
  «sk-bf-...», «сгенерируй текст через нейросеть» или упоминает SDK провайдера,
  но не сам шлюз. НИКОГДА не обращайся к API провайдеров напрямую — весь смысл
  шлюза в том, чтобы ни один запрос не шёл мимо него.
---

# Шлюз Bifrost

У пользователя развёрнут LLM-шлюз **Bifrost** по адресу `https://your-gateway.example.com`.
Любой вызов любого ИИ-провайдера должен идти через него — так все запросы
логируются и фиксируются в одном месте. Прямое обращение к `api.openai.com`,
`api.anthropic.com`, `generativelanguage.googleapis.com` и т.п. рушит весь смысл:
такие запросы будут невидимы для логирования шлюза.

Поэтому задача навыка простая: **как только ты трогаешь код, который общается с
LLM, направляй его на шлюз, а не на провайдера напрямую.** На практике это почти
всегда изменение одной строки (`base_url`) плюс использование ключа шлюза.

## Три вещи, которые нужны всегда

1. **Базовый URL:** `https://your-gateway.example.com/v1`
2. **Строка модели:** `провайдер/модель` — например `openai/gpt-4o-mini`,
   `anthropic/claude-3-5-sonnet-20241022`
3. **Авторизация:** `Authorization: Bearer <ключ>`, где ключ **всегда начинается
   с `sk-bf-`**

Так как шлюз говорит на протоколе OpenAI, любой инструмент, SDK или язык, умеющий
работать с OpenAI, умеет работать и со шлюзом — меняется только базовый URL и
строка модели. Это общее правило для **любого** языка, даже не показанного ниже.

## Ключ доступа

- Пользователь передаёт ключ **в чате**. Он всегда начинается с `sk-bf-` (по
  этому префиксу его и опознавай). Если в диалоге встретилась строка вида
  `sk-bf-abc123...` — это и есть ключ шлюза.
- **Никогда не зашивай ключ в исходники.** В рабочем коде читай его из
  переменной окружения — по умолчанию `BIFROST_API_KEY` (запасной вариант —
  `OPENAI_API_KEY`, если SDK/инструмент читает только это имя). Литерал ключа
  допустим только в разовых однострочниках или когда пользователь прямо просит.
- Если ключа ещё нет, а нужен рабочий код — используй переменную окружения и
  напиши, что её надо задать: `export BIFROST_API_KEY=sk-bf-...`. Не выдумывай
  фейковый `sk-bf-`-ключ, похожий на настоящий.

## Строка модели

На едином эндпоинте модель — **всегда** `провайдер/модель`. Префикс говорит
шлюзу, к какому провайдеру идти; хвост — это имя модели у самого провайдера.

**Провайдеры, включённые на `your-gateway.example.com`, и их префиксы.** Ключевой
момент: в строке модели используется каноничный ключ Bifrost, а не разговорное
имя. Особенно легко ошибиться с Claude и Grok:

| Провайдер (как обычно называют) | префикс | пример строки модели |
|---|---|---|
| OpenAI | `openai` | `openai/gpt-4o-mini`, `openai/gpt-4o` |
| Claude (Anthropic) | `anthropic` | `anthropic/claude-3-5-sonnet-20241022` |
| Gemini (Google) | `gemini` | `gemini/gemini-1.5-pro`, `gemini/gemini-2.0-flash` |
| DeepSeek | `deepseek` | `deepseek/deepseek-chat`, `deepseek/deepseek-reasoner` |
| Grok (xAI) | `xai` | `xai/grok-2-latest` |
| Perplexity | `perplexity` | `perplexity/sonar-pro` |

То есть Claude — это `anthropic/...` (НЕ `claude/...`), а Grok — `xai/...`
(НЕ `grok/...`). Имена самих моделей (хвост после слэша) и их актуальный список
бери из даташита — см. ниже.

Точные имена моделей, поддерживаемые параметры, контекстное окно и **стоимость**
для каждой модели живут в даташите Bifrost (источник истины, цены меняются):
- Параметры моделей: <https://getbifrost.ai/datasheet/model-parameters>
- Модели, лимиты и цены: <https://getbifrost.ai/datasheet>

Сводка по параметрам запроса и по тому, что различается между провайдерами, —
в [references/models-and-parameters.md](references/models-and-parameters.md).
Если не уверен в точном имени модели или префиксе — сохрани форму
`провайдер/модель`, свернись на пример из таблицы и уточни у пользователя, а не
угадывай.

## Какой эндпоинт выбрать: Responses API → Chat Completions

**По умолчанию используй Responses API** (`/v1/responses`) — это более новый и
актуальный формат OpenAI. Шлюз принимает `/v1/responses` с любым `провайдер/модель`
и сам транслирует запрос под нужного провайдера, так что приоритет Responses
работает для всех провайдеров, а не только для OpenAI.

**Падай на Chat Completions** (`/v1/chat/completions`), только если клиент, SDK
или фреймворк не умеет Responses API (или ты не уверен, что умеет). Оба эндпоинта
используют один и тот же ключ `sk-bf-...` и один и тот же формат `провайдер/модель`.

Ориентир, кто умеет Responses API:

| Поддерживают Responses API (приоритет) | Только Chat Completions (fallback) |
|---|---|
| Свежий OpenAI SDK: `client.responses.create` (Python/Node) | Старые версии OpenAI SDK |
| Vercel AI SDK: `openai.responses("model")` | LlamaIndex `OpenAILike`, многие «OpenAI-compatible» клиенты |
| Прямой HTTP / curl на `/v1/responses` | `openai-php`, если в его версии нет `->responses()` |
| `langchain-openai` с `use_responses_api=True` | Любой клиент, где нет явной поддержки Responses |

Нативные SDK Anthropic и Google — это отдельный формат (`messages` /
`generate_content`), это не Responses и не Chat Completions; см.
[references/native-sdks.md](references/native-sdks.md).

## Способ 1 (по умолчанию): единый эндпоинт, приоритет Responses API

### Responses API — приоритетный вариант

**curl:**

```bash
curl https://your-gateway.example.com/v1/responses \
  -H "Authorization: Bearer $BIFROST_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "model": "openai/gpt-4o-mini",
    "input": "Привет!"
  }'
```

Ответ лежит в `output` / `output_text`, а не в `choices[].message`.

**Python (OpenAI SDK):**

```python
import os
from openai import OpenAI

client = OpenAI(
    base_url="https://your-gateway.example.com/v1",
    api_key=os.environ["BIFROST_API_KEY"],   # sk-bf-...
)

resp = client.responses.create(
    model="openai/gpt-4o-mini",                 # провайдер/модель
    input="Привет!",
)
print(resp.output_text)

# Многоходовый диалог — цепочка через previous_response_id
follow_up = client.responses.create(
    model="openai/gpt-4o-mini",
    input="А теперь смешнее.",
    previous_response_id=resp.id,
)
print(follow_up.output_text)
```

**PHP (сырой cURL — работает на любой версии PHP):**

```php
<?php
$ch = curl_init('https://your-gateway.example.com/v1/responses');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . getenv('BIFROST_API_KEY'),  // sk-bf-...
        'Content-Type: application/json',
    ],
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        'model' => 'openai/gpt-4o-mini',                          // провайдер/модель
        'input' => 'Привет!',
    ], JSON_UNESCAPED_UNICODE),
]);
$data = json_decode(curl_exec($ch), true);
curl_close($ch);
echo $data['output_text'] ?? json_encode($data['output'], JSON_UNESCAPED_UNICODE);
```

### Chat Completions — запасной вариант (когда Responses не поддерживается)

**curl:**

```bash
curl https://your-gateway.example.com/v1/chat/completions \
  -H "Authorization: Bearer $BIFROST_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "model": "anthropic/claude-3-5-sonnet-20241022",
    "messages": [{"role": "user", "content": "Привет!"}]
  }'
```

**Python (OpenAI SDK):**

```python
import os
from openai import OpenAI

client = OpenAI(
    base_url="https://your-gateway.example.com/v1",
    api_key=os.environ["BIFROST_API_KEY"],   # sk-bf-...
)
resp = client.chat.completions.create(
    model="anthropic/claude-3-5-sonnet-20241022",
    messages=[{"role": "user", "content": "Привет!"}],
)
print(resp.choices[0].message.content)
```

**PHP (сырой cURL):**

```php
<?php
$ch = curl_init('https://your-gateway.example.com/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . getenv('BIFROST_API_KEY'),  // sk-bf-...
        'Content-Type: application/json',
    ],
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        'model' => 'openai/gpt-4o-mini',                          // провайдер/модель
        'messages' => [['role' => 'user', 'content' => 'Привет!']],
    ], JSON_UNESCAPED_UNICODE),
]);
$response = json_decode(curl_exec($ch), true);
curl_close($ch);
echo $response['choices'][0]['message']['content'];
```

Больше примеров:
- **PHP** (пакет `openai-php/client`) и **Laravel** (Laravel AI SDK, `laravel/ai`) —
  в [references/php-laravel.md](references/php-laravel.md).
- **JavaScript / TypeScript** и фреймворки (LangChain, LlamaIndex, Vercel AI SDK) —
  в [references/javascript-and-frameworks.md](references/javascript-and-frameworks.md).

## Способ 2: нативные SDK провайдеров (drop-in) — когда код должен остаться на «родном» SDK

Иногда переписывать на формат OpenAI/Responses не имеет смысла — например, код уже
использует Anthropic SDK с его специфичными полями или Google GenAI SDK. Bifrost
даёт «родные» эндпоинты провайдеров: SDK остаётся тот же, меняется **только**
базовый URL и ключ (всё тот же `sk-bf-...`):

| SDK | какой base URL поставить | формат модели |
|---|---|---|
| OpenAI SDK | `https://your-gateway.example.com/openai` | `gpt-4o-mini` (или `провайдер/модель` для кросс-роутинга) |
| Anthropic SDK | `https://your-gateway.example.com/anthropic` | `claude-3-5-sonnet-20241022` |
| Google GenAI SDK | `https://your-gateway.example.com/genai` | `gemini-1.5-pro` |

Подробные примеры — в [references/native-sdks.md](references/native-sdks.md).
Для нового кода предпочитай Способ 1 (единый эндпоинт, Responses API); Способ 2 —
только чтобы не переписывать уже работающий провайдер-специфичный код.

## Перед завершением — быстрая самопроверка

Написал или поправил код с LLM — убедись, что верны все четыре пункта:

- [ ] Базовый URL / хост указывает на `your-gateway.example.com` (а не на
      `api.openai.com`, `api.anthropic.com`,
      `generativelanguage.googleapis.com` или голый хост провайдера).
- [ ] Формат запроса — Responses API (`/v1/responses`), а Chat Completions
      (`/v1/chat/completions`) только там, где клиент не умеет Responses.
- [ ] Строка модели — `провайдер/модель` на эндпоинте `/v1` (или родное имя
      модели на drop-in `/openai` `/anthropic` `/genai`).
- [ ] Ключ — `sk-bf-...` ключ шлюза, прочитанный из переменной окружения, а не
      зашитый в код.
- [ ] Ни один путь исполнения не идёт к провайдеру напрямую — включая настройки
      SDK по умолчанию, которые ты не переопределил. Если base URL клиента не
      переопределён, он молча пойдёт мимо шлюза, поэтому задавай его явно.

Если хоть какой-то код всё ещё достаёт провайдера напрямую — исправь: обойдённый
запрос невидим для шлюза, а именно ради видимости всё это и делается.
