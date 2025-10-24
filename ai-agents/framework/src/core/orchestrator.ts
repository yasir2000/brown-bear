/**
 * Agent Orchestrator
 * Manages agent lifecycle, task distribution, and coordination
 */

import EventEmitter from 'eventemitter3';
import { v4 as uuidv4 } from 'uuid';
import type {
  AgentConfig,
  TaskConfig,
  AgentStatus,
  TaskStatus,
  TaskResult,
} from '../types';
import type { CommunicationBus } from './communication';
import type { StateManager } from './state';
import type { Logger } from '../utils/logger';
import { AgentError, TaskError, ResourceExhaustedError } from '../errors';

interface AgentInstance {
  id: string;
  config: AgentConfig;
  status: AgentStatus;
  currentTask?: string;
  metrics: {
    tasksCompleted: number;
    tasksFailed: number;
    averageResponseTime: number;
  };
}

interface TaskInstance {
  id: string;
  config: TaskConfig;
  status: TaskStatus;
  assignedTo?: string;
  result?: TaskResult;
  createdAt: Date;
  startedAt?: Date;
  completedAt?: Date;
}

export class Orchestrator extends EventEmitter {
  private agents: Map<string, AgentInstance> = new Map();
  private tasks: Map<string, TaskInstance> = new Map();
  private taskQueue: string[] = [];
  private communicationBus: CommunicationBus;
  private stateManager: StateManager;
  private logger: Logger;
  private running = false;
  private processingInterval?: NodeJS.Timeout;

  constructor(config: {
    communicationBus: CommunicationBus;
    stateManager: StateManager;
    logger: Logger;
  }) {
    super();
    this.communicationBus = config.communicationBus;
    this.stateManager = config.stateManager;
    this.logger = config.logger.child('Orchestrator');
  }

  /**
   * Start the orchestrator
   */
  async start(): Promise<void> {
    if (this.running) {
      this.logger.warn('Orchestrator already running');
      return;
    }

    this.logger.info('Starting orchestrator...');

    this.running = true;

    // Start task processing loop
    this.processingInterval = setInterval(() => {
      this.processTasks().catch((error) => {
        this.logger.error('Error processing tasks', error);
      });
    }, 1000);

    this.logger.info('Orchestrator started');
  }

  /**
   * Stop the orchestrator
   */
  async stop(): Promise<void> {
    if (!this.running) {
      return;
    }

    this.logger.info('Stopping orchestrator...');

    this.running = false;

    if (this.processingInterval) {
      clearInterval(this.processingInterval);
    }

    // Wait for in-progress tasks to complete
    const inProgressTasks = Array.from(this.tasks.values()).filter(
      (task) => task.status === 'in_progress'
    );

    if (inProgressTasks.length > 0) {
      this.logger.info(
        `Waiting for ${inProgressTasks.length} tasks to complete...`
      );
      // In production, implement graceful shutdown with timeout
    }

    this.logger.info('Orchestrator stopped');
  }

  /**
   * Register a new agent
   */
  async registerAgent(config: AgentConfig): Promise<string> {
    const agentId = config.id || uuidv4();

    const agent: AgentInstance = {
      id: agentId,
      config: { ...config, id: agentId },
      status: 'idle' as AgentStatus,
      metrics: {
        tasksCompleted: 0,
        tasksFailed: 0,
        averageResponseTime: 0,
      },
    };

    this.agents.set(agentId, agent);

    await this.stateManager.setAgent(agentId, agent);

    this.emit('agent:registered', { agentId, config });

    return agentId;
  }

  /**
   * Unregister an agent
   */
  async unregisterAgent(agentId: string): Promise<void> {
    const agent = this.agents.get(agentId);

    if (!agent) {
      throw new AgentError(`Agent not found: ${agentId}`, agentId);
    }

    if (agent.currentTask) {
      throw new AgentError(
        `Cannot unregister agent with active task: ${agentId}`,
        agentId
      );
    }

    this.agents.delete(agentId);
    await this.stateManager.deleteAgent(agentId);

    this.emit('agent:unregistered', { agentId });
  }

  /**
   * Create a new task
   */
  async createTask(config: TaskConfig): Promise<string> {
    const taskId = config.id || uuidv4();

    const task: TaskInstance = {
      id: taskId,
      config: { ...config, id: taskId },
      status: 'pending' as TaskStatus,
      createdAt: new Date(),
    };

    this.tasks.set(taskId, task);
    this.taskQueue.push(taskId);

    await this.stateManager.setTask(taskId, task);

    this.emit('task:created', { taskId, config });

    return taskId;
  }

  /**
   * Cancel a task
   */
  async cancelTask(taskId: string): Promise<void> {
    const task = this.tasks.get(taskId);

    if (!task) {
      throw new TaskError(`Task not found: ${taskId}`, taskId);
    }

    if (task.status === 'completed' || task.status === 'cancelled') {
      throw new TaskError(`Task already ${task.status}: ${taskId}`, taskId);
    }

    task.status = 'cancelled' as TaskStatus;

    // Remove from queue if pending
    const queueIndex = this.taskQueue.indexOf(taskId);
    if (queueIndex !== -1) {
      this.taskQueue.splice(queueIndex, 1);
    }

    // If task is assigned, notify agent
    if (task.assignedTo) {
      await this.communicationBus.publish({
        type: 'task_cancelled',
        taskId,
        agentId: task.assignedTo,
      });

      const agent = this.agents.get(task.assignedTo);
      if (agent && agent.currentTask === taskId) {
        agent.currentTask = undefined;
        agent.status = 'idle' as AgentStatus;
      }
    }

    await this.stateManager.setTask(taskId, task);

    this.emit('task:cancelled', { taskId });
  }

  /**
   * Get task by ID
   */
  async getTask(taskId: string): Promise<TaskInstance | undefined> {
    return this.tasks.get(taskId);
  }

  /**
   * Get all tasks with optional filtering
   */
  async getTasks(filter?: {
    status?: string;
    type?: string;
  }): Promise<TaskInstance[]> {
    let tasks = Array.from(this.tasks.values());

    if (filter?.status) {
      tasks = tasks.filter((task) => task.status === filter.status);
    }

    if (filter?.type) {
      tasks = tasks.filter((task) => task.config.type === filter.type);
    }

    return tasks;
  }

  /**
   * Get agent by ID
   */
  async getAgent(agentId: string): Promise<AgentInstance | undefined> {
    return this.agents.get(agentId);
  }

  /**
   * Get all agents
   */
  async getAgents(): Promise<AgentInstance[]> {
    return Array.from(this.agents.values());
  }

  /**
   * Get system metrics
   */
  async getMetrics() {
    const agents = Array.from(this.agents.values());
    const tasks = Array.from(this.tasks.values());

    return {
      agents: {
        total: agents.length,
        idle: agents.filter((a) => a.status === 'idle').length,
        active: agents.filter((a) => a.status === 'active').length,
        busy: agents.filter((a) => a.status === 'busy').length,
      },
      tasks: {
        total: tasks.length,
        pending: tasks.filter((t) => t.status === 'pending').length,
        inProgress: tasks.filter((t) => t.status === 'in_progress').length,
        completed: tasks.filter((t) => t.status === 'completed').length,
        failed: tasks.filter((t) => t.status === 'failed').length,
      },
      queue: {
        size: this.taskQueue.length,
      },
    };
  }

  /**
   * Process pending tasks and assign to available agents
   */
  private async processTasks(): Promise<void> {
    if (this.taskQueue.length === 0) {
      return;
    }

    const availableAgents = Array.from(this.agents.values()).filter(
      (agent) => agent.status === 'idle'
    );

    if (availableAgents.length === 0) {
      return;
    }

    const taskId = this.taskQueue[0];
    const task = this.tasks.get(taskId);

    if (!task) {
      this.taskQueue.shift();
      return;
    }

    // Find suitable agent
    const agent = this.findSuitableAgent(task, availableAgents);

    if (!agent) {
      // No suitable agent available, task remains in queue
      return;
    }

    // Assign task to agent
    await this.assignTask(task, agent);

    // Remove from queue
    this.taskQueue.shift();
  }

  /**
   * Find a suitable agent for a task
   */
  private findSuitableAgent(
    task: TaskInstance,
    availableAgents: AgentInstance[]
  ): AgentInstance | undefined {
    if (!task.config.requiredCapabilities) {
      // No specific requirements, return first available
      return availableAgents[0];
    }

    // Find agent with required capabilities
    return availableAgents.find((agent) => {
      const capabilities = agent.config.capabilities;
      return task.config.requiredCapabilities!.every((cap) =>
        capabilities.includes(cap)
      );
    });
  }

  /**
   * Assign a task to an agent
   */
  private async assignTask(
    task: TaskInstance,
    agent: AgentInstance
  ): Promise<void> {
    task.status = 'assigned' as TaskStatus;
    task.assignedTo = agent.id;
    task.startedAt = new Date();

    agent.status = 'busy' as AgentStatus;
    agent.currentTask = task.id;

    await this.stateManager.setTask(task.id, task);
    await this.stateManager.setAgent(agent.id, agent);

    // Notify agent via communication bus
    await this.communicationBus.publish({
      type: 'task_assignment',
      taskId: task.id,
      agentId: agent.id,
      task: task.config,
    });

    this.emit('task:assigned', {
      taskId: task.id,
      agentId: agent.id,
    });

    this.logger.info(`Task ${task.id} assigned to agent ${agent.id}`);
  }
}
