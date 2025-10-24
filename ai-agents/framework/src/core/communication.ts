/**
 * Communication Bus for Inter-Agent Communication
 * Handles message passing between agents using Redis Pub/Sub
 */

import EventEmitter from 'eventemitter3';
import Redis from 'ioredis';
import type { Message } from '../types';
import { CommunicationError } from '../errors';

export interface CommunicationBusConfig {
  redis?: {
    host: string;
    port: number;
    password?: string;
    db?: number;
  };
}

export class CommunicationBus extends EventEmitter {
  private publisher?: Redis;
  private subscriber?: Redis;
  private connected = false;
  private config: CommunicationBusConfig;

  constructor(config: CommunicationBusConfig) {
    super();
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

    try {
      this.publisher = new Redis(redisConfig);
      this.subscriber = new Redis(redisConfig);

      this.subscriber.on('message', (channel, message) => {
        this.handleMessage(channel, message);
      });

      await this.subscriber.subscribe('agents:*', 'tasks:*', 'system:*');

      this.connected = true;
    } catch (error) {
      throw new CommunicationError(`Failed to connect to Redis: ${error}`);
    }
  }

  /**
   * Disconnect from Redis
   */
  async disconnect(): Promise<void> {
    if (!this.connected) {
      return;
    }

    if (this.subscriber) {
      await this.subscriber.quit();
    }

    if (this.publisher) {
      await this.publisher.quit();
    }

    this.connected = false;
  }

  /**
   * Publish a message to a channel
   */
  async publish(message: unknown): Promise<void> {
    if (!this.connected || !this.publisher) {
      throw new CommunicationError('Communication bus not connected');
    }

    const channel = this.getChannelName(message);
    await this.publisher.publish(channel, JSON.stringify(message));
  }

  /**
   * Subscribe to specific channels
   */
  async subscribe(channels: string[]): Promise<void> {
    if (!this.connected || !this.subscriber) {
      throw new CommunicationError('Communication bus not connected');
    }

    await this.subscriber.subscribe(...channels);
  }

  /**
   * Handle incoming messages
   */
  private handleMessage(channel: string, message: string): void {
    try {
      const data = JSON.parse(message);
      this.emit('message', { channel, data });
    } catch (error) {
      this.emit('error', new CommunicationError(`Invalid message: ${error}`));
    }
  }

  /**
   * Get channel name for message routing
   */
  private getChannelName(message: any): string {
    if (message.type?.startsWith('agent:')) {
      return 'agents:events';
    }
    if (message.type?.startsWith('task:')) {
      return 'tasks:events';
    }
    return 'system:events';
  }

  /**
   * Check if connected
   */
  isConnected(): boolean {
    return this.connected;
  }
}
