// OpenAI SDK (Responses API, preferred) through the Bifrost gateway.
//
//   export BIFROST_BASE_URL=https://your-gateway.example.com/v1
//   export BIFROST_API_KEY=sk-bf-...
//   npm install openai
//   node openai_sdk.mjs
import OpenAI from "openai";

const client = new OpenAI({
  baseURL: process.env.BIFROST_BASE_URL ?? "https://your-gateway.example.com/v1",
  apiKey: process.env.BIFROST_API_KEY, // sk-bf-...
});

const resp = await client.responses.create({
  model: "openai/gpt-4o-mini", // provider/model
  input: "Say hello in one short sentence.",
});
console.log(resp.output_text);

// Fallback — Chat Completions (any provider via the provider/ prefix):
// const chat = await client.chat.completions.create({
//   model: "anthropic/claude-3-5-sonnet-20241022",
//   messages: [{ role: "user", content: "Say hello." }],
// });
// console.log(chat.choices[0].message.content);
