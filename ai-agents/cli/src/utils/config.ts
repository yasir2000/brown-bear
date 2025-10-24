/**
 * Configuration utilities
 */

import { promises as fs } from 'fs';
import { homedir } from 'os';
import { join } from 'path';
import YAML from 'yaml';

const CONFIG_DIR = join(homedir(), '.brownbear-ai');
const CONFIG_FILE = join(CONFIG_DIR, 'config.yaml');

export interface Config {
  orchestrator: {
    url: string;
    apiKey?: string;
  };
  ai: {
    provider: string;
    apiKey?: string;
    model: string;
  };
  redis: {
    host: string;
    port: number;
  };
}

export async function getConfig(): Promise<Config> {
  try {
    const content = await fs.readFile(CONFIG_FILE, 'utf-8');
    return YAML.parse(content);
  } catch (error) {
    throw new Error('Configuration not found. Run "bb-ai init" first.');
  }
}

export async function writeConfig(config: Config): Promise<void> {
  await fs.mkdir(CONFIG_DIR, { recursive: true });
  const yaml = YAML.stringify(config);
  await fs.writeFile(CONFIG_FILE, yaml, 'utf-8');
}
