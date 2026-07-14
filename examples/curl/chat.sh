#!/usr/bin/env bash
# Chat Completions (fallback) through the Bifrost gateway.
# The model can be any provider — the gateway routes by the provider/ prefix.
#
#   export BIFROST_BASE_URL=https://your-gateway.example.com/v1
#   export BIFROST_API_KEY=sk-bf-...
#   ./chat.sh
set -euo pipefail

: "${BIFROST_BASE_URL:=https://your-gateway.example.com/v1}"
: "${BIFROST_API_KEY:?Set BIFROST_API_KEY (starts with sk-bf-)}"

curl -sS "$BIFROST_BASE_URL/chat/completions" \
  -H "Authorization: Bearer $BIFROST_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "model": "anthropic/claude-3-5-sonnet-20241022",
    "messages": [{"role": "user", "content": "Say hello in one short sentence."}]
  }'
