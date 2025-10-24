/**
 * Type definitions for Brown Bear AI Multi-Agent Framework
 */

import { z } from 'zod';

// ============================================================================
// Agent Types
// ============================================================================

export enum AgentType {
  CODE_REVIEW = 'code_review',
  TESTING = 'testing',
  DOCUMENTATION = 'documentation',
  SECURITY = 'security',
  DEVOPS = 'devops',
  PROJECT_MANAGEMENT = 'project_management',
  ANALYTICS = 'analytics',
  DEPLOYMENT = 'deployment',
  CUSTOM = 'custom',
}

export enum AgentStatus {
  IDLE = 'idle',
  ACTIVE = 'active',
  BUSY = 'busy',
  PAUSED = 'paused',
  ERROR = 'error',
  STOPPED = 'stopped',
}

export interface AgentCapability {
  name: string;
  description: string;
  parameters?: Record<string, unknown>;
}

export interface AgentConfig {
  id?: string;
  name: string;
  type: AgentType;
  capabilities: string[];
  model: string;
  temperature?: number;
  maxTokens?: number;
  systemPrompt?: string;
  tools?: AgentTool[];
  metadata?: Record<string, unknown>;
}

export interface AgentMetrics {
  tasksCompleted: number;
  tasksInProgress: number;
  tasksFailed: number;
  averageResponseTime: number;
  successRate: number;
  uptime: number;
  lastActivity: Date;
}

// ============================================================================
// Task Types
// ============================================================================

export enum TaskType {
  CODE_REVIEW = 'code_review',
  TEST_GENERATION = 'test_generation',
  DOCUMENTATION = 'documentation',
  SECURITY_SCAN = 'security_scan',
  DEPLOYMENT = 'deployment',
  ANALYSIS = 'analysis',
  CUSTOM = 'custom',
}

export enum TaskStatus {
  PENDING = 'pending',
  ASSIGNED = 'assigned',
  IN_PROGRESS = 'in_progress',
  COMPLETED = 'completed',
  FAILED = 'failed',
  CANCELLED = 'cancelled',
}

export enum TaskPriority {
  LOW = 'low',
  MEDIUM = 'medium',
  HIGH = 'high',
  CRITICAL = 'critical',
}

export interface TaskConfig {
  id?: string;
  type: TaskType;
  priority: TaskPriority;
  payload: Record<string, unknown>;
  assignedTo?: string;
  requiredCapabilities?: string[];
  timeout?: number;
  retryPolicy?: RetryPolicy;
  metadata?: Record<string, unknown>;
}

export interface TaskResult {
  success: boolean;
  data?: unknown;
  error?: Error;
  metrics?: {
    executionTime: number;
    tokensUsed?: number;
    cost?: number;
  };
}

export interface RetryPolicy {
  maxAttempts: number;
  backoff: 'linear' | 'exponential';
  initialDelay: number;
  maxDelay: number;
}

// ============================================================================
// Communication Types
// ============================================================================

export enum MessageType {
  TASK_ASSIGNMENT = 'task_assignment',
  TASK_UPDATE = 'task_update',
  TASK_RESULT = 'task_result',
  AGENT_STATUS = 'agent_status',
  COLLABORATION_REQUEST = 'collaboration_request',
  COLLABORATION_RESPONSE = 'collaboration_response',
  SYSTEM_EVENT = 'system_event',
}

export interface Message {
  id: string;
  type: MessageType;
  from: string;
  to?: string;
  payload: unknown;
  timestamp: Date;
  metadata?: Record<string, unknown>;
}

// ============================================================================
// Orchestrator Types
// ============================================================================

export interface OrchestratorConfig {
  maxConcurrentAgents: number;
  maxConcurrentTasks: number;
  taskTimeout: number;
  retryPolicy: RetryPolicy;
  redis: {
    host: string;
    port: number;
    password?: string;
    db?: number;
  };
  messageQueue: {
    type: 'redis' | 'rabbitmq';
    url: string;
  };
  storage: {
    type: 'postgres' | 'mysql';
    host: string;
    port: number;
    database: string;
    username: string;
    password: string;
  };
}

// ============================================================================
// Tool Types
// ============================================================================

export interface AgentTool {
  name: string;
  description: string;
  parameters: z.ZodSchema;
  execute: (params: unknown) => Promise<unknown>;
}

// ============================================================================
// Event Types
// ============================================================================

export enum EventType {
  AGENT_CREATED = 'agent:created',
  AGENT_STARTED = 'agent:started',
  AGENT_STOPPED = 'agent:stopped',
  AGENT_ERROR = 'agent:error',

  TASK_CREATED = 'task:created',
  TASK_ASSIGNED = 'task:assigned',
  TASK_STARTED = 'task:started',
  TASK_COMPLETED = 'task:completed',
  TASK_FAILED = 'task:failed',
  TASK_CANCELLED = 'task:cancelled',

  SYSTEM_STARTUP = 'system:startup',
  SYSTEM_SHUTDOWN = 'system:shutdown',
  SYSTEM_ERROR = 'system:error',
}

export interface Event {
  type: EventType;
  payload: unknown;
  timestamp: Date;
  metadata?: Record<string, unknown>;
}

// ============================================================================
// Framework Types
// ============================================================================

export interface FrameworkConfig {
  apiKey?: string;
  orchestratorUrl: string;
  provider: 'openai' | 'anthropic' | 'ollama';
  model: string;
  redis?: {
    host: string;
    port: number;
    password?: string;
  };
  logging?: {
    level: 'debug' | 'info' | 'warn' | 'error';
    format: 'json' | 'pretty';
  };
}

// ============================================================================
// Integration Types
// ============================================================================

export interface GitIntegration {
  provider: 'github' | 'gitlab' | 'bitbucket';
  token: string;
  webhookSecret?: string;
}

export interface PullRequestEvent {
  repository: string;
  number: number;
  title: string;
  description: string;
  author: string;
  files: FileChange[];
  baseBranch: string;
  headBranch: string;
}

export interface FileChange {
  filename: string;
  status: 'added' | 'modified' | 'deleted' | 'renamed';
  additions: number;
  deletions: number;
  changes: number;
  patch?: string;
}

export interface CodeReviewResult {
  summary: string;
  issues: CodeIssue[];
  suggestions: string[];
  securityConcerns: SecurityConcern[];
  performanceIssues: PerformanceIssue[];
  approved: boolean;
}

export interface CodeIssue {
  severity: 'critical' | 'major' | 'minor' | 'info';
  type: string;
  file: string;
  line: number;
  message: string;
  suggestion?: string;
}

export interface SecurityConcern {
  severity: 'critical' | 'high' | 'medium' | 'low';
  type: string;
  cwe?: string;
  file: string;
  line: number;
  description: string;
  recommendation: string;
}

export interface PerformanceIssue {
  type: string;
  file: string;
  line: number;
  description: string;
  impact: 'high' | 'medium' | 'low';
  recommendation: string;
}

// ============================================================================
// Zod Schemas for Validation
// ============================================================================

export const AgentConfigSchema = z.object({
  id: z.string().optional(),
  name: z.string().min(1),
  type: z.nativeEnum(AgentType),
  capabilities: z.array(z.string()),
  model: z.string(),
  temperature: z.number().min(0).max(2).optional(),
  maxTokens: z.number().positive().optional(),
  systemPrompt: z.string().optional(),
  metadata: z.record(z.unknown()).optional(),
});

export const TaskConfigSchema = z.object({
  id: z.string().optional(),
  type: z.nativeEnum(TaskType),
  priority: z.nativeEnum(TaskPriority),
  payload: z.record(z.unknown()),
  assignedTo: z.string().optional(),
  requiredCapabilities: z.array(z.string()).optional(),
  timeout: z.number().positive().optional(),
  metadata: z.record(z.unknown()).optional(),
});

export const MessageSchema = z.object({
  id: z.string(),
  type: z.nativeEnum(MessageType),
  from: z.string(),
  to: z.string().optional(),
  payload: z.unknown(),
  timestamp: z.date(),
  metadata: z.record(z.unknown()).optional(),
});
