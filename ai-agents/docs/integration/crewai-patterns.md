# 🚢 CrewAI Integration & Patterns

Leverage CrewAI's agent collaboration patterns in Brown Bear ALM.

## Overview

CrewAI provides powerful patterns for:
- **Role-based Agents**: Agents with specific roles and expertise
- **Task Delegation**: Hierarchical task distribution
- **Collaborative Problem Solving**: Agents working together
- **Sequential & Parallel Workflows**: Flexible execution patterns

## Installation

```bash
# Already included in framework dependencies
pnpm add crewai
```

## Core Concepts

### Agents as Crew Members

```typescript
import { Crew, Agent, Task, Process } from 'crewai';
import { ChatOllama } from '@langchain/community/chat_models/ollama';

// Define specialized agents
const seniorEngineer = new Agent({
  role: 'Senior Software Engineer',
  goal: 'Write high-quality, maintainable code',
  backstory: `You are a senior engineer with 10+ years of experience.
    You excel at writing clean, efficient code and following best practices.`,
  llm: new ChatOllama({ model: 'codellama:34b' }),
  verbose: true,
  allowDelegation: true,
});

const qaEngineer = new Agent({
  role: 'QA Engineer',
  goal: 'Ensure code quality through comprehensive testing',
  backstory: `You are a meticulous QA engineer who finds edge cases
    and ensures robust test coverage.`,
  llm: new ChatOllama({ model: 'mistral:7b' }),
  verbose: true,
  allowDelegation: false,
});

const securityExpert = new Agent({
  role: 'Security Specialist',
  goal: 'Identify and fix security vulnerabilities',
  backstory: `You are a security expert who specializes in finding
    vulnerabilities and ensuring secure coding practices.`,
  llm: new ChatOllama({ model: 'deepseek-coder:33b' }),
  verbose: true,
  allowDelegation: false,
});

const techLead = new Agent({
  role: 'Technical Lead',
  goal: 'Coordinate team and ensure project success',
  backstory: `You are a technical lead who reviews work, makes decisions,
    and ensures the team delivers high-quality solutions.`,
  llm: new ChatOllama({ model: 'llama2:70b' }),
  verbose: true,
  allowDelegation: true,
});
```

### Defining Tasks

```typescript
const codeImplementationTask = new Task({
  description: `Implement a user authentication system with:
    - JWT token generation
    - Password hashing with bcrypt
    - Email verification
    - Rate limiting
    
    Use TypeScript and follow SOLID principles.`,
  agent: seniorEngineer,
  expectedOutput: 'Complete, production-ready code with inline documentation',
});

const testGenerationTask = new Task({
  description: `Create comprehensive tests for the authentication system:
    - Unit tests for all functions
    - Integration tests for API endpoints
    - Edge case testing
    - Security testing
    
    Use Jest and achieve >90% coverage.`,
  agent: qaEngineer,
  expectedOutput: 'Complete test suite with high coverage',
});

const securityReviewTask = new Task({
  description: `Review the authentication system for security:
    - Check for SQL injection vulnerabilities
    - Verify proper password hashing
    - Review JWT implementation
    - Check rate limiting effectiveness
    
    Provide detailed security report.`,
  agent: securityExpert,
  expectedOutput: 'Security audit report with recommendations',
});

const finalReviewTask = new Task({
  description: `Review all work and make final decision:
    - Review code quality
    - Check test coverage
    - Verify security concerns addressed
    - Make approval/rejection decision
    
    Provide comprehensive feedback.`,
  agent: techLead,
  expectedOutput: 'Final review with approval status',
});
```

### Creating a Crew

```typescript
// Sequential process - tasks execute one after another
const developmentCrew = new Crew({
  agents: [seniorEngineer, qaEngineer, securityExpert, techLead],
  tasks: [
    codeImplementationTask,
    testGenerationTask,
    securityReviewTask,
    finalReviewTask,
  ],
  process: Process.Sequential,
  verbose: true,
});

// Execute the crew
const result = await developmentCrew.kickoff();
console.log('Development complete:', result);
```

## Integration with Brown Bear

### 1. CrewAI-Powered Code Review

```typescript
import { AgentFramework } from '@brownbear/ai-agents-framework';
import { Crew, Agent, Task, Process } from 'crewai';

class CrewAICodeReviewAgent extends BaseAgent {
  private crew: Crew;

  constructor(config: AgentConfig) {
    super(config);
    
    // Create crew for code review
    const codeAnalyst = new Agent({
      role: 'Code Analyst',
      goal: 'Analyze code structure and complexity',
      llm: new ChatOllama({ model: 'codellama:13b' }),
    });

    const securityReviewer = new Agent({
      role: 'Security Reviewer',
      goal: 'Find security vulnerabilities',
      llm: new ChatOllama({ model: 'mistral:7b' }),
    });

    const performanceExpert = new Agent({
      role: 'Performance Expert',
      goal: 'Identify performance bottlenecks',
      llm: new ChatOllama({ model: 'codellama:13b' }),
    });

    this.crew = new Crew({
      agents: [codeAnalyst, securityReviewer, performanceExpert],
      process: Process.Sequential,
    });
  }

  protected async onExecuteTask(task: TaskConfig): Promise<unknown> {
    const tasks = this.createCrewTasks(task.payload.code);
    this.crew.tasks = tasks;
    
    const result = await this.crew.kickoff();
    return this.parseCrewResult(result);
  }

  private createCrewTasks(code: string): Task[] {
    return [
      new Task({
        description: `Analyze this code:\n${code}`,
        expectedOutput: 'Code analysis report',
      }),
      new Task({
        description: `Review security of the analyzed code`,
        expectedOutput: 'Security report',
      }),
      new Task({
        description: `Review performance of the code`,
        expectedOutput: 'Performance report',
      }),
    ];
  }

  private parseCrewResult(result: any) {
    return {
      summary: result.summary,
      analysis: result.taskOutputs,
      recommendations: result.recommendations,
    };
  }
}
```

### 2. Hierarchical Development Workflow

```typescript
// Hierarchical process - manager delegates to specialized agents
const hierarchicalCrew = new Crew({
  agents: [techLead, seniorEngineer, qaEngineer, securityExpert],
  tasks: [
    new Task({
      description: 'Develop and deliver a production-ready feature',
      expectedOutput: 'Complete, tested, secure feature',
    }),
  ],
  process: Process.Hierarchical,
  manager: techLead,
  verbose: true,
});

// Tech lead will automatically delegate to appropriate team members
const result = await hierarchicalCrew.kickoff();
```

### 3. Full-Stack Feature Development

```typescript
// Create comprehensive development crew
const fullStackCrew = new Crew({
  agents: [
    new Agent({
      role: 'Backend Developer',
      goal: 'Build robust APIs',
      llm: new ChatOllama({ model: 'codellama:34b' }),
    }),
    new Agent({
      role: 'Frontend Developer',
      goal: 'Create beautiful, functional UIs',
      llm: new ChatOllama({ model: 'codellama:13b' }),
    }),
    new Agent({
      role: 'Database Architect',
      goal: 'Design efficient database schemas',
      llm: new ChatOllama({ model: 'mistral:7b' }),
    }),
    new Agent({
      role: 'DevOps Engineer',
      goal: 'Setup CI/CD and infrastructure',
      llm: new ChatOllama({ model: 'llama2:13b' }),
    }),
  ],
  tasks: [
    new Task({
      description: 'Design and implement database schema',
      expectedOutput: 'Migration files and schema documentation',
    }),
    new Task({
      description: 'Implement RESTful API endpoints',
      expectedOutput: 'API implementation with OpenAPI documentation',
    }),
    new Task({
      description: 'Create frontend components',
      expectedOutput: 'React components with Storybook stories',
    }),
    new Task({
      description: 'Setup deployment pipeline',
      expectedOutput: 'GitHub Actions workflow and Dockerfile',
    }),
  ],
  process: Process.Sequential,
});

const feature = await fullStackCrew.kickoff();
```

## Advanced Patterns

### 1. Iterative Refinement

```typescript
async function iterativeCodeReview(code: string, maxIterations = 3) {
  const reviewer = new Agent({
    role: 'Code Reviewer',
    goal: 'Improve code quality',
    llm: new ChatOllama({ model: 'codellama:13b' }),
  });

  const refactorer = new Agent({
    role: 'Code Refactorer',
    goal: 'Refactor code based on feedback',
    llm: new ChatOllama({ model: 'codellama:34b' }),
  });

  let currentCode = code;
  
  for (let i = 0; i < maxIterations; i++) {
    const crew = new Crew({
      agents: [reviewer, refactorer],
      tasks: [
        new Task({
          description: `Review this code:\n${currentCode}`,
          agent: reviewer,
          expectedOutput: 'List of improvements needed',
        }),
        new Task({
          description: 'Refactor the code based on review feedback',
          agent: refactorer,
          expectedOutput: 'Improved code',
        }),
      ],
      process: Process.Sequential,
    });

    const result = await crew.kickoff();
    currentCode = result.finalCode;
    
    if (result.noIssuesFound) break;
  }
  
  return currentCode;
}
```

### 2. Specialized Domain Crews

```typescript
// Security-focused crew
const securityCrew = new Crew({
  agents: [
    new Agent({
      role: 'OWASP Specialist',
      goal: 'Find OWASP Top 10 vulnerabilities',
      llm: new ChatOllama({ model: 'deepseek-coder:33b' }),
    }),
    new Agent({
      role: 'Dependency Auditor',
      goal: 'Check for vulnerable dependencies',
      llm: new ChatOllama({ model: 'mistral:7b' }),
    }),
    new Agent({
      role: 'Cryptography Expert',
      goal: 'Verify encryption implementation',
      llm: new ChatOllama({ model: 'llama2:70b' }),
    }),
  ],
  process: Process.Hierarchical,
});

// Performance-focused crew
const performanceCrew = new Crew({
  agents: [
    new Agent({
      role: 'Algorithm Optimizer',
      goal: 'Optimize algorithm complexity',
      llm: new ChatOllama({ model: 'codellama:34b' }),
    }),
    new Agent({
      role: 'Database Query Optimizer',
      goal: 'Optimize database queries',
      llm: new ChatOllama({ model: 'mistral:7b' }),
    }),
    new Agent({
      role: 'Caching Specialist',
      goal: 'Implement effective caching strategies',
      llm: new ChatOllama({ model: 'llama2:13b' }),
    }),
  ],
  process: Process.Sequential,
});
```

### 3. Multi-Crew Orchestration

```typescript
async function comprehensiveReview(pullRequest: PullRequest) {
  // Run multiple specialized crews in parallel
  const [codeResult, securityResult, perfResult] = await Promise.all([
    developmentCrew.kickoff({ context: pullRequest }),
    securityCrew.kickoff({ context: pullRequest }),
    performanceCrew.kickoff({ context: pullRequest }),
  ]);

  // Meta-crew to synthesize results
  const metaCrew = new Crew({
    agents: [
      new Agent({
        role: 'Chief Architect',
        goal: 'Make final decision based on all reviews',
        llm: new ChatOllama({ model: 'llama2:70b' }),
      }),
    ],
    tasks: [
      new Task({
        description: `Synthesize these reviews and make final decision:
          Code Review: ${codeResult}
          Security Review: ${securityResult}
          Performance Review: ${perfResult}`,
        expectedOutput: 'Final approval/rejection with reasoning',
      }),
    ],
  });

  return await metaCrew.kickoff();
}
```

## Best Practices

1. **Role Clarity**: Give agents clear, specific roles
2. **Model Selection**: Use appropriate models for each agent's expertise
3. **Task Granularity**: Break complex tasks into smaller, focused tasks
4. **Allow Delegation**: Enable for manager/lead agents
5. **Expected Output**: Always define clear expected outputs
6. **Backstory**: Provide context to guide agent behavior

## Integration Example

```typescript
// Register CrewAI agents with Brown Bear framework
const framework = new AgentFramework({
  provider: 'ollama',
  model: 'codellama',
  orchestratorUrl: 'http://localhost:8080',
});

await framework.initialize();

// Create crew-powered agent
const crewAgent = new CrewAICodeReviewAgent({
  name: 'collaborative-reviewers',
  type: 'code_review',
  capabilities: ['multi-perspective', 'comprehensive-analysis'],
  model: 'codellama:13b',
});

await framework.registerAgent(crewAgent.getConfig());

// Use like any Brown Bear agent
await framework.createTask({
  type: 'code_review',
  priority: 'high',
  payload: {
    code: fileContent,
    language: 'TypeScript',
  },
});
```

## Resources

- [CrewAI Documentation](https://docs.crewai.com/)
- [CrewAI Examples](https://github.com/joaomdmoura/crewAI-examples)
- [Brown Bear Examples](../examples/crewai/)

## Next Steps

- [AutoGen Integration](./autogen-integration.md)
- [Multi-Framework Patterns](../advanced/multi-framework.md)
- [Custom Crew Templates](../advanced/crew-templates.md)
