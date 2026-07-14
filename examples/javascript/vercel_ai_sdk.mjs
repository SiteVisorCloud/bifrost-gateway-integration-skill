// Vercel AI SDK through the Bifrost gateway.
//
//   export BIFROST_BASE_URL=https://your-gateway.example.com/v1
//   export BIFROST_API_KEY=sk-bf-...
//   npm install ai @ai-sdk/openai
//   node vercel_ai_sdk.mjs
import { createOpenAI } from "@ai-sdk/openai";
import { generateText } from "ai";

const gateway = createOpenAI({
  baseURL: process.env.BIFROST_BASE_URL ?? "https://your-gateway.example.com/v1",
  apiKey: process.env.BIFROST_API_KEY, // sk-bf-...
});

const { text } = await generateText({
  // .responses(...) uses the Responses API (preferred); gateway("...") would use chat.
  model: gateway.responses("openai/gpt-4o-mini"), // provider/model
  prompt: "Say hello in one short sentence.",
});
console.log(text);
