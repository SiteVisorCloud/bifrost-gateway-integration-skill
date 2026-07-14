"""No SDK — raw HTTP to the Responses API through the Bifrost gateway.

    export BIFROST_BASE_URL=https://your-gateway.example.com/v1
    export BIFROST_API_KEY=sk-bf-...
    pip install requests
    python raw_requests.py
"""
import os

import requests

base_url = os.environ.get("BIFROST_BASE_URL", "https://your-gateway.example.com/v1")
api_key = os.environ["BIFROST_API_KEY"]  # sk-bf-...

resp = requests.post(
    f"{base_url}/responses",
    headers={
        "Authorization": f"Bearer {api_key}",
        "Content-Type": "application/json",
    },
    json={
        "model": "openai/gpt-4o-mini",  # provider/model
        "input": "Say hello in one short sentence.",
    },
    timeout=60,
)
resp.raise_for_status()
data = resp.json()
print(data.get("output_text") or data.get("output"))
