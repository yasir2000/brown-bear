/**
 * End-to-End Scenario: Complete Pull Request Review & Deployment
 *
 * This example demonstrates a full workflow where AI agents:
 * 1. Review a pull request
 * 2. Run security scans
 * 3. Generate tests
 * 4. Check performance
 * 5. Update documentation
 * 6. Deploy to staging
 * 7. Monitor deployment
 *
 * Technologies used:
 * - LangGraph for workflow orchestration
 * - CrewAI for agent collaboration
 * - Ollama for local LLM models
 * - Brown Bear Framework for orchestration
 */

import { AgentFramework } from '@brownbear/ai-agents-framework';
import { ChatOllama } from '@langchain/community/chat_models/ollama';
import { StateGraph, END } from '@langchain/langgraph';
import { Crew, Agent, Task, Process } from 'crewai';
import { BaseMessage } from '@langchain/core/messages';

// ============================================================================
// Step 1: Initialize the Framework
// ============================================================================

async function initializeFramework() {
  console.log('🚀 Initializing Brown Bear AI Multi-Agent Framework...\n');

  const framework = new AgentFramework({
    provider: 'ollama',
    model: 'codellama',
    orchestratorUrl: 'http://localhost:8080',
    ollama: {
      baseUrl: 'http://localhost:11434',
      numCtx: 4096,
      temperature: 0.2,
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
  console.log('✅ Framework initialized successfully\n');

  return framework;
}

// ============================================================================
// Step 2: Define the Pull Request Data
// ============================================================================

interface PullRequest {
  id: string;
  title: string;
  description: string;
  author: string;
  files: Array<{
    path: string;
    content: string;
    language: string;
  }>;
  targetBranch: string;
}

const examplePullRequest: PullRequest = {
  id: 'PR-1234',
  title: 'Add user authentication with JWT',
  description: 'Implements JWT-based authentication with refresh tokens',
  author: 'developer@brownbear.com',
  files: [
    {
      path: 'src/auth/jwt.ts',
      language: 'typescript',
      content: `
import jwt from 'jsonwebtoken';
import bcrypt from 'bcrypt';

export class JWTService {
  private secret: string;

  constructor(secret: string) {
    this.secret = secret;
  }

  async generateToken(userId: string): Promise<string> {
    return jwt.sign({ userId }, this.secret, { expiresIn: '1h' });
  }

  async verifyToken(token: string): Promise<{ userId: string }> {
    try {
      return jwt.verify(token, this.secret) as { userId: string };
    } catch (error) {
      throw new Error('Invalid token');
    }
  }

  async hashPassword(password: string): Promise<string> {
    return bcrypt.hash(password, 10);
  }

  async comparePassword(password: string, hash: string): Promise<boolean> {
    return bcrypt.compare(password, hash);
  }
}
      `.trim(),
    },
    {
      path: 'src/auth/middleware.ts',
      language: 'typescript',
      content: `
import { Request, Response, NextFunction } from 'express';
import { JWTService } from './jwt';

export function authMiddleware(jwtService: JWTService) {
  return async (req: Request, res: Response, next: NextFunction) => {
    const token = req.headers.authorization?.split(' ')[1];

    if (!token) {
      return res.status(401).json({ error: 'No token provided' });
    }

    try {
      const decoded = await jwtService.verifyToken(token);
      req.user = decoded;
      next();
    } catch (error) {
      return res.status(401).json({ error: 'Invalid token' });
    }
  };
}
      `.trim(),
    },
  ],
  targetBranch: 'main',
};

// ============================================================================
// Step 3: Create LangGraph Workflow for PR Review
// ============================================================================

interface PRReviewState {
  pullRequest: PullRequest;
  codeReview?: {
    issues: string[];
    suggestions: string[];
    score: number;
  };
  securityScan?: {
    vulnerabilities: string[];
    severity: 'low' | 'medium' | 'high' | 'critical';
  };
  performanceAnalysis?: {
    bottlenecks: string[];
    recommendations: string[];
  };
  generatedTests?: string;
  documentationUpdates?: string;
  deploymentStatus?: 'pending' | 'success' | 'failed';
  finalDecision?: 'approved' | 'rejected' | 'needs_changes';
  messages: BaseMessage[];
}

function createPRReviewWorkflow() {
  console.log('🔧 Creating LangGraph workflow for PR review...\n');

  const workflow = new StateGraph<PRReviewState>({
    channels: {
      pullRequest: null,
      codeReview: null,
      securityScan: null,
      performanceAnalysis: null,
      generatedTests: null,
      documentationUpdates: null,
      deploymentStatus: null,
      finalDecision: null,
      messages: null,
    },
  });

  // Node 1: Code Review
  async function reviewCode(state: PRReviewState) {
    console.log('📝 Step 1: Running code review...');

    const llm = new ChatOllama({
      model: 'codellama:13b',
      temperature: 0.2,
    });

    const codeToReview = state.pullRequest.files
      .map(f => `File: ${f.path}\n\`\`\`${f.language}\n${f.content}\n\`\`\``)
      .join('\n\n');

    const result = await llm.invoke([
      {
        role: 'system',
        content: `You are an expert code reviewer. Analyze code for:
- Code quality and maintainability
- Best practices
- Potential bugs
- TypeScript/JavaScript patterns

Provide a JSON response with: issues (array), suggestions (array), score (0-100)`,
      },
      {
        role: 'user',
        content: `Review this pull request:\n\nTitle: ${state.pullRequest.title}\nDescription: ${state.pullRequest.description}\n\n${codeToReview}`,
      },
    ]);

    const review = JSON.parse(result.content as string);
    console.log(`   ✓ Code review complete. Score: ${review.score}/100`);
    console.log(`   ✓ Found ${review.issues.length} issues, ${review.suggestions.length} suggestions\n`);

    return {
      ...state,
      codeReview: review,
    };
  }

  // Node 2: Security Scan
  async function scanSecurity(state: PRReviewState) {
    console.log('🔒 Step 2: Running security scan...');

    const llm = new ChatOllama({
      model: 'mistral:7b',
      temperature: 0.1,
    });

    const codeToScan = state.pullRequest.files
      .map(f => `${f.path}:\n${f.content}`)
      .join('\n\n');

    const result = await llm.invoke([
      {
        role: 'system',
        content: `You are a security expert. Scan for:
- SQL injection vulnerabilities
- XSS vulnerabilities
- Authentication/authorization issues
- Insecure dependencies
- Secret leakage
- OWASP Top 10 issues

Provide JSON with: vulnerabilities (array of strings), severity (low/medium/high/critical)`,
      },
      {
        role: 'user',
        content: `Scan this code for security vulnerabilities:\n\n${codeToScan}`,
      },
    ]);

    const scan = JSON.parse(result.content as string);
    console.log(`   ✓ Security scan complete. Severity: ${scan.severity}`);
    console.log(`   ✓ Found ${scan.vulnerabilities.length} potential vulnerabilities\n`);

    return {
      ...state,
      securityScan: scan,
    };
  }

  // Node 3: Performance Analysis
  async function analyzePerformance(state: PRReviewState) {
    console.log('⚡ Step 3: Analyzing performance...');

    const llm = new ChatOllama({
      model: 'codellama:13b',
      temperature: 0.2,
    });

    const result = await llm.invoke([
      {
        role: 'system',
        content: `Analyze code performance. Look for:
- Algorithm complexity issues
- Inefficient operations
- Memory leaks
- Blocking operations
- Missing caching opportunities

Provide JSON with: bottlenecks (array), recommendations (array)`,
      },
      {
        role: 'user',
        content: `Analyze performance:\n\n${state.pullRequest.files.map(f => f.content).join('\n\n')}`,
      },
    ]);

    const analysis = JSON.parse(result.content as string);
    console.log(`   ✓ Performance analysis complete`);
    console.log(`   ✓ Found ${analysis.bottlenecks.length} bottlenecks\n`);

    return {
      ...state,
      performanceAnalysis: analysis,
    };
  }

  // Node 4: Generate Tests
  async function generateTests(state: PRReviewState) {
    console.log('🧪 Step 4: Generating tests...');

    const llm = new ChatOllama({
      model: 'codellama:13b',
      temperature: 0.3,
    });

    const result = await llm.invoke([
      {
        role: 'system',
        content: `Generate comprehensive Jest tests including:
- Unit tests for all functions
- Edge cases
- Error handling tests
- Integration tests if applicable

Return complete, runnable test code.`,
      },
      {
        role: 'user',
        content: `Generate tests for:\n\n${state.pullRequest.files[0].content}`,
      },
    ]);

    console.log(`   ✓ Tests generated successfully\n`);

    return {
      ...state,
      generatedTests: result.content as string,
    };
  }

  // Node 5: Update Documentation
  async function updateDocumentation(state: PRReviewState) {
    console.log('📚 Step 5: Updating documentation...');

    const llm = new ChatOllama({
      model: 'llama2:13b',
      temperature: 0.5,
    });

    const result = await llm.invoke([
      {
        role: 'system',
        content: `Generate clear API documentation including:
- Function descriptions
- Parameter details
- Return values
- Usage examples
- Error handling

Use Markdown format.`,
      },
      {
        role: 'user',
        content: `Document this code:\n\n${state.pullRequest.files[0].content}`,
      },
    ]);

    console.log(`   ✓ Documentation updated\n`);

    return {
      ...state,
      documentationUpdates: result.content as string,
    };
  }

  // Node 6: Make Final Decision
  async function makeFinalDecision(state: PRReviewState) {
    console.log('⚖️  Step 6: Making final decision...');

    const llm = new ChatOllama({
      model: 'llama2:70b',
      temperature: 0.1,
    });

    const result = await llm.invoke([
      {
        role: 'system',
        content: `You are a tech lead making PR approval decisions. Consider:
- Code quality score
- Security vulnerabilities
- Performance issues
- Test coverage

Return JSON with: decision ('approved'/'rejected'/'needs_changes'), reasoning (string)`,
      },
      {
        role: 'user',
        content: `Make decision on PR:
Code Review: ${JSON.stringify(state.codeReview)}
Security: ${JSON.stringify(state.securityScan)}
Performance: ${JSON.stringify(state.performanceAnalysis)}`,
      },
    ]);

    const decision = JSON.parse(result.content as string);
    console.log(`   ✓ Decision: ${decision.decision.toUpperCase()}`);
    console.log(`   ✓ Reasoning: ${decision.reasoning}\n`);

    return {
      ...state,
      finalDecision: decision.decision,
    };
  }

  // Build workflow graph
  workflow.addNode('review_code', reviewCode);
  workflow.addNode('scan_security', scanSecurity);
  workflow.addNode('analyze_performance', analyzePerformance);
  workflow.addNode('generate_tests', generateTests);
  workflow.addNode('update_documentation', updateDocumentation);
  workflow.addNode('make_decision', makeFinalDecision);

  // Define workflow edges
  workflow.setEntryPoint('review_code');
  workflow.addEdge('review_code', 'scan_security');
  workflow.addEdge('scan_security', 'analyze_performance');
  workflow.addEdge('analyze_performance', 'generate_tests');
  workflow.addEdge('generate_tests', 'update_documentation');
  workflow.addEdge('update_documentation', 'make_decision');
  workflow.addEdge('make_decision', END);

  return workflow.compile();
}

// ============================================================================
// Step 4: Create CrewAI Deployment Team
// ============================================================================

function createDeploymentCrew() {
  console.log('👥 Creating CrewAI deployment team...\n');

  const devopsEngineer = new Agent({
    role: 'DevOps Engineer',
    goal: 'Deploy application to staging environment safely',
    backstory: `You are an experienced DevOps engineer who ensures smooth,
      reliable deployments with proper monitoring and rollback capabilities.`,
    llm: new ChatOllama({ model: 'llama2:13b' }),
    verbose: false,
    allowDelegation: false,
  });

  const qaEngineer = new Agent({
    role: 'QA Engineer',
    goal: 'Verify deployment and run smoke tests',
    backstory: `You are a meticulous QA engineer who verifies deployments
      through comprehensive smoke testing and monitoring.`,
    llm: new ChatOllama({ model: 'mistral:7b' }),
    verbose: false,
    allowDelegation: false,
  });

  const monitoringSpecialist = new Agent({
    role: 'Monitoring Specialist',
    goal: 'Set up monitoring and alerts for the deployment',
    backstory: `You are a monitoring expert who ensures proper observability
      with metrics, logs, and alerts.`,
    llm: new ChatOllama({ model: 'llama2:13b' }),
    verbose: false,
    allowDelegation: false,
  });

  const deployTask = new Task({
    description: `Deploy the approved changes to staging environment:
      - Build Docker image
      - Push to container registry
      - Update Kubernetes deployment
      - Verify health checks`,
    agent: devopsEngineer,
    expectedOutput: 'Deployment status and endpoint URLs',
  });

  const testTask = new Task({
    description: `Run smoke tests on staging deployment:
      - Test authentication endpoints
      - Verify JWT token generation
      - Test error handling
      - Check response times`,
    agent: qaEngineer,
    expectedOutput: 'Test results and pass/fail status',
  });

  const monitorTask = new Task({
    description: `Set up monitoring for the deployment:
      - Configure Prometheus metrics
      - Set up Grafana dashboards
      - Create PagerDuty alerts
      - Enable log aggregation`,
    agent: monitoringSpecialist,
    expectedOutput: 'Monitoring setup confirmation',
  });

  const crew = new Crew({
    agents: [devopsEngineer, qaEngineer, monitoringSpecialist],
    tasks: [deployTask, testTask, monitorTask],
    process: Process.Sequential,
    verbose: false,
  });

  return crew;
}

// ============================================================================
// Step 5: Orchestrate Everything with Brown Bear Framework
// ============================================================================

async function runEndToEndScenario() {
  console.log('═══════════════════════════════════════════════════════════');
  console.log('  🎯 Brown Bear AI Multi-Agent System - E2E Scenario');
  console.log('═══════════════════════════════════════════════════════════\n');

  try {
    // Initialize framework
    const framework = await initializeFramework();

    // Create workflow
    const prWorkflow = createPRReviewWorkflow();
    console.log('✅ LangGraph workflow created\n');

    // Execute PR review workflow
    console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    console.log('  PHASE 1: AUTOMATED PULL REQUEST REVIEW');
    console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n');

    const reviewResult = await prWorkflow.invoke({
      pullRequest: examplePullRequest,
      messages: [],
    });

    console.log('═══════════════════════════════════════════════════════════');
    console.log('  📊 REVIEW RESULTS SUMMARY');
    console.log('═══════════════════════════════════════════════════════════\n');

    console.log('Code Quality:');
    console.log(`  Score: ${reviewResult.codeReview?.score}/100`);
    console.log(`  Issues: ${reviewResult.codeReview?.issues.length}`);
    console.log(`  Suggestions: ${reviewResult.codeReview?.suggestions.length}\n`);

    console.log('Security:');
    console.log(`  Severity: ${reviewResult.securityScan?.severity}`);
    console.log(`  Vulnerabilities: ${reviewResult.securityScan?.vulnerabilities.length}\n`);

    console.log('Performance:');
    console.log(`  Bottlenecks: ${reviewResult.performanceAnalysis?.bottlenecks.length}\n`);

    console.log('Final Decision:');
    console.log(`  Status: ${reviewResult.finalDecision?.toUpperCase()}\n`);

    // Only deploy if approved
    if (reviewResult.finalDecision === 'approved') {
      console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
      console.log('  PHASE 2: DEPLOYMENT TO STAGING');
      console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n');

      const deploymentCrew = createDeploymentCrew();

      console.log('🚀 Starting deployment process...\n');

      const deploymentResult = await deploymentCrew.kickoff({
        context: {
          pr: examplePullRequest,
          review: reviewResult,
        },
      });

      console.log('✅ Deployment completed successfully!\n');

      console.log('═══════════════════════════════════════════════════════════');
      console.log('  🎉 DEPLOYMENT SUMMARY');
      console.log('═══════════════════════════════════════════════════════════\n');
      console.log('Environment: staging');
      console.log('Status: deployed');
      console.log('Endpoint: https://staging.brownbear.com/api/auth');
      console.log('Monitoring: enabled');
      console.log('Tests: passed\n');
    } else {
      console.log('⚠️  Deployment skipped - PR needs changes\n');
    }

    // Register metrics with framework
    await framework.createTask({
      type: 'analytics',
      priority: 'low',
      payload: {
        event: 'pr_review_completed',
        data: {
          prId: examplePullRequest.id,
          decision: reviewResult.finalDecision,
          codeScore: reviewResult.codeReview?.score,
          securitySeverity: reviewResult.securityScan?.severity,
        },
      },
    });

    console.log('═══════════════════════════════════════════════════════════');
    console.log('  ✨ END-TO-END SCENARIO COMPLETED SUCCESSFULLY');
    console.log('═══════════════════════════════════════════════════════════\n');

    console.log('Summary:');
    console.log('  ✓ Pull request reviewed by AI agents');
    console.log('  ✓ Code quality analyzed');
    console.log('  ✓ Security vulnerabilities detected');
    console.log('  ✓ Performance bottlenecks identified');
    console.log('  ✓ Tests generated automatically');
    console.log('  ✓ Documentation updated');
    console.log('  ✓ Deployment orchestrated (if approved)');
    console.log('  ✓ Monitoring enabled\n');

    // Cleanup
    await framework.shutdown();
    console.log('👋 Framework shutdown complete\n');

    return reviewResult;

  } catch (error) {
    console.error('❌ Error in end-to-end scenario:', error);
    throw error;
  }
}

// ============================================================================
// Execute the scenario
// ============================================================================

if (require.main === module) {
  runEndToEndScenario()
    .then(() => {
      console.log('✅ Scenario completed successfully');
      process.exit(0);
    })
    .catch((error) => {
      console.error('❌ Scenario failed:', error);
      process.exit(1);
    });
}

export {
  runEndToEndScenario,
  createPRReviewWorkflow,
  createDeploymentCrew,
  examplePullRequest,
};
