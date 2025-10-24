/**
 * Brown Bear AI Multi-Agent Framework
 * Core Framework Entry Point
 *
 * @module @brownbear/ai-agents-framework
 */

export * from './core/framework';
export * from './core/orchestrator';
export * from './core/agent';
export * from './core/task';
export * from './core/communication';
export * from './core/state';

export * from './agents/base-agent';
export * from './agents/code-review-agent';
export * from './agents/testing-agent';
export * from './agents/documentation-agent';
export * from './agents/security-agent';
export * from './agents/devops-agent';
export * from './agents/project-management-agent';
export * from './agents/analytics-agent';
export * from './agents/deployment-agent';

export * from './types';
export * from './config';
export * from './errors';
export * from './utils';

// Version
export const VERSION = '1.0.0';
