/**
 * Brown Bear AI Agents SDK
 * Main entry point
 */

import axios, { AxiosInstance } from 'axios';
import EventEmitter from 'eventemitter3';
import WebSocket from 'ws';
import type { AgentConfig, TaskConfig, AgentType } from '@brownbear/ai-agents-framework';

export interface SDKConfig {
  apiKey?: string;
  baseUrl: string;
  wsUrl?: string;
  timeout?: number;
}

export class AgentSDK extends EventEmitter {
  private http: AxiosInstance;
  private ws?: WebSocket;
  private config: SDKConfig;

  constructor(config: SDKConfig) {
    super();
    this.config = config;

    this.http = axios.create({
      baseURL: config.baseUrl,
      timeout: config.timeout || 30000,
      headers: {
        'Content-Type': 'application/json',
        ...(config.apiKey && { Authorization: `Bearer ${config.apiKey}` }),
      },
    });

    // Initialize WebSocket connection for real-time updates
    if (config.wsUrl) {
      this.connectWebSocket();
    }
  }

  /**
   * Create and register a new agent
   */
  async createAgent(agentConfig: AgentConfig): Promise<SDKAgent> {
    const response = await this.http.post('/api/v1/agents/create', agentConfig);
    const agentId = response.data.id;

    return new SDKAgent(agentId, this.http, agentConfig);
  }

  /**
   * Get agent by ID
   */
  async getAgent(agentId: string): Promise<SDKAgent | null> {
    try {
      const response = await this.http.get(`/api/v1/agents/${agentId}`);
      return new SDKAgent(agentId, this.http, response.data.config);
    } catch (error) {
      return null;
    }
  }

  /**
   * List all agents
   */
  async listAgents(filter?: { type?: AgentType }): Promise<SDKAgent[]> {
    const response = await this.http.get('/api/v1/agents', { params: filter });
    return response.data.map(
      (agent: any) => new SDKAgent(agent.id, this.http, agent.config)
    );
  }

  /**
   * Create a task
   */
  async createTask(taskConfig: TaskConfig): Promise<string> {
    const response = await this.http.post('/api/v1/tasks/create', taskConfig);
    return response.data.id;
  }

  /**
   * Get task by ID
   */
  async getTask(taskId: string) {
    const response = await this.http.get(`/api/v1/tasks/${taskId}`);
    return response.data;
  }

  /**
   * Get system metrics
   */
  async getMetrics() {
    const response = await this.http.get('/api/v1/metrics');
    return response.data;
  }

  /**
   * Connect WebSocket for real-time updates
   */
  private connectWebSocket(): void {
    const wsUrl = this.config.wsUrl || this.config.baseUrl.replace('http', 'ws');

    this.ws = new WebSocket(wsUrl);

    this.ws.on('open', () => {
      this.emit('connected');
    });

    this.ws.on('message', (data: string) => {
      const message = JSON.parse(data);
      this.emit(message.type, message.payload);
    });

    this.ws.on('error', (error) => {
      this.emit('error', error);
    });

    this.ws.on('close', () => {
      this.emit('disconnected');
    });
  }

  /**
   * Disconnect from the system
   */
  async disconnect(): Promise<void> {
    if (this.ws) {
      this.ws.close();
    }
  }
}

/**
 * SDK Agent wrapper class
 */
export class SDKAgent extends EventEmitter {
  constructor(
    private id: string,
    private http: AxiosInstance,
    private config: AgentConfig
  ) {
    super();
  }

  /**
   * Activate the agent
   */
  async activate(): Promise<void> {
    await this.http.post(`/api/v1/agents/${this.id}/activate`);
    this.emit('activated');
  }

  /**
   * Pause the agent
   */
  async pause(): Promise<void> {
    await this.http.post(`/api/v1/agents/${this.id}/pause`);
    this.emit('paused');
  }

  /**
   * Delete the agent
   */
  async delete(): Promise<void> {
    await this.http.delete(`/api/v1/agents/${this.id}`);
    this.emit('deleted');
  }

  /**
   * Get agent details
   */
  async getDetails() {
    const response = await this.http.get(`/api/v1/agents/${this.id}`);
    return response.data;
  }

  /**
   * Get agent metrics
   */
  async getMetrics() {
    const response = await this.http.get(`/api/v1/agents/${this.id}/metrics`);
    return response.data;
  }

  getId(): string {
    return this.id;
  }

  getConfig(): AgentConfig {
    return { ...this.config };
  }
}

// Re-export framework types for convenience
export * from '@brownbear/ai-agents-framework';
