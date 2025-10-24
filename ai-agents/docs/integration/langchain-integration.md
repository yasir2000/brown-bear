# 🦜 LangChain & LangGraph Integration

Deep integration with LangChain and LangGraph for powerful agent orchestration.

## Overview

Brown Bear AI leverages:
- **LangChain**: For LLM interactions, tool calling, and prompt management
- **LangGraph**: For stateful agent workflows and complex multi-agent orchestration

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    Brown Bear Framework                      │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌────────────┐  ┌────────────┐  ┌────────────────────┐   │
│  │ LangChain  │  │ LangGraph  │  │  Custom Agents     │   │
│  │            │  │            │  │                     │   │
│  │ • Chains   │  │ • Graphs   │  │ • CodeReview       │   │
│  │ • Tools    │  │ • State    │  │ • Testing          │   │
│  │ • Memory   │  │ • Nodes    │  │ • Security         │   │
│  │ • Prompts  │  │ • Edges    │  │ • Documentation    │   │
│  └────────────┘  └────────────┘  └────────────────────┘   │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

## Installation

```bash
# Core dependencies (already in framework/package.json)
pnpm add @langchain/core @langchain/langgraph
pnpm add @langchain/openai @langchain/anthropic @langchain/community
```

## LangChain Integration

### 1. Basic Agent with Tools

```typescript
import { ChatOpenAI } from '@langchain/openai';
import { ChatOllama } from '@langchain/community/chat_models/ollama';
import { AgentExecutor, createOpenAIFunctionsAgent } from 'langchain/agents';
import { pull } from 'langchain/hub';
import { DynamicTool } from '@langchain/core/tools';

// Define custom tools for code review
const codeAnalysisTool = new DynamicTool({
  name: 'analyze_code_complexity',
  description: 'Analyzes code complexity metrics (cyclomatic complexity, cognitive complexity)',
  func: async (code: string) => {
    // Your complexity analysis logic
    return JSON.stringify({
      cyclomaticComplexity: 5,
      cognitiveComplexity: 3,
      linesOfCode: 150,
    });
  },
});

const securityScanTool = new DynamicTool({
  name: 'scan_security_vulnerabilities',
  description: 'Scans code for common security vulnerabilities',
  func: async (code: string) => {
    // Your security scanning logic
    return JSON.stringify({
      vulnerabilities: [
        { type: 'SQL_INJECTION', severity: 'HIGH', line: 42 },
      ],
    });
  },
});

// Create LangChain agent
const llm = new ChatOllama({
  baseUrl: 'http://localhost:11434',
  model: 'codellama',
  temperature: 0.2,
});

const tools = [codeAnalysisTool, securityScanTool];

const prompt = await pull('hwchase17/openai-functions-agent');

const agent = await createOpenAIFunctionsAgent({
  llm,
  tools,
  prompt,
});

const agentExecutor = new AgentExecutor({
  agent,
  tools,
});

// Use in Brown Bear framework
class LangChainCodeReviewAgent extends BaseAgent {
  private executor: AgentExecutor;

  constructor(config: AgentConfig) {
    super(config);
    this.executor = agentExecutor;
  }

  protected async onExecuteTask(task: TaskConfig): Promise<unknown> {
    const result = await this.executor.invoke({
      input: `Review this code for quality and security:\n${task.payload.code}`,
    });
    
    return {
      review: result.output,
      toolCalls: result.intermediateSteps,
    };
  }
}
```

### 2. Memory-Enabled Agents

```typescript
import { BufferMemory } from 'langchain/memory';
import { ConversationChain } from 'langchain/chains';

class StatefulCodeReviewAgent extends BaseAgent {
  private memory: BufferMemory;
  private chain: ConversationChain;

  constructor(config: AgentConfig) {
    super(config);
    
    this.memory = new BufferMemory({
      returnMessages: true,
      memoryKey: 'chat_history',
    });

    const llm = new ChatOllama({
      model: 'codellama:13b',
    });

    this.chain = new ConversationChain({
      llm,
      memory: this.memory,
    });
  }

  protected async onExecuteTask(task: TaskConfig): Promise<unknown> {
    // Agent remembers previous reviews and context
    const result = await this.chain.call({
      input: task.payload.query,
    });
    
    return result;
  }
}
```

### 3. Custom Prompt Templates

```typescript
import { ChatPromptTemplate, MessagesPlaceholder } from '@langchain/core/prompts';

const codeReviewPrompt = ChatPromptTemplate.fromMessages([
  [
    'system',
    `You are an expert code reviewer specializing in {language}.
    
    Review the code for:
    - Code quality and maintainability
    - Performance optimizations
    - Security vulnerabilities
    - Best practices for {language}
    
    Provide specific, actionable feedback with line numbers.`,
  ],
  new MessagesPlaceholder('chat_history'),
  ['human', '{input}'],
]);

// Use with agent
const formatted = await codeReviewPrompt.formatMessages({
  language: 'TypeScript',
  input: 'Review this function...',
  chat_history: [],
});
```

## LangGraph Integration

### 1. Multi-Agent Workflow with State

```typescript
import { StateGraph, END } from '@langchain/langgraph';
import { BaseMessage } from '@langchain/core/messages';

// Define workflow state
interface CodeReviewState {
  code: string;
  language: string;
  complexityAnalysis?: any;
  securityScan?: any;
  performanceReview?: any;
  finalReview?: string;
  messages: BaseMessage[];
}

// Create workflow graph
const workflow = new StateGraph<CodeReviewState>({
  channels: {
    code: null,
    language: null,
    complexityAnalysis: null,
    securityScan: null,
    performanceReview: null,
    finalReview: null,
    messages: null,
  },
});

// Define nodes (agents)
async function analyzeComplexity(state: CodeReviewState) {
  const llm = new ChatOllama({ model: 'codellama' });
  const result = await llm.invoke([
    {
      role: 'user',
      content: `Analyze the complexity of this ${state.language} code:\n${state.code}`,
    },
  ]);
  
  return {
    ...state,
    complexityAnalysis: result.content,
  };
}

async function scanSecurity(state: CodeReviewState) {
  const llm = new ChatOllama({ model: 'mistral' });
  const result = await llm.invoke([
    {
      role: 'user',
      content: `Scan for security vulnerabilities in this code:\n${state.code}`,
    },
  ]);
  
  return {
    ...state,
    securityScan: result.content,
  };
}

async function reviewPerformance(state: CodeReviewState) {
  const llm = new ChatOllama({ model: 'codellama:13b' });
  const result = await llm.invoke([
    {
      role: 'user',
      content: `Review performance aspects of this code:\n${state.code}`,
    },
  ]);
  
  return {
    ...state,
    performanceReview: result.content,
  };
}

async function generateFinalReview(state: CodeReviewState) {
  const llm = new ChatOllama({ model: 'llama2:13b' });
  const result = await llm.invoke([
    {
      role: 'user',
      content: `Synthesize these analyses into a comprehensive review:
        
        Complexity: ${state.complexityAnalysis}
        Security: ${state.securityScan}
        Performance: ${state.performanceReview}
        
        Provide a structured, actionable review.`,
    },
  ]);
  
  return {
    ...state,
    finalReview: result.content,
  };
}

// Build graph
workflow.addNode('analyze_complexity', analyzeComplexity);
workflow.addNode('scan_security', scanSecurity);
workflow.addNode('review_performance', reviewPerformance);
workflow.addNode('generate_final_review', generateFinalReview);

// Define edges (workflow)
workflow.setEntryPoint('analyze_complexity');
workflow.addEdge('analyze_complexity', 'scan_security');
workflow.addEdge('scan_security', 'review_performance');
workflow.addEdge('review_performance', 'generate_final_review');
workflow.addEdge('generate_final_review', END);

// Compile graph
const app = workflow.compile();

// Use in Brown Bear agent
class LangGraphCodeReviewAgent extends BaseAgent {
  private workflow: typeof app;

  constructor(config: AgentConfig) {
    super(config);
    this.workflow = app;
  }

  protected async onExecuteTask(task: TaskConfig): Promise<unknown> {
    const result = await this.workflow.invoke({
      code: task.payload.code,
      language: task.payload.language || 'TypeScript',
      messages: [],
    });
    
    return result;
  }
}
```

### 2. Conditional Branching

```typescript
// Define conditional edge
function shouldRunDeepSecurity(state: CodeReviewState): string {
  // If complexity is high, run deep security scan
  const complexity = JSON.parse(state.complexityAnalysis || '{}');
  if (complexity.cyclomaticComplexity > 10) {
    return 'deep_security_scan';
  }
  return 'review_performance';
}

workflow.addConditionalEdges(
  'scan_security',
  shouldRunDeepSecurity,
  {
    deep_security_scan: 'deep_security_scan',
    review_performance: 'review_performance',
  }
);
```

### 3. Human-in-the-Loop

```typescript
import { HumanMessage } from '@langchain/core/messages';

async function requestHumanReview(state: CodeReviewState) {
  // Pause workflow for human input
  const humanInput = await promptForHumanReview({
    context: state,
    question: 'Does this security issue need immediate attention?',
  });
  
  return {
    ...state,
    messages: [...state.messages, new HumanMessage(humanInput)],
    requiresImmediateAction: humanInput.includes('yes'),
  };
}

workflow.addNode('human_review', requestHumanReview);
```

## Advanced Patterns

### 1. ReAct Pattern

```typescript
import { initializeAgentExecutorWithOptions } from 'langchain/agents';

const executor = await initializeAgentExecutorWithOptions(tools, llm, {
  agentType: 'chat-conversational-react-description',
  verbose: true,
});

// Agent reasons, acts, and observes iteratively
const result = await executor.call({
  input: 'Review this code and fix any security issues you find',
});
```

### 2. Plan-and-Execute

```typescript
import { PlanAndExecuteAgentExecutor } from 'langchain/experimental/plan_and_execute';

const planExecuteAgent = PlanAndExecuteAgentExecutor.fromLLMAndTools({
  llm,
  tools,
});

// Agent creates a plan first, then executes steps
const result = await planExecuteAgent.call({
  input: 'Perform a comprehensive code review including refactoring suggestions',
});
```

### 3. Multi-Agent Debate

```typescript
// Create debating agents
const agent1 = new ChatOllama({ model: 'codellama:13b' });
const agent2 = new ChatOllama({ model: 'mistral:7b' });

async function multiAgentDebate(code: string, rounds: number = 3) {
  let messages = [];
  
  for (let i = 0; i < rounds; i++) {
    // Agent 1 reviews
    const review1 = await agent1.invoke([
      ...messages,
      { role: 'user', content: `Review this code:\n${code}` },
    ]);
    messages.push({ role: 'assistant', content: review1.content, agent: 'agent1' });
    
    // Agent 2 critiques
    const critique = await agent2.invoke([
      ...messages,
      { role: 'user', content: 'Critique the previous review. What was missed?' },
    ]);
    messages.push({ role: 'assistant', content: critique.content, agent: 'agent2' });
  }
  
  // Synthesize final review
  const finalLLM = new ChatOllama({ model: 'llama2:70b' });
  const final = await finalLLM.invoke([
    ...messages,
    { role: 'user', content: 'Synthesize the debate into a final, comprehensive review.' },
  ]);
  
  return final.content;
}
```

## Integration with Brown Bear Framework

### Complete Example

```typescript
import { AgentFramework } from '@brownbear/ai-agents-framework';
import { StateGraph } from '@langchain/langgraph';
import { ChatOllama } from '@langchain/community/chat_models/ollama';

// Initialize Brown Bear framework
const framework = new AgentFramework({
  provider: 'ollama',
  model: 'codellama',
  orchestratorUrl: 'http://localhost:8080',
});

await framework.initialize();

// Create LangGraph workflow
const codeReviewWorkflow = createCodeReviewWorkflow();

// Register as Brown Bear agent
const agentId = await framework.registerAgent({
  name: 'langgraph-code-reviewer',
  type: 'code_review',
  capabilities: ['comprehensive-analysis', 'multi-step-reasoning'],
  model: 'codellama:13b',
  metadata: {
    workflow: 'langgraph',
    steps: ['complexity', 'security', 'performance', 'synthesis'],
  },
});

// Handle tasks with LangGraph
framework.on('task:assigned', async (event) => {
  if (event.agentId === agentId) {
    const task = await framework.getTask(event.taskId);
    
    // Execute LangGraph workflow
    const result = await codeReviewWorkflow.invoke({
      code: task.config.payload.code,
      language: task.config.payload.language,
      messages: [],
    });
    
    // Return result to framework
    await framework.completeTask(event.taskId, result);
  }
});
```

## Best Practices

1. **Model Selection**: Use appropriate models for each workflow step
2. **State Management**: Keep state minimal and serializable
3. **Error Handling**: Add error recovery nodes in graphs
4. **Caching**: Cache LLM responses for repeated queries
5. **Monitoring**: Log intermediate steps for debugging

## Resources

- [LangChain Documentation](https://js.langchain.com/docs/)
- [LangGraph Documentation](https://langchain-ai.github.io/langgraphjs/)
- [LangChain Hub](https://smith.langchain.com/hub)
- [Example Workflows](../examples/langchain/)

## Next Steps

- [CrewAI Integration](./crewai-patterns.md)
- [AutoGen Integration](./autogen-integration.md)
- [Advanced Workflows](../advanced/workflows.md)
