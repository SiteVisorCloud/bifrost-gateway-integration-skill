#!/usr/bin/env bash
# Responses API (preferred) through the Bifrost gateway.
#
#   export BIFROST_BASE_URL=https://your-gateway.example.com/v1
#   export BIFROST_API_KEY=sk-bf-...
#   ./responses.sh
set -euo pipefail

: "${BIFROST_BASE_URL:=https://your-gateway.example.com/v1}"
: "${BIFROST_API_KEY:?Set BIFROST_API_KEY (starts with sk-bf-)}"

curl -sS "$BIFROST_BASE_URL/responses" \
  -H "Authorization: Bearer $BIFROST_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "model": "openai/gpt-4o-mini",
    "input": "Say hello in one short sentence."
  }'
