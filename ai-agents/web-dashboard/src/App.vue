<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <div class="flex-shrink-0 flex items-center">
              <h1 class="text-2xl font-bold text-indigo-600">🤖 Brown Bear AI</h1>
            </div>
            <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
              <router-link to="/" class="nav-link">Dashboard</router-link>
              <router-link to="/agents" class="nav-link">Agents</router-link>
              <router-link to="/tasks" class="nav-link">Tasks</router-link>
              <router-link to="/analytics" class="nav-link">Analytics</router-link>
            </div>
          </div>
          <div class="flex items-center">
            <span class="px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
              {{ connectionStatus }}
            </span>
          </div>
        </div>
      </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
      <router-view />
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useAgentStore } from './stores/agents';

const agentStore = useAgentStore();
const connectionStatus = ref('Connected');

onMounted(async () => {
  await agentStore.fetchAgents();
});
</script>

<style scoped>
.nav-link {
  @apply inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors;
  @apply border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300;
}

.nav-link.router-link-active {
  @apply border-indigo-500 text-gray-900;
}
</style>
