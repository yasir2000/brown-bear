# 🦙 Ollama Integration Guide

Complete guide for using local LLM models with Ollama in the Brown Bear AI Multi-Agent System.

## Overview

Ollama allows you to run powerful open-source LLMs locally, providing:
- **Privacy**: Your code never leaves your infrastructure
- **Cost**: No API costs for model usage
- **Speed**: Low latency for local inference
- **Customization**: Fine-tune models for your specific use case

## Supported Models

- **Llama 2** (7B, 13B, 70B)
- **Mistral** (7B, 8x7B Mixtral)
- **CodeLlama** (7B, 13B, 34B)
- **Phi-2** (2.7B)
- **Neural Chat** (7B)
- **Starling** (7B)
- And many more...

## Installation

### 1. Install Ollama

```bash
# macOS
brew install ollama

# Linux
curl -fsSL https://ollama.ai/install.sh | sh

# Windows
# Download from https://ollama.ai/download
```

### 2. Pull Models

```bash
# For general purpose
ollama pull llama2

# For code-specific tasks
ollama pull codellama

# For faster inference
ollama pull mistral

# For advanced coding
ollama pull deepseek-coder
```

### 3. Start Ollama Server

```bash
ollama serve
# Server runs on http://localhost:11434
```

## Configuration

### Environment Variables

```bash
# .env
AI_PROVIDER=ollama
AI_MODEL=codellama
OLLAMA_BASE_URL=http://localhost:11434
OLLAMA_NUM_CTX=4096  # Context window size
OLLAMA_NUM_GPU=1     # Number of GPUs to use
OLLAMA_NUM_THREAD=8  # Number of CPU threads
```

### Framework Configuration

```typescript
import { AgentFramework } from '@brownbear/ai-agents-framework';

const framework = new AgentFramework({
  provider: 'ollama',
  model: 'codellama',
  orchestratorUrl: 'http://localhost:8080',
  ollama: {
    baseUrl: 'http://localhost:11434',
    numCtx: 4096,
    temperature: 0.3,
  },
  redis: {
    host: 'localhost',
    port: 6379,
  },
  logging: {
    level: 'info',
    format: 'pretty',
  },
});

await framework.initialize();
```

## Usage Examples

### 1. Code Review Agent with CodeLlama

```typescript
import { CodeReviewAgent } from '@brownbear/ai-agents-framework';

const codeReviewer = new CodeReviewAgent({
  name: 'local-code-reviewer',
  type: 'code_review',
  capabilities: ['code-quality', 'bug-detection', 'refactoring'],
  model: 'codellama:13b',
  temperature: 0.2,
  systemPrompt: `You are an expert code reviewer. Analyze code for:
    - Bugs and potential issues
    - Code quality and maintainability
    - Performance optimizations
    - Security vulnerabilities
    Provide specific, actionable feedback.`,
});

await framework.registerAgent(codeReviewer.getConfig());

// Review a pull request
const result = await framework.createTask({
  type: 'code_review',
  priority: 'high',
  payload: {
    files: [
      { path: 'src/api/users.ts', content: '...' },
      { path: 'src/models/user.ts', content: '...' },
    ],
  },
});
```

### 2. Documentation Agent with Llama 2

```typescript
const docAgent = new DocumentationAgent({
  name: 'doc-generator',
  type: 'documentation',
  capabilities: ['api-docs', 'readme', 'inline-comments'],
  model: 'llama2:13b',
  temperature: 0.5,
  systemPrompt: `Generate clear, comprehensive documentation.
    Include examples, parameter descriptions, and return values.`,
});

await framework.registerAgent(docAgent.getConfig());

// Generate documentation
await framework.createTask({
  type: 'documentation',
  priority: 'medium',
  payload: {
    sourceCode: fileContent,
    docType: 'api',
    format: 'markdown',
  },
});
```

### 3. Security Agent with Mistral

```typescript
const securityAgent = new SecurityAgent({
  name: 'security-scanner',
  type: 'security',
  capabilities: ['vulnerability-scan', 'dependency-audit', 'owasp'],
  model: 'mistral:7b',
  temperature: 0.1,
  systemPrompt: `You are a security expert. Identify:
    - SQL injection vulnerabilities
    - XSS vulnerabilities
    - Authentication issues
    - Insecure dependencies
    Provide severity ratings and remediation steps.`,
});

await framework.registerAgent(securityAgent.getConfig());
```

### 4. Multi-Agent Collaboration with Ollama

```typescript
// Create a team of local agents
const agents = {
  architect: await framework.registerAgent({
    name: 'solution-architect',
    type: 'custom',
    model: 'llama2:70b',
    capabilities: ['system-design', 'architecture-review'],
  }),
  
  developer: await framework.registerAgent({
    name: 'senior-developer',
    type: 'code_review',
    model: 'codellama:34b',
    capabilities: ['code-implementation', 'refactoring'],
  }),
  
  tester: await framework.registerAgent({
    name: 'qa-engineer',
    type: 'testing',
    model: 'mistral:7b',
    capabilities: ['test-generation', 'test-review'],
  }),
  
  reviewer: await framework.registerAgent({
    name: 'tech-lead',
    type: 'code_review',
    model: 'deepseek-coder:33b',
    capabilities: ['code-review', 'best-practices'],
  }),
};

// Orchestrate a feature development workflow
async function developFeature(featureSpec: string) {
  // 1. Architect designs solution
  const design = await framework.createTask({
    type: 'custom',
    priority: 'high',
    assignedTo: agents.architect,
    payload: {
      requirement: featureSpec,
      action: 'design-solution',
    },
  });
  
  // 2. Developer implements
  const implementation = await framework.createTask({
    type: 'custom',
    priority: 'high',
    assignedTo: agents.developer,
    payload: {
      design: await framework.getTask(design),
      action: 'implement-feature',
    },
  });
  
  // 3. Tester generates tests
  const tests = await framework.createTask({
    type: 'test_generation',
    priority: 'high',
    assignedTo: agents.tester,
    payload: {
      implementation: await framework.getTask(implementation),
    },
  });
  
  // 4. Reviewer reviews everything
  const review = await framework.createTask({
    type: 'code_review',
    priority: 'high',
    assignedTo: agents.reviewer,
    payload: {
      design: await framework.getTask(design),
      implementation: await framework.getTask(implementation),
      tests: await framework.getTask(tests),
    },
  });
  
  return {
    design: await framework.getTask(design),
    implementation: await framework.getTask(implementation),
    tests: await framework.getTask(tests),
    review: await framework.getTask(review),
  };
}
```

## Model Selection Guide

### Code Review & Analysis
- **Best**: `codellama:34b` or `deepseek-coder:33b`
- **Fast**: `codellama:7b` or `mistral:7b`
- **Balanced**: `codellama:13b`

### Documentation Generation
- **Best**: `llama2:70b` or `mistral:8x7b`
- **Fast**: `llama2:7b` or `phi-2`
- **Balanced**: `llama2:13b` or `mistral:7b`

### Security Analysis
- **Best**: `deepseek-coder:33b` or `llama2:70b`
- **Fast**: `mistral:7b`
- **Balanced**: `codellama:13b`

### Test Generation
- **Best**: `codellama:34b` or `deepseek-coder:33b`
- **Fast**: `codellama:7b`
- **Balanced**: `codellama:13b`

## Performance Optimization

### 1. Context Window Management

```typescript
// Optimize for large code files
const agent = await framework.registerAgent({
  name: 'large-file-reviewer',
  type: 'code_review',
  model: 'codellama:13b',
  maxTokens: 8192,  // Larger context
  ollama: {
    numCtx: 8192,
  },
});
```

### 2. GPU Acceleration

```bash
# Check GPU usage
ollama ps

# Configure GPU layers
export OLLAMA_NUM_GPU=1
export OLLAMA_GPU_LAYERS=35
```

### 3. Batch Processing

```typescript
// Process multiple files efficiently
const files = ['file1.ts', 'file2.ts', 'file3.ts'];

const reviews = await Promise.all(
  files.map(file =>
    framework.createTask({
      type: 'code_review',
      payload: { file },
    })
  )
);
```

### 4. Model Quantization

```bash
# Use quantized models for faster inference
ollama pull codellama:7b-q4_0  # 4-bit quantization
ollama pull mistral:7b-q5_K_M  # 5-bit quantization
```

## Advanced Features

### Custom Model Fine-tuning

```bash
# Create a Modelfile
cat > Modelfile << EOF
FROM codellama:13b

# Set custom parameters
PARAMETER temperature 0.2
PARAMETER top_p 0.9
PARAMETER top_k 40

# Custom system prompt
SYSTEM You are a specialized code reviewer for TypeScript and Node.js applications.
Focus on:
- TypeScript type safety
- Async/await best practices
- Error handling
- Performance optimizations
EOF

# Create custom model
ollama create my-ts-reviewer -f Modelfile
```

### Use Custom Model

```typescript
const agent = await framework.registerAgent({
  name: 'typescript-specialist',
  type: 'code_review',
  model: 'my-ts-reviewer',
  capabilities: ['typescript', 'nodejs', 'async-patterns'],
});
```

### Streaming Responses

```typescript
// For real-time feedback
framework.on('task:update', (event) => {
  if (event.streaming) {
    console.log('Partial result:', event.partialData);
  }
});
```

## Hybrid Deployment

### Mix Local and Cloud Models

```typescript
// Use Ollama for code review (privacy-sensitive)
const codeReviewer = await framework.registerAgent({
  name: 'local-reviewer',
  type: 'code_review',
  model: 'codellama:34b',
  provider: 'ollama',
});

// Use GPT-4 for complex architecture decisions
const architect = await framework.registerAgent({
  name: 'cloud-architect',
  type: 'custom',
  model: 'gpt-4-turbo',
  provider: 'openai',
  apiKey: process.env.OPENAI_API_KEY,
});

// Route tasks based on sensitivity
function routeTask(task: TaskConfig) {
  if (task.payload.containsSensitiveCode) {
    task.assignedTo = codeReviewer;
  } else {
    task.assignedTo = architect;
  }
  return framework.createTask(task);
}
```

## Troubleshooting

### Model Not Found

```bash
# List available models
ollama list

# Pull missing model
ollama pull codellama
```

### Out of Memory

```bash
# Use smaller model or quantized version
ollama pull codellama:7b-q4_0

# Or reduce context window
export OLLAMA_NUM_CTX=2048
```

### Slow Inference

```bash
# Enable GPU
export OLLAMA_NUM_GPU=1

# Increase threads
export OLLAMA_NUM_THREAD=16

# Use smaller/quantized model
ollama pull mistral:7b-q4_0
```

## CLI Usage with Ollama

```bash
# Initialize with Ollama
bb-ai init
# Select: ollama
# Model: codellama

# Create agent with local model
bb-ai agents create code-review \
  --name local-reviewer \
  --model codellama:13b \
  --capabilities security,performance

# Run task
bb-ai tasks create code-review \
  --file ./src/app.ts \
  --model codellama:13b
```

## Best Practices

1. **Model Selection**: Start with smaller models (7B) for testing, scale up as needed
2. **Context Management**: Keep context windows appropriate for your hardware
3. **Privacy**: Use Ollama for sensitive code, cloud models for general tasks
4. **Caching**: Enable response caching for repeated analyses
5. **Monitoring**: Track inference times and adjust models accordingly

## Resources

- [Ollama Documentation](https://ollama.ai/docs)
- [Ollama Models Library](https://ollama.ai/library)
- [Model Comparison Guide](https://ollama.ai/models)
- [Brown Bear AI Discord](https://discord.gg/brownbear) - #ollama channel

## Next Steps

- [LangChain Integration](./langchain-integration.md)
- [CrewAI Patterns](./crewai-patterns.md)
- [Performance Tuning](../operations/performance.md)
- [Custom Model Training](../advanced/custom-models.md)
