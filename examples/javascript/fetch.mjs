// No SDK — fetch to the Responses API through the Bifrost gateway.
//
//   export BIFROST_BASE_URL=https://your-gateway.example.com/v1
//   export BIFROST_API_KEY=sk-bf-...
//   node fetch.mjs
const baseUrl = process.env.BIFROST_BASE_URL ?? "https://your-gateway.example.com/v1";
const apiKey = process.env.BIFROST_API_KEY; // sk-bf-...

const resp = await fetch(`${baseUrl}/responses`, {
  method: "POST",
  headers: {
    Authorization: `Bearer ${apiKey}`,
    "Content-Type": "application/json",
  },
  body: JSON.stringify({
    model: "openai/gpt-4o-mini", // provider/model
    input: "Say hello in one short sentence.",
  }),
});

if (!resp.ok) throw new Error(`Gateway error ${resp.status}: ${await resp.text()}`);
const data = await resp.json();
console.log(data.output_text ?? data.output);
