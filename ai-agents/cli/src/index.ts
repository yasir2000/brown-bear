#!/usr/bin/env node

/**
 * Brown Bear AI Agents CLI
 * Main entry point
 */

import { Command } from 'commander';
import chalk from 'chalk';
import { orchestratorCommands } from './commands/orchestrator';
import { agentCommands } from './commands/agents';
import { taskCommands } from './commands/tasks';
import { configCommands } from './commands/config';
import { monitorCommands } from './commands/monitor';
import { dashboardCommands } from './commands/dashboard';
import { initCommand } from './commands/init';

const program = new Command();

program
  .name('bb-ai')
  .description('Brown Bear AI Multi-Agent System CLI')
  .version('1.0.0');

// Init command
program
  .command('init')
  .description('Initialize Brown Bear AI configuration')
  .action(initCommand);

// Orchestrator commands
program
  .command('orchestrator')
  .description('Manage the AI orchestrator')
  .addCommand(orchestratorCommands);

// Agent commands
program
  .command('agents')
  .alias('agent')
  .description('Manage AI agents')
  .addCommand(agentCommands);

// Task commands
program
  .command('tasks')
  .alias('task')
  .description('Manage tasks')
  .addCommand(taskCommands);

// Config commands
program
  .command('config')
  .description('Configuration management')
  .addCommand(configCommands);

// Monitor commands
program
  .command('monitor')
  .description('Monitor agents and system')
  .addCommand(monitorCommands);

// Dashboard command
program
  .command('dashboard')
  .description('Open web dashboard')
  .action(dashboardCommands);

// Error handling
program.exitOverride();

try {
  program.parse(process.argv);
} catch (error: any) {
  console.error(chalk.red('Error:'), error.message);
  process.exit(1);
}

// Show help if no command provided
if (!process.argv.slice(2).length) {
  program.outputHelp();
}
