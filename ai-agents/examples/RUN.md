# 🚀 How to Run the End-to-End Scenario

This guide walks you through running the complete AI multi-agent example from scratch.

## Prerequisites

### 1. System Requirements

- **Node.js**: 18.x or higher
- **Docker**: For Redis and PostgreSQL
- **pnpm**: 8.x or higher
- **Ollama**: For local LLM models
- **RAM**: Minimum 16GB (32GB recommended for larger models)
- **Disk**: ~20GB for models

### 2. Install Ollama

#### macOS
```bash
brew install ollama
```

#### Linux
```bash
curl -fsSL https://ollama.ai/install.sh | sh
```

#### Windows
Download from [https://ollama.ai/download](https://ollama.ai/download)

### 3. Pull Required Models

This will download ~15GB of models. Grab a coffee! ☕

```bash
# Start Ollama service
ollama serve

# In a new terminal, pull models
ollama pull codellama:13b    # ~7GB - Code review & analysis
ollama pull mistral:7b       # ~4GB - Security scanning
ollama pull llama2:13b       # ~7GB - Documentation & DevOps
ollama pull llama2:70b       # ~40GB - Final decisions (optional)

# Verify models are available
ollama list
```

**Note**: If you don't have enough RAM/disk for `llama2:70b`, the example will fall back to `llama2:13b`.

## Step-by-Step Setup

### Step 1: Clone and Navigate

```bash
cd /path/to/brown-bear
cd ai-agents
```

### Step 2: Install Dependencies

```bash
# Install all workspace dependencies
pnpm install

# This will install dependencies for:
# - framework
# - sdk
# - api
# - cli
# - web-dashboard
# - examples
```

### Step 3: Start Infrastructure Services

```bash
# Start Redis (required)
docker run -d \
  --name brownbear-redis \
  -p 6379:6379 \
  redis:alpine

# Start PostgreSQL (optional, for persistence)
docker run -d \
  --name brownbear-postgres \
  -e POSTGRES_DB=brownbear_ai \
  -e POSTGRES_USER=postgres \
  -e POSTGRES_PASSWORD=postgres \
  -p 5432:5432 \
  postgres:15-alpine

# Verify services are running
docker ps
```

### Step 4: Configure Environment

```bash
# Copy example environment file
cd examples
cp .env.example .env

# Edit .env if needed (defaults should work)
nano .env
```

### Step 5: Build the Framework

```bash
# Build framework
cd ../framework
pnpm build

# Build SDK
cd ../sdk
pnpm build

# Build API server
cd ../api
pnpm build
```

### Step 6: Start the Framework Orchestrator

```bash
# In a new terminal
cd ai-agents/framework
pnpm start

# You should see:
# 🚀 Brown Bear AI Framework starting...
# ✅ Connected to Redis
# ✅ Framework initialized
# 🎧 Listening on http://localhost:8080
```

### Step 7: Run the End-to-End Example

```bash
# In another terminal
cd ai-agents/examples
pnpm run e2e

# Or run directly with ts-node
npx ts-node end-to-end-scenario.ts
```

## Expected Output

The scenario will run for approximately 2-5 minutes depending on your hardware and model sizes.

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

...
```

## Troubleshooting

### Ollama Connection Error

```bash
# Error: connect ECONNREFUSED 127.0.0.1:11434

# Solution: Make sure Ollama is running
ollama serve
```

### Redis Connection Error

```bash
# Error: connect ECONNREFUSED 127.0.0.1:6379

# Solution: Start Redis
docker start brownbear-redis

# Or if container doesn't exist:
docker run -d --name brownbear-redis -p 6379:6379 redis:alpine
```

### Model Not Found

```bash
# Error: model 'codellama:13b' not found

# Solution: Pull the model
ollama pull codellama:13b

# List available models
ollama list
```

### Out of Memory

```bash
# Error: Cannot allocate memory

# Solution: Use smaller models
# Edit examples/end-to-end-scenario.ts and change:
# - codellama:13b -> codellama:7b
# - llama2:13b -> llama2:7b
# - llama2:70b -> llama2:13b
```

### Framework Not Responding

```bash
# Check if framework is running
curl http://localhost:8080/health

# If not responding, restart:
cd ai-agents/framework
pnpm start
```

### TypeScript Compilation Errors

```bash
# Clean and rebuild
cd ai-agents
pnpm clean
pnpm install
pnpm build
```

## Performance Tips

### Speed Up Inference

1. **Use GPU acceleration** (if available):
```bash
# Check GPU usage
nvidia-smi

# Ollama automatically uses GPU if available
export OLLAMA_NUM_GPU=1
```

2. **Use smaller models**:
- `codellama:7b` instead of `codellama:13b` (~50% faster)
- `mistral:7b-q4_0` (quantized, ~30% faster)

3. **Increase context window**:
```bash
export OLLAMA_NUM_CTX=8192  # Default is 4096
```

4. **Use more CPU threads**:
```bash
export OLLAMA_NUM_THREAD=16  # Adjust based on your CPU
```

### Reduce Memory Usage

1. **Use quantized models**:
```bash
ollama pull codellama:7b-q4_0  # 4-bit quantization
ollama pull mistral:7b-q5_K_M  # 5-bit quantization
```

2. **Run one model at a time**: Edit the scenario to use the same model for all steps.

3. **Reduce context window**:
```bash
export OLLAMA_NUM_CTX=2048
```

## Next Steps

### Try Other Examples

```bash
# Simple code review with single agent
pnpm run simple

# Multi-agent collaboration
pnpm run multi-agent

# Ollama-specific features
pnpm run ollama

# LangChain workflows
pnpm run langchain

# CrewAI hierarchical teams
pnpm run crewai
```

### Modify the Scenario

1. **Change the code being reviewed**: Edit `examplePullRequest` in `end-to-end-scenario.ts`
2. **Add more review steps**: Extend the LangGraph workflow
3. **Use different models**: Change model names in the agents
4. **Customize agent behavior**: Modify agent system prompts

### Use Cloud Models

For faster inference or when local resources are limited:

```bash
# Edit .env
AI_PROVIDER=openai
OPENAI_API_KEY=sk-...
OPENAI_MODEL=gpt-4-turbo-preview

# Run example
pnpm run e2e
```

### Monitor Performance

```bash
# Watch Ollama logs
ollama logs

# Monitor resource usage
htop  # or top on macOS

# Check Redis
redis-cli monitor
```

## Support

- **Documentation**: [../docs/](../docs/)
- **Discord**: https://discord.gg/brownbear
- **GitHub Issues**: https://github.com/yasir2000/brown-bear/issues

## Clean Up

When you're done:

```bash
# Stop infrastructure
docker stop brownbear-redis brownbear-postgres
docker rm brownbear-redis brownbear-postgres

# Stop Ollama (if you want)
pkill ollama

# Remove models (to free disk space)
ollama rm codellama:13b
ollama rm mistral:7b
ollama rm llama2:13b
```

---

🎉 **Enjoy exploring the Brown Bear AI Multi-Agent System!**
