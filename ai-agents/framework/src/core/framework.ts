/**
 * Core Agent Framework - Main Entry Point
 * Orchestrates multi-agent system initialization and coordination
 */

import EventEmitter from 'eventemitter3';
import { v4 as uuidv4 } from 'uuid';
import type {
  FrameworkConfig,
  AgentConfig,
  TaskConfig,
  Event,
  EventType,
} from '../types';
import { Orchestrator } from './orchestrator';
import { CommunicationBus } from './communication';
import { StateManager } from './state';
import { ConfigurationError } from '../errors';
import { Logger } from '../utils/logger';

export class AgentFramework extends EventEmitter {
  private orchestrator: Orchestrator;
  private communicationBus: CommunicationBus;
  private stateManager: StateManager;
  private logger: Logger;
  private config: FrameworkConfig;
  private initialized = false;

  constructor(config: FrameworkConfig) {
    super();

    this.validateConfig(config);
    this.config = config;
    this.logger = new Logger('AgentFramework', config.logging);

    this.communicationBus = new CommunicationBus({
      redis: config.redis,
    });

    this.stateManager = new StateManager({
      redis: config.redis,
    });

    this.orchestrator = new Orchestrator({
      communicationBus: this.communicationBus,
      stateManager: this.stateManager,
      logger: this.logger,
    });

    this.setupEventHandlers();
  }

  /**
   * Initialize the framework and all subsystems
   */
  async initialize(): Promise<void> {
    if (this.initialized) {
      this.logger.warn('Framework already initialized');
      return;
    }

    this.logger.info('Initializing Brown Bear AI Multi-Agent Framework...');

    try {
      // Initialize subsystems in order
      await this.communicationBus.connect();
      await this.stateManager.connect();
      await this.orchestrator.start();

      this.initialized = true;

      this.emitSystemEvent('system:startup', {
        timestamp: new Date(),
        version: '1.0.0',
      });

      this.logger.info('Framework initialized successfully');
    } catch (error) {
      this.logger.error('Failed to initialize framework', error);
      throw error;
    }
  }

  /**
   * Register a new agent with the framework
   */
  async registerAgent(config: AgentConfig): Promise<string> {
    this.ensureInitialized();

    const agentId = await this.orchestrator.registerAgent(config);

    this.logger.info(`Agent registered: ${config.name} (${agentId})`);

    this.emitSystemEvent('agent:created', {
      agentId,
      config,
    });

    return agentId;
  }

  /**
   * Unregister an agent from the framework
   */
  async unregisterAgent(agentId: string): Promise<void> {
    this.ensureInitialized();

    await this.orchestrator.unregisterAgent(agentId);

    this.logger.info(`Agent unregistered: ${agentId}`);

    this.emitSystemEvent('agent:removed', { agentId });
  }

  /**
   * Create and submit a task to the orchestrator
   */
  async createTask(config: TaskConfig): Promise<string> {
    this.ensureInitialized();

    const taskId = await this.orchestrator.createTask(config);

    this.logger.info(`Task created: ${taskId}`);

    this.emitSystemEvent('task:created', {
      taskId,
      config,
    });

    return taskId;
  }

  /**
   * Get task status and result
   */
  async getTask(taskId: string) {
    this.ensureInitialized();
    return this.orchestrator.getTask(taskId);
  }

  /**
   * Cancel a running task
   */
  async cancelTask(taskId: string): Promise<void> {
    this.ensureInitialized();

    await this.orchestrator.cancelTask(taskId);

    this.logger.info(`Task cancelled: ${taskId}`);

    this.emitSystemEvent('task:cancelled', { taskId });
  }

  /**
   * Get all registered agents
   */
  async getAgents() {
    this.ensureInitialized();
    return this.orchestrator.getAgents();
  }

  /**
   * Get specific agent by ID
   */
  async getAgent(agentId: string) {
    this.ensureInitialized();
    return this.orchestrator.getAgent(agentId);
  }

  /**
   * Get all tasks (optionally filtered)
   */
  async getTasks(filter?: { status?: string; type?: string }) {
    this.ensureInitialized();
    return this.orchestrator.getTasks(filter);
  }

  /**
   * Get system metrics
   */
  async getMetrics() {
    this.ensureInitialized();
    return this.orchestrator.getMetrics();
  }

  /**
   * Shutdown the framework gracefully
   */
  async shutdown(): Promise<void> {
    if (!this.initialized) {
      return;
    }

    this.logger.info('Shutting down framework...');

    try {
      await this.orchestrator.stop();
      await this.communicationBus.disconnect();
      await this.stateManager.disconnect();

      this.initialized = false;

      this.emitSystemEvent('system:shutdown', {
        timestamp: new Date(),
      });

      this.logger.info('Framework shutdown complete');
    } catch (error) {
      this.logger.error('Error during shutdown', error);
      throw error;
    }
  }

  /**
   * Set up event handlers for internal communication
   */
  private setupEventHandlers(): void {
    // Forward orchestrator events
    this.orchestrator.on('agent:status', (data) => {
      this.emit('agent:status', data);
    });

    this.orchestrator.on('task:update', (data) => {
      this.emit('task:update', data);
    });

    this.orchestrator.on('error', (error) => {
      this.logger.error('Orchestrator error', error);
      this.emit('error', error);
    });

    // Handle communication bus events
    this.communicationBus.on('message', (message) => {
      this.emit('message', message);
    });

    this.communicationBus.on('error', (error) => {
      this.logger.error('Communication error', error);
      this.emit('error', error);
    });
  }

  /**
   * Emit a system event
   */
  private emitSystemEvent(type: string, payload: unknown): void {
    const event: Event = {
      type: type as EventType,
      payload,
      timestamp: new Date(),
    };

    this.emit('system:event', event);
  }

  /**
   * Validate framework configuration
   */
  private validateConfig(config: FrameworkConfig): void {
    if (!config.orchestratorUrl) {
      throw new ConfigurationError('Orchestrator URL is required');
    }

    if (!config.provider || !config.model) {
      throw new ConfigurationError('AI provider and model are required');
    }

    // Validate provider-specific requirements
    if (config.provider === 'openai' || config.provider === 'anthropic') {
      if (!config.apiKey) {
        throw new ConfigurationError(
          `API key is required for ${config.provider}`
        );
      }
    }
  }

  /**
   * Ensure framework is initialized before operations
   */
  private ensureInitialized(): void {
    if (!this.initialized) {
      throw new ConfigurationError(
        'Framework not initialized. Call initialize() first.'
      );
    }
  }

  /**
   * Get framework configuration
   */
  getConfig(): Readonly<FrameworkConfig> {
    return { ...this.config };
  }

  /**
   * Check if framework is initialized
   */
  isInitialized(): boolean {
    return this.initialized;
  }
}
