# Changelog

All notable changes to this project are documented here. The format is based on
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres
to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0]

### Added

- **Bifrost gateway quick start** — a one-time setup guide (Docker / npx, web UI,
  providers, virtual keys, verify) in `references/install-bifrost.md` for both
  skill variants, with a short block in each `SKILL.md` and a pointer from the
  READMEs.

## [1.0.0]

### Added

- Claude Agent Skill in two languages: `skills/bifrost-gateway/` (English) and
  `skills/bifrost-gateway-ru/` (Russian).
- Responses‑API‑first guidance with a Chat Completions fallback.
- Provider/model naming table for OpenAI, Anthropic (Claude), Gemini, DeepSeek,
  xAI (Grok), and Perplexity, including the `anthropic`/`xai` prefix gotchas.
- Reference docs: models &amp; parameters, native‑SDK drop‑ins, JS/frameworks,
  and PHP/Laravel (Laravel AI SDK).
- Runnable examples for Python, PHP, Laravel, JavaScript/TypeScript, and curl.
- Bilingual README (English + Russian), MIT license, and CI that validates
  `SKILL.md` frontmatter and guards against internal host/brand leakage.
