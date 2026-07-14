# Setting up a Bifrost gateway (quick start)

The rest of this skill assumes an OpenAI-compatible gateway already exists. If you
need to stand one up, here's the fastest path with **Bifrost** — the open-source
LLM gateway by Maxim (<https://github.com/maximhq/bifrost>). This is a one-time
setup.

## 1. Run the gateway

Docker (recommended — the volume persists config across restarts):

```bash
docker run -p 8080:8080 -v "$(pwd)/data:/app/data" maximhq/bifrost
```

…or without Docker:

```bash
npx -y @maximhq/bifrost
```

The gateway and its web UI are now at <http://localhost:8080>. For production, put
it behind your own domain + TLS — that host (plus `/v1`) becomes your
`BIFROST_BASE_URL`.

## 2. Add providers

Open <http://localhost:8080> and add providers (OpenAI, Anthropic, Gemini,
DeepSeek, xAI, Perplexity, …) with their API keys — no code needed.

Or configure by file (GitOps) with a `config.json` in the working directory:

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

`value` can reference an environment variable via `env.VAR_NAME` (recommended) or
be a literal `sk-...` key. These provider keys are your accounts with each
provider — now held on the gateway instead of scattered across apps. That's the
whole point.

## 3. Create a gateway (virtual) key

In the UI under **Governance → Virtual Keys**, create a virtual key. That key —
the value clients send as `Authorization: Bearer …` — is what `BIFROST_API_KEY`
holds (Bifrost virtual keys are commonly prefixed `sk-bf-`). Virtual keys let you
set budgets, rate limits, and per-app/team access control.
Docs: <https://docs.getbifrost.ai/features/governance/virtual-keys>

## 4. Verify

```bash
curl http://localhost:8080/v1/chat/completions \
  -H "Authorization: Bearer $BIFROST_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"model":"openai/gpt-4o-mini","messages":[{"role":"user","content":"Hello, Bifrost!"}]}'
```

Once it responds, set `BIFROST_BASE_URL` to `http://localhost:8080/v1` (or your
deployed host + `/v1`) and `BIFROST_API_KEY` to your virtual key — and the rest of
this skill applies unchanged.

Setup docs: <https://docs.getbifrost.ai/quickstart/gateway/setting-up>
