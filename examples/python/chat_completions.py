"""Chat Completions (fallback) through the Bifrost gateway, via the OpenAI SDK.

Use this when a client can't do the Responses API. Note the model can be ANY
provider (here: Claude) — the gateway routes by the provider/ prefix.

    export BIFROST_BASE_URL=https://your-gateway.example.com/v1
    export BIFROST_API_KEY=sk-bf-...
    pip install openai
    python chat_completions.py
"""
import os

from openai import OpenAI

client = OpenAI(
    base_url=os.environ.get("BIFROST_BASE_URL", "https://your-gateway.example.com/v1"),
    api_key=os.environ["BIFROST_API_KEY"],  # sk-bf-...
)

resp = client.chat.completions.create(
    model="anthropic/claude-3-5-sonnet-20241022",  # provider/model
    messages=[{"role": "user", "content": "Say hello in one short sentence."}],
)
print(resp.choices[0].message.content)
