/**
 * State Manager for Agent and Task Persistence
 * Handles state storage and retrieval using Redis
 */

import Redis from 'ioredis';

export interface StateManagerConfig {
  redis?: {
    host: string;
    port: number;
    password?: string;
    db?: number;
  };
}

export class StateManager {
  private redis?: Redis;
  private connected = false;
  private config: StateManagerConfig;

  constructor(config: StateManagerConfig) {
    this.config = config;
  }

  /**
   * Connect to Redis
   */
  async connect(): Promise<void> {
    if (this.connected) {
      return;
    }

    const redisConfig = this.config.redis || {
      host: 'localhost',
      port: 6379,
    };

    this.redis = new Redis(redisConfig);
    this.connected = true;
  }

  /**
   * Disconnect from Redis
   */
  async disconnect(): Promise<void> {
    if (!this.connected || !this.redis) {
      return;
    }

    await this.redis.quit();
    this.connected = false;
  }

  /**
   * Store agent state
   */
  async setAgent(agentId: string, data: unknown): Promise<void> {
    if (!this.redis) throw new Error('Not connected');
    await this.redis.set(`agent:${agentId}`, JSON.stringify(data));
  }

  /**
   * Retrieve agent state
   */
  async getAgent(agentId: string): Promise<unknown | null> {
    if (!this.redis) throw new Error('Not connected');
    const data = await this.redis.get(`agent:${agentId}`);
    return data ? JSON.parse(data) : null;
  }

  /**
   * Delete agent state
   */
  async deleteAgent(agentId: string): Promise<void> {
    if (!this.redis) throw new Error('Not connected');
    await this.redis.del(`agent:${agentId}`);
  }

  /**
   * Store task state
   */
  async setTask(taskId: string, data: unknown): Promise<void> {
    if (!this.redis) throw new Error('Not connected');
    await this.redis.set(`task:${taskId}`, JSON.stringify(data));
  }

  /**
   * Retrieve task state
   */
  async getTask(taskId: string): Promise<unknown | null> {
    if (!this.redis) throw new Error('Not connected');
    const data = await this.redis.get(`task:${taskId}`);
    return data ? JSON.parse(data) : null;
  }

  /**
   * Delete task state
   */
  async deleteTask(taskId: string): Promise<void> {
    if (!this.redis) throw new Error('Not connected');
    await this.redis.del(`task:${taskId}`);
  }

  /**
   * Get all agents
   */
  async getAllAgents(): Promise<unknown[]> {
    if (!this.redis) throw new Error('Not connected');
    const keys = await this.redis.keys('agent:*');
    const agents = [];

    for (const key of keys) {
      const data = await this.redis.get(key);
      if (data) {
        agents.push(JSON.parse(data));
      }
    }

    return agents;
  }

  /**
   * Get all tasks
   */
  async getAllTasks(): Promise<unknown[]> {
    if (!this.redis) throw new Error('Not connected');
    const keys = await this.redis.keys('task:*');
    const tasks = [];

    for (const key of keys) {
      const data = await this.redis.get(key);
      if (data) {
        tasks.push(JSON.parse(data));
      }
    }

    return tasks;
  }

  /**
   * Check if connected
   */
  isConnected(): boolean {
    return this.connected;
  }
}
