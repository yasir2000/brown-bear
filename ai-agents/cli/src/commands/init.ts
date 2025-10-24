/**
 * Initialization command
 */

import inquirer from 'inquirer';
import chalk from 'chalk';
import ora from 'ora';
import { writeConfig } from '../utils/config';

export async function initCommand() {
  console.log(chalk.bold.cyan('🤖 Brown Bear AI Multi-Agent System Setup\n'));

  const answers = await inquirer.prompt([
    {
      type: 'input',
      name: 'orchestratorUrl',
      message: 'Orchestrator URL:',
      default: 'http://localhost:8080',
    },
    {
      type: 'input',
      name: 'apiKey',
      message: 'API Key (optional):',
    },
    {
      type: 'list',
      name: 'aiProvider',
      message: 'AI Provider:',
      choices: ['openai', 'anthropic', 'ollama'],
      default: 'openai',
    },
    {
      type: 'input',
      name: 'aiApiKey',
      message: 'AI Provider API Key:',
      when: (answers) => answers.aiProvider !== 'ollama',
    },
    {
      type: 'input',
      name: 'aiModel',
      message: 'AI Model:',
      default: (answers: any) => {
        switch (answers.aiProvider) {
          case 'openai': return 'gpt-4-turbo';
          case 'anthropic': return 'claude-3-opus';
          case 'ollama': return 'llama2';
        }
      },
    },
    {
      type: 'input',
      name: 'redisHost',
      message: 'Redis Host:',
      default: 'localhost',
    },
    {
      type: 'input',
      name: 'redisPort',
      message: 'Redis Port:',
      default: '6379',
    },
  ]);

  const spinner = ora('Saving configuration...').start();

  const config = {
    orchestrator: {
      url: answers.orchestratorUrl,
      apiKey: answers.apiKey,
    },
    ai: {
      provider: answers.aiProvider,
      apiKey: answers.aiApiKey,
      model: answers.aiModel,
    },
    redis: {
      host: answers.redisHost,
      port: parseInt(answers.redisPort),
    },
  };

  try {
    await writeConfig(config);
    spinner.succeed('Configuration saved');
    console.log(chalk.green('\n✓ Brown Bear AI is configured and ready to use!'));
    console.log(chalk.gray('\nNext steps:'));
    console.log(chalk.gray('  1. Start the orchestrator: bb-ai orchestrator start'));
    console.log(chalk.gray('  2. Create an agent: bb-ai agents create code-review'));
    console.log(chalk.gray('  3. Monitor the system: bb-ai monitor agents'));
  } catch (error: any) {
    spinner.fail('Failed to save configuration');
    console.error(chalk.red(error.message));
  }
}
