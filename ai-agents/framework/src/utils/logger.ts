/**
 * Logger utility for the AI Multi-Agent Framework
 */

import pino from 'pino';

export interface LoggerConfig {
  level?: 'debug' | 'info' | 'warn' | 'error';
  format?: 'json' | 'pretty';
}

export class Logger {
  private logger: pino.Logger;

  constructor(name: string, config?: LoggerConfig) {
    const level = config?.level || 'info';
    const pretty = config?.format === 'pretty';

    this.logger = pino({
      name,
      level,
      ...(pretty && {
        transport: {
          target: 'pino-pretty',
          options: {
            colorize: true,
            translateTime: 'SYS:standard',
            ignore: 'pid,hostname',
          },
        },
      }),
    });
  }

  debug(message: string, ...args: unknown[]): void {
    this.logger.debug({ args }, message);
  }

  info(message: string, ...args: unknown[]): void {
    this.logger.info({ args }, message);
  }

  warn(message: string, ...args: unknown[]): void {
    this.logger.warn({ args }, message);
  }

  error(message: string, error?: unknown): void {
    this.logger.error({ error }, message);
  }

  child(name: string): Logger {
    const childLogger = new Logger(name);
    childLogger.logger = this.logger.child({ component: name });
    return childLogger;
  }
}
