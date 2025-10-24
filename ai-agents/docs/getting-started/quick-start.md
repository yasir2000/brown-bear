# 🚀 Quick Start Guide

Get started with the Brown Bear AI Multi-Agent System in minutes.

## Prerequisites

- **Node.js** 18+ and pnpm
- **Redis** 6+
- **Docker** (optional, for containerized deployment)
- **OpenAI API Key** or other AI provider credentials

## Installation

### 1. Install the CLI

```bash
npm install -g @brownbear/ai-agents-cli
# or
pnpm add -g @brownbear/ai-agents-cli
```

### 2. Initialize Configuration

```bash
bb-ai init
```

Follow the interactive prompts to configure:
- Orchestrator URL
- AI provider (OpenAI, Anthropic, Ollama)
- API keys
- Redis connection

### 3. Start Required Services

```bash
# Start Redis (if not running)
docker run -d -p 6379:6379 redis:7-alpine

# Start the API server
cd ai-agents/api
pnpm install
pnpm dev
```

### 4. Create Your First Agent

```bash
# Create a code review agent
bb-ai agents create code-review --name senior-reviewer --capabilities security,performance

# List agents
bb-ai agents list
```

### 5. Open the Web Dashboard

```bash
# Start the dashboard
cd ai-agents/web-dashboard
pnpm install
pnpm dev

# Access at http://localhost:3000
```

## Your First Integration

### Automated Code Review on Pull Requests

```typescript
import { AgentFramework, CodeReviewAgent } from '@brownbear/ai-agents-framework';

// Initialize framework
const framework = new AgentFramework({
  apiKey: process.env.OPENAI_API_KEY,
  orchestratorUrl: 'http://localhost:8080',
  provider: 'openai',
  model: 'gpt-4-turbo',
});

await framework.initialize();

// Create code review agent
const reviewerConfig = {
  name: 'senior-code-reviewer',
  type: 'code_review',
  capabilities: ['security', 'performance', 'best-practices'],
  model: 'gpt-4-turbo',
  temperature: 0.3,
};

const agentId = await framework.registerAgent(reviewerConfig);

// Listen for GitHub webhook
app.post('/webhooks/github', async (req, res) => {
  const event = req.body;
  
  if (event.action === 'opened' && event.pull_request) {
    // Create review task
    await framework.createTask({
      type: 'code_review',
      priority: 'high',
      payload: {
        repository: event.repository.full_name,
        prNumber: event.pull_request.number,
        files: event.pull_request.changed_files,
      },
    });
  }
  
  res.sendStatus(200);
});
```

## Next Steps

- **[Create Custom Agents](./agents/custom.md)** - Build specialized agents for your needs
- **[Configure Integrations](./integration/)** - Connect with your existing tools
- **[Deploy to Production](./deployment/)** - Scale your multi-agent system
- **[Best Practices](./advanced/)** - Advanced patterns and optimization

## Common Issues

### Redis Connection Error

Ensure Redis is running:
```bash
redis-cli ping
# Should return: PONG
```

### API Key Not Found

Check your configuration:
```bash
bb-ai config show
```

### Agent Not Activating

Check the logs:
```bash
bb-ai agents logs <agent-id>
```

## Support

- 📖 [Full Documentation](./README.md)
- 💬 [Community Forum](https://community.brownbear.dev)
- 🐛 [Report Issues](https://github.com/yasir2000/brown-bear/issues)
- 💡 [Feature Requests](https://github.com/yasir2000/brown-bear/discussions)
