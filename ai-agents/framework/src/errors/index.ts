/**
 * Custom error classes for the AI Multi-Agent Framework
 */

export class FrameworkError extends Error {
  constructor(message: string, public code?: string) {
    super(message);
    this.name = 'FrameworkError';
    Error.captureStackTrace(this, this.constructor);
  }
}

export class AgentError extends FrameworkError {
  constructor(message: string, public agentId?: string) {
    super(message, 'AGENT_ERROR');
    this.name = 'AgentError';
  }
}

export class TaskError extends FrameworkError {
  constructor(message: string, public taskId?: string) {
    super(message, 'TASK_ERROR');
    this.name = 'TaskError';
  }
}

export class CommunicationError extends FrameworkError {
  constructor(message: string) {
    super(message, 'COMMUNICATION_ERROR');
    this.name = 'CommunicationError';
  }
}

export class ConfigurationError extends FrameworkError {
  constructor(message: string) {
    super(message, 'CONFIGURATION_ERROR');
    this.name = 'ConfigurationError';
  }
}

export class ValidationError extends FrameworkError {
  constructor(message: string, public errors?: unknown) {
    super(message, 'VALIDATION_ERROR');
    this.name = 'ValidationError';
  }
}

export class TimeoutError extends FrameworkError {
  constructor(message: string, public timeout?: number) {
    super(message, 'TIMEOUT_ERROR');
    this.name = 'TimeoutError';
  }
}

export class ResourceExhaustedError extends FrameworkError {
  constructor(message: string, public resource?: string) {
    super(message, 'RESOURCE_EXHAUSTED');
    this.name = 'ResourceExhaustedError';
  }
}
