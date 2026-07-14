# Установка шлюза Bifrost (быстрый старт)

Остальная часть навыка предполагает, что OpenAI-совместимый шлюз уже есть. Если
его нужно поднять — вот самый быстрый путь на **Bifrost**, опенсорсном
LLM-gateway от Maxim (<https://github.com/maximhq/bifrost>). Это разовая настройка.

## 1. Запустить шлюз

Docker (рекомендуется — том сохраняет конфигурацию между перезапусками):

```bash
docker run -p 8080:8080 -v "$(pwd)/data:/app/data" maximhq/bifrost
```

…или без Docker:

```bash
npx -y @maximhq/bifrost
```

Шлюз и его веб-интерфейс теперь на <http://localhost:8080>. Для продакшена
поставь его за своим доменом + TLS — этот хост (плюс `/v1`) и станет твоим
`BIFROST_BASE_URL`.

## 2. Добавить провайдеров

Открой <http://localhost:8080> и добавь провайдеров (OpenAI, Anthropic, Gemini,
DeepSeek, xAI, Perplexity, …) с их API-ключами — без кода.

Или настрой файлом (GitOps) — `config.json` в рабочей директории:

```json
{
  "providers": {
    "openai": {
      "keys": [
        { "name": "openai-key-1", "value": "env.OPENAI_API_KEY", "models": ["gpt-4o-mini", "gpt-4o"], "weight": 1.0 }
      ]
    },
    "anthropic": {
      "keys": [
        { "name": "anthropic-key-1", "value": "env.ANTHROPIC_API_KEY", "weight": 1.0 }
      ]
    }
  },
  "config_store": { "enabled": true, "type": "sqlite" }
}
```

`value` может ссылаться на переменную окружения через `env.ИМЯ_ПЕРЕМЕННОЙ`
(рекомендуется) или быть литеральным ключом `sk-...`. Эти ключи провайдеров — твои
аккаунты у каждого провайдера, теперь они хранятся на шлюзе, а не разбросаны по
приложениям. В этом весь смысл.

## 3. Создать ключ шлюза (virtual key)

В интерфейсе в разделе **Governance → Virtual Keys** создай virtual key. Этот
ключ — то, что клиенты отправляют в `Authorization: Bearer …` — и есть
`BIFROST_API_KEY` (virtual keys Bifrost обычно с префиксом `sk-bf-`). Virtual keys
позволяют задавать бюджеты, лимиты и доступ по приложениям/командам.
Документация: <https://docs.getbifrost.ai/features/governance/virtual-keys>

## 4. Проверить

```bash
curl http://localhost:8080/v1/chat/completions \
  -H "Authorization: Bearer $BIFROST_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"model":"openai/gpt-4o-mini","messages":[{"role":"user","content":"Привет, Bifrost!"}]}'
```

Как только ответит — задай `BIFROST_BASE_URL` = `http://localhost:8080/v1` (или
твой задеплоенный хост + `/v1`), а `BIFROST_API_KEY` = твой virtual key, и весь
остальной навык работает без изменений.

Документация по установке: <https://docs.getbifrost.ai/quickstart/gateway/setting-up>
