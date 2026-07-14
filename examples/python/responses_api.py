"""Responses API (preferred) through the Bifrost gateway, via the OpenAI SDK.

    export BIFROST_BASE_URL=https://your-gateway.example.com/v1
    export BIFROST_API_KEY=sk-bf-...
    pip install openai
    python responses_api.py
"""
import os

from openai import OpenAI

client = OpenAI(
    base_url=os.environ.get("BIFROST_BASE_URL", "https://your-gateway.example.com/v1"),
    api_key=os.environ["BIFROST_API_KEY"],  # sk-bf-...
)

resp = client.responses.create(
    model="openai/gpt-4o-mini",  # provider/model
    input="Say hello in one short sentence.",
)
print(resp.output_text)

# Multi-turn: chain with previous_response_id
follow_up = client.responses.create(
    model="openai/gpt-4o-mini",
    input="Now say it more formally.",
    previous_response_id=resp.id,
)
print(follow_up.output_text)
