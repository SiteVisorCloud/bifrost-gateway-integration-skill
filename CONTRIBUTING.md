# Contributing

Thanks for helping improve this skill! New language/framework examples and doc
fixes are especially welcome.

## Ground rules

- **Never commit a real gateway host or key.** Use the placeholder
  `https://your-gateway.example.com/v1` and read secrets from `BIFROST_BASE_URL`
  / `BIFROST_API_KEY`. CI fails if an internal brand/host string appears.
- Keep examples **minimal, runnable, and env‑driven** — no hard-coded keys, no
  extra dependencies beyond what the example demonstrates.
- Prefer the **Responses API** (`/v1/responses`); show Chat Completions only as a
  fallback.
- Model strings are always `provider/model` (e.g. `openai/gpt-4o-mini`).

## Adding an example

1. Put it under `examples/<language-or-framework>/`.
2. Read `BIFROST_BASE_URL` and `BIFROST_API_KEY` from the environment; default the
   base URL to the placeholder.
3. Add a one-line usage comment (how to install deps + run).
4. Link it from both `README.md` and `README.ru.md`.

## Editing the skill

The skill has two variants that must stay in sync:

- `skills/bifrost-gateway/` (English)
- `skills/bifrost-gateway-ru/` (Russian)

Change both when you touch shared content. Keep `SKILL.md` under ~500 lines and
the `description` free of `<`/`>` and under 1024 characters.

## Testing locally

```bash
# Validate SKILL.md frontmatter and the anonymization guard the way CI does:
python - <<'PY'
import re, pathlib, sys
bad=[]
for p in pathlib.Path("skills").glob("*/SKILL.md"):
    fm=re.match(r'^---\n(.*?)\n---', p.read_text(), re.S)
    if not fm: bad.append(f"{p}: no frontmatter")
print("\n".join(bad) or "SKILL.md OK")
PY
```

## Sign-off

By contributing you agree your work is licensed under this repo's [MIT](LICENSE)
license. A simple `Signed-off-by:` line in your commit (DCO style) is appreciated
but not required.
