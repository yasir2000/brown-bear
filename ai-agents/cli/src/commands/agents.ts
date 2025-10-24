/**
 * Agent management commands
 */

import { Command } from 'commander';
import chalk from 'chalk';
import ora from 'ora';
import Table from 'cli-table3';
import { AgentSDK } from '@brownbear/ai-agents-sdk';
import { getConfig } from '../utils/config';

export const agentCommands = new Command();

agentCommands
  .command('list')
  .description('List all agents')
  .action(async () => {
    const spinner = ora('Fetching agents...').start();

    try {
      const config = await getConfig();
      const sdk = new AgentSDK({
        baseUrl: config.orchestrator.url,
        apiKey: config.orchestrator.apiKey,
      });

      const agents = await sdk.listAgents();
      spinner.succeed('Agents fetched');

      if (agents.length === 0) {
        console.log(chalk.yellow('No agents found'));
        return;
      }

      const table = new Table({
        head: ['ID', 'Name', 'Type', 'Status', 'Capabilities'],
        colWidths: [40, 20, 15, 10, 40],
      });

      agents.forEach((agent) => {
        const config = agent.getConfig();
        table.push([
          agent.getId(),
          config.name,
          config.type,
          chalk.green('Active'),
          config.capabilities.join(', '),
        ]);
      });

      console.log(table.toString());
    } catch (error: any) {
      spinner.fail('Failed to fetch agents');
      console.error(chalk.red(error.message));
    }
  });

agentCommands
  .command('create <type>')
  .description('Create a new agent')
  .option('-n, --name <name>', 'Agent name')
  .option('-m, --model <model>', 'AI model to use', 'gpt-4-turbo')
  .option('-c, --capabilities <capabilities>', 'Comma-separated capabilities')
  .action(async (type, options) => {
    const spinner = ora('Creating agent...').start();

    try {
      const config = await getConfig();
      const sdk = new AgentSDK({
        baseUrl: config.orchestrator.url,
        apiKey: config.orchestrator.apiKey,
      });

      const agent = await sdk.createAgent({
        name: options.name || `${type}-agent`,
        type,
        model: options.model,
        capabilities: options.capabilities ? options.capabilities.split(',') : [],
      });

      spinner.succeed(`Agent created: ${agent.getId()}`);
      console.log(chalk.green(`✓ Agent ${options.name} created successfully`));
    } catch (error: any) {
      spinner.fail('Failed to create agent');
      console.error(chalk.red(error.message));
    }
  });

agentCommands
  .command('delete <id>')
  .description('Delete an agent')
  .action(async (id) => {
    const spinner = ora('Deleting agent...').start();

    try {
      const config = await getConfig();
      const sdk = new AgentSDK({
        baseUrl: config.orchestrator.url,
        apiKey: config.orchestrator.apiKey,
      });

      const agent = await sdk.getAgent(id);
      if (agent) {
        await agent.delete();
        spinner.succeed('Agent deleted');
        console.log(chalk.green(`✓ Agent ${id} deleted successfully`));
      } else {
        spinner.fail('Agent not found');
      }
    } catch (error: any) {
      spinner.fail('Failed to delete agent');
      console.error(chalk.red(error.message));
    }
  });

agentCommands
  .command('logs <id>')
  .description('View agent logs')
  .option('-f, --follow', 'Follow log output')
  .action(async (id, options) => {
    console.log(chalk.blue(`📋 Viewing logs for agent ${id}...`));
    console.log(chalk.gray('(Log streaming not yet implemented)'));
  });
