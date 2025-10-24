# 🎯 Examples

This directory contains practical examples demonstrating the Brown Bear AI Multi-Agent System.

## End-to-End Scenario

### [`end-to-end-scenario.ts`](./end-to-end-scenario.ts)

A complete, production-like workflow demonstrating all major features:

**What it does:**
1. **Code Review** - Analyzes pull request code quality using CodeLlama
2. **Security Scan** - Detects vulnerabilities using Mistral
3. **Performance Analysis** - Identifies bottlenecks using CodeLlama
4. **Test Generation** - Creates comprehensive Jest tests
5. **Documentation** - Updates API documentation using Llama2
6. **Decision Making** - Tech lead agent approves/rejects PR
7. **Deployment** - CrewAI team deploys to staging (if approved)
8. **Monitoring** - Sets up observability and alerts

**Technologies Used:**
- ✅ **LangGraph** - Multi-step stateful workflow
- ✅ **CrewAI** - Collaborative deployment team
- ✅ **Ollama** - Local LLM models (CodeLlama, Mistral, Llama2)
- ✅ **Brown Bear Framework** - Orchestration and task management

**Models Used:**
- `codellama:13b` - Code review and performance analysis
- `mistral:7b` - Security scanning and QA
- `llama2:13b` - Documentation and DevOps
- `llama2:70b` - Final decision making

### Running the Example

```bash
# Prerequisites
# 1. Start Ollama and pull required models
ollama pull codellama:13b
ollama pull mistral:7b
ollama pull llama2:13b
ollama pull llama2:70b

# 2. Start Redis
docker run -d -p 6379:6379 redis:alpine

# 3. Start the framework orchestrator
cd ai-agents/framework
pnpm install
pnpm build
pnpm start

# 4. Run the example
cd ai-agents/examples
pnpm install
npx ts-node end-to-end-scenario.ts
```

### Expected Output

```
═══════════════════════════════════════════════════════════
  🎯 Brown Bear AI Multi-Agent System - E2E Scenario
═══════════════════════════════════════════════════════════

🚀 Initializing Brown Bear AI Multi-Agent Framework...
✅ Framework initialized successfully

🔧 Creating LangGraph workflow for PR review...
✅ LangGraph workflow created

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  PHASE 1: AUTOMATED PULL REQUEST REVIEW
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

📝 Step 1: Running code review...
   ✓ Code review complete. Score: 85/100
   ✓ Found 2 issues, 3 suggestions

🔒 Step 2: Running security scan...
   ✓ Security scan complete. Severity: medium
   ✓ Found 1 potential vulnerabilities

⚡ Step 3: Analyzing performance...
   ✓ Performance analysis complete
   ✓ Found 0 bottlenecks

🧪 Step 4: Generating tests...
   ✓ Tests generated successfully

📚 Step 5: Updating documentation...
   ✓ Documentation updated

⚖️  Step 6: Making final decision...
   ✓ Decision: APPROVED
   ✓ Reasoning: Code quality is good, minor security issue can be addressed

═══════════════════════════════════════════════════════════
  📊 REVIEW RESULTS SUMMARY
═══════════════════════════════════════════════════════════

Code Quality:
  Score: 85/100
  Issues: 2
  Suggestions: 3

Security:
  Severity: medium
  Vulnerabilities: 1

Performance:
  Bottlenecks: 0

Final Decision:
  Status: APPROVED

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  PHASE 2: DEPLOYMENT TO STAGING
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

👥 Creating CrewAI deployment team...
🚀 Starting deployment process...
✅ Deployment completed successfully!

═══════════════════════════════════════════════════════════
  🎉 DEPLOYMENT SUMMARY
═══════════════════════════════════════════════════════════

Environment: staging
Status: deployed
Endpoint: https://staging.brownbear.com/api/auth
Monitoring: enabled
Tests: passed

═══════════════════════════════════════════════════════════
  ✨ END-TO-END SCENARIO COMPLETED SUCCESSFULLY
═══════════════════════════════════════════════════════════

Summary:
  ✓ Pull request reviewed by AI agents
  ✓ Code quality analyzed
  ✓ Security vulnerabilities detected
  ✓ Performance bottlenecks identified
  ✓ Tests generated automatically
  ✓ Documentation updated
  ✓ Deployment orchestrated (if approved)
  ✓ Monitoring enabled

👋 Framework shutdown complete
```

## Other Examples

### Basic Examples

- [`simple-code-review.ts`](./simple-code-review.ts) - Basic code review with single agent
- [`multi-agent-collaboration.ts`](./multi-agent-collaboration.ts) - Multiple agents working together
- [`ollama-local.ts`](./ollama-local.ts) - Using local Ollama models

### Advanced Examples

- [`langchain-workflow.ts`](./langchain-workflow.ts) - LangChain tools and chains
- [`langgraph-state-machine.ts`](./langgraph-state-machine.ts) - Complex state machines
- [`crewai-hierarchical.ts`](./crewai-hierarchical.ts) - Hierarchical agent teams
- [`hybrid-deployment.ts`](./hybrid-deployment.ts) - Mix of local and cloud models

### Integration Examples

- [`git-webhook-handler.ts`](./git-webhook-handler.ts) - Handle Git webhooks
- [`ci-cd-integration.ts`](./ci-cd-integration.ts) - CI/CD pipeline integration
- [`jira-integration.ts`](./jira-integration.ts) - Jira ticket automation

## Configuration

All examples use environment variables for configuration:

```bash
# .env
AI_PROVIDER=ollama
OLLAMA_BASE_URL=http://localhost:11434
REDIS_HOST=localhost
REDIS_PORT=6379
FRAMEWORK_URL=http://localhost:8080

# Optional: Use cloud models
OPENAI_API_KEY=sk-...
ANTHROPIC_API_KEY=sk-ant-...
```

## Customization

Each example can be customized by modifying:

1. **Models**: Change the Ollama models used
2. **Workflow**: Adjust the LangGraph state machine
3. **Agents**: Modify agent roles and backstories
4. **Tasks**: Change task descriptions and priorities

## Troubleshooting

### Models not found
```bash
ollama pull codellama:13b
ollama list  # Verify models are available
```

### Redis connection failed
```bash
docker ps  # Check if Redis is running
docker run -d -p 6379:6379 redis:alpine
```

### Framework not responding
```bash
cd ai-agents/framework
pnpm start  # Start the orchestrator
```

## Next Steps

- Read the [Quick Start Guide](../docs/getting-started/quick-start.md)
- Explore [Integration Guides](../docs/integration/)
- Check [API Documentation](../docs/api/)

## Contributing

To add a new example:

1. Create a new `.ts` file in this directory
2. Follow the existing pattern with clear comments
3. Add entry to this README
4. Include expected output
5. Submit a pull request

## Resources

- [LangGraph Documentation](https://langchain-ai.github.io/langgraphjs/)
- [CrewAI Documentation](https://docs.crewai.com/)
- [Ollama Models](https://ollama.ai/library)
- [Brown Bear AI Docs](../docs/)
