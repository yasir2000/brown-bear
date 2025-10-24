import { createRouter, createWebHistory } from 'vue-router';
import Dashboard from '../views/Dashboard.vue';
import Agents from '../views/Agents.vue';
import Tasks from '../views/Tasks.vue';
import Analytics from '../views/Analytics.vue';

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      name: 'Dashboard',
      component: Dashboard,
    },
    {
      path: '/agents',
      name: 'Agents',
      component: Agents,
    },
    {
      path: '/tasks',
      name: 'Tasks',
      component: Tasks,
    },
    {
      path: '/analytics',
      name: 'Analytics',
      component: Analytics,
    },
  ],
});

export default router;
