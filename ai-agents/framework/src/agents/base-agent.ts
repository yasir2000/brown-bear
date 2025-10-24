/**
 * Base Agent Class
 * All specialized agents extend from this base class
 */

import EventEmitter from 'eventemitter3';
import { v4 as uuidv4 } from 'uuid';
import type { AgentConfig, AgentStatus, TaskConfig, TaskResult } from '../types';
import { AgentError } from '../errors';

export abstract class BaseAgent extends EventEmitter {
  protected id: string;
  protected config: AgentConfig;
  protected status: AgentStatus = 'idle';
  protected currentTask?: TaskConfig;

  constructor(config: AgentConfig) {
    super();
    this.id = config.id || uuidv4();
    this.config = { ...config, id: this.id };
  }

  /**
   * Activate the agent
   */
  async activate(): Promise<void> {
    if (this.status !== 'idle' && this.status !== 'paused') {
      throw new AgentError(`Agent cannot be activated from ${this.status} state`, this.id);
    }

    await this.onActivate();
    this.status = 'active';
    this.emit('status', { agentId: this.id, status: this.status });
  }

  /**
   * Pause the agent
   */
  async pause(): Promise<void> {
    if (this.status !== 'active') {
      throw new AgentError(`Agent cannot be paused from ${this.status} state`, this.id);
    }

    await this.onPause();
    this.status = 'paused';
    this.emit('status', { agentId: this.id, status: this.status });
  }

  /**
   * Stop the agent
   */
  async stop(): Promise<void> {
    await this.onStop();
    this.status = 'stopped';
    this.emit('status', { agentId: this.id, status: this.status });
  }

  /**
   * Execute a task
   */
  async executeTask(task: TaskConfig): Promise<TaskResult> {
    if (this.status !== 'active') {
      throw new AgentError(`Agent not active: ${this.status}`, this.id);
    }

    this.currentTask = task;
    this.status = 'busy';
    this.emit('status', { agentId: this.id, status: this.status });

    const startTime = Date.now();

    try {
      const result = await this.onExecuteTask(task);

      const executionTime = Date.now() - startTime;

      this.currentTask = undefined;
      this.status = 'active';
      this.emit('status', { agentId: this.id, status: this.status });

      return {
        success: true,
        data: result,
        metrics: {
          executionTime,
        },
      };
    } catch (error) {
      this.currentTask = undefined;
      this.status = 'active';
      this.emit('status', { agentId: this.id, status: this.status });
      this.emit('error', error);

      return {
        success: false,
        error: error as Error,
        metrics: {
          executionTime: Date.now() - startTime,
        },
      };
    }
  }

  /**
   * Get agent ID
   */
  getId(): string {
    return this.id;
  }

  /**
   * Get agent configuration
   */
  getConfig(): AgentConfig {
    return { ...this.config };
  }

  /**
   * Get agent status
   */
  getStatus(): AgentStatus {
    return this.status;
  }

  /**
   * Hook: Called when agent is activated
   */
  protected abstract onActivate(): Promise<void>;

  /**
   * Hook: Called when agent is paused
   */
  protected abstract onPause(): Promise<void>;

  /**
   * Hook: Called when agent is stopped
   */
  protected abstract onStop(): Promise<void>;

  /**
   * Hook: Called to execute a task
   */
  protected abstract onExecuteTask(task: TaskConfig): Promise<unknown>;
}
