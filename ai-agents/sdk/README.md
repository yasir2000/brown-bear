# 🛠️ Brown Bear AI Agents SDK

TypeScript/JavaScript SDK for building and interacting with AI agents in the Brown Bear ALM platform.

## Installation

```bash
npm install @brownbear/ai-agents-sdk
# or
pnpm add @brownbear/ai-agents-sdk
```

## Quick Start

```typescript
import { AgentSDK, CodeReviewAgent } from '@brownbear/ai-agents-sdk';

// Initialize SDK
const sdk = new AgentSDK({
  apiKey: process.env.BROWNBEAR_AI_KEY,
  baseUrl: 'http://localhost:8080',
});

// Create and register an agent
const agent = sdk.createAgent(CodeReviewAgent, {
  name: 'my-code-reviewer',
  capabilities: ['security', 'performance'],
  model: 'gpt-4-turbo',
});

await agent.activate();

// Listen to events
agent.on('task:completed', (result) => {
  console.log('Task completed:', result);
});
```

## Features

- 🎯 **Simple API**: Intuitive, developer-friendly interface
- 🔄 **Event-Driven**: Real-time updates via events
- 📘 **TypeScript**: Full type safety and IntelliSense
- 🧩 **Extensible**: Easy to create custom agents
- 🔌 **Pluggable**: Works with any AI provider

## Documentation

See [SDK Documentation](../docs/sdk/) for detailed guides and API reference.
