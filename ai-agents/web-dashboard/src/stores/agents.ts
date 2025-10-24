/**
 * Pinia Store for Agent Management
 */

import { defineStore } from 'pinia';
import { ref } from 'vue';
import { AgentSDK } from '@brownbear/ai-agents-sdk';

const sdk = new AgentSDK({
  baseUrl: import.meta.env.VITE_API_URL || 'http://localhost:8080',
});

export const useAgentStore = defineStore('agents', () => {
  const agents = ref<any[]>([]);
  const loading = ref(false);
  const error = ref<string | null>(null);

  async function fetchAgents() {
    loading.value = true;
    error.value = null;

    try {
      agents.value = await sdk.listAgents();
    } catch (e: any) {
      error.value = e.message;
    } finally {
      loading.value = false;
    }
  }

  async function createAgent(config: any) {
    loading.value = true;
    error.value = null;

    try {
      const agent = await sdk.createAgent(config);
      agents.value.push(agent);
      return agent;
    } catch (e: any) {
      error.value = e.message;
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function deleteAgent(id: string) {
    loading.value = true;
    error.value = null;

    try {
      const agent = await sdk.getAgent(id);
      if (agent) {
        await agent.delete();
        agents.value = agents.value.filter(a => a.getId() !== id);
      }
    } catch (e: any) {
      error.value = e.message;
      throw e;
    } finally {
      loading.value = false;
    }
  }

  return {
    agents,
    loading,
    error,
    fetchAgents,
    createAgent,
    deleteAgent,
  };
});
