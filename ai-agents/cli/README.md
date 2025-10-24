# 💻 Brown Bear AI Agents CLI

Command-line interface for managing AI agents in the Brown Bear ALM platform.

## Installation

```bash
# Install globally
npm install -g @brownbear/ai-agents-cli

# Or use with pnpm
pnpm add -g @brownbear/ai-agents-cli
```

## Usage

```bash
# Initialize configuration
bb-ai init

# Start orchestrator
bb-ai orchestrator start

# List all agents
bb-ai agents list

# Create a new agent
bb-ai agents create code-review --name my-reviewer

# Deploy an agent
bb-ai agents deploy my-reviewer

# View agent logs
bb-ai agents logs my-reviewer

# Create a task
bb-ai tasks create --type code_review --file ./src/app.ts

# Monitor system
bb-ai monitor agents
bb-ai monitor metrics

# Open web dashboard
bb-ai dashboard
```

## Configuration

Configuration file: `~/.brownbear-ai/config.yaml`

```yaml
orchestrator:
  url: http://localhost:8080
  apiKey: your-api-key

ai:
  provider: openai
  apiKey: your-openai-key
  model: gpt-4-turbo

redis:
  host: localhost
  port: 6379
```

## Commands

- `bb-ai init` - Initialize configuration
- `bb-ai orchestrator` - Manage orchestrator
- `bb-ai agents` - Manage agents
- `bb-ai tasks` - Manage tasks
- `bb-ai monitor` - Monitor system
- `bb-ai config` - Configuration management
- `bb-ai dashboard` - Open web dashboard
