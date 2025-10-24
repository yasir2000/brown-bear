<template>
  <div>
    <div class="mb-8 flex justify-between items-center">
      <div>
        <h2 class="text-3xl font-bold text-gray-900">Agents</h2>
        <p class="mt-2 text-sm text-gray-700">Manage your AI agents</p>
      </div>
      <button
        @click="showCreateModal = true"
        class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
      >
        Create Agent
      </button>
    </div>

    <!-- Agents List -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
      <ul role="list" class="divide-y divide-gray-200">
        <li v-for="agent in agentStore.agents" :key="agent.getId()" class="px-6 py-4 hover:bg-gray-50">
          <div class="flex items-center justify-between">
            <div class="flex-1">
              <p class="text-sm font-medium text-indigo-600">{{ agent.getConfig().name }}</p>
              <p class="text-sm text-gray-500">Type: {{ agent.getConfig().type }}</p>
              <p class="text-xs text-gray-400 mt-1">
                Capabilities: {{ agent.getConfig().capabilities.join(', ') }}
              </p>
            </div>
            <div class="flex items-center space-x-4">
              <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                Active
              </span>
              <button
                @click="deleteAgent(agent.getId())"
                class="text-red-600 hover:text-red-800"
              >
                Delete
              </button>
            </div>
          </div>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useAgentStore } from '../stores/agents';

const agentStore = useAgentStore();
const showCreateModal = ref(false);

onMounted(async () => {
  await agentStore.fetchAgents();
});

async function deleteAgent(id: string) {
  if (confirm('Are you sure you want to delete this agent?')) {
    await agentStore.deleteAgent(id);
  }
}
</script>
