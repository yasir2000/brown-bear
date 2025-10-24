/**
 * REST API Routes
 */

import { Router, Express } from 'express';
import type { AgentFramework } from '@brownbear/ai-agents-framework';

export function setupRESTRoutes(app: Express) {
  const router = Router();

  // Agent routes
  router.post('/agents/create', async (req, res) => {
    try {
      const framework: AgentFramework = req.app.locals.framework;
      const agentId = await framework.registerAgent(req.body);
      res.json({ id: agentId, message: 'Agent created successfully' });
    } catch (error: any) {
      res.status(400).json({ error: error.message });
    }
  });

  router.get('/agents', async (req, res) => {
    try {
      const framework: AgentFramework = req.app.locals.framework;
      const agents = await framework.getAgents();
      res.json(agents);
    } catch (error: any) {
      res.status(500).json({ error: error.message });
    }
  });

  router.get('/agents/:id', async (req, res) => {
    try {
      const framework: AgentFramework = req.app.locals.framework;
      const agent = await framework.getAgent(req.params.id);
      if (!agent) {
        return res.status(404).json({ error: 'Agent not found' });
      }
      res.json(agent);
    } catch (error: any) {
      res.status(500).json({ error: error.message });
    }
  });

  router.delete('/agents/:id', async (req, res) => {
    try {
      const framework: AgentFramework = req.app.locals.framework;
      await framework.unregisterAgent(req.params.id);
      res.json({ message: 'Agent deleted successfully' });
    } catch (error: any) {
      res.status(400).json({ error: error.message });
    }
  });

  // Task routes
  router.post('/tasks/create', async (req, res) => {
    try {
      const framework: AgentFramework = req.app.locals.framework;
      const taskId = await framework.createTask(req.body);
      res.json({ id: taskId, message: 'Task created successfully' });
    } catch (error: any) {
      res.status(400).json({ error: error.message });
    }
  });

  router.get('/tasks/:id', async (req, res) => {
    try {
      const framework: AgentFramework = req.app.locals.framework;
      const task = await framework.getTask(req.params.id);
      if (!task) {
        return res.status(404).json({ error: 'Task not found' });
      }
      res.json(task);
    } catch (error: any) {
      res.status(500).json({ error: error.message });
    }
  });

  router.post('/tasks/:id/cancel', async (req, res) => {
    try {
      const framework: AgentFramework = req.app.locals.framework;
      await framework.cancelTask(req.params.id);
      res.json({ message: 'Task cancelled successfully' });
    } catch (error: any) {
      res.status(400).json({ error: error.message });
    }
  });

  // Metrics route
  router.get('/metrics', async (req, res) => {
    try {
      const framework: AgentFramework = req.app.locals.framework;
      const metrics = await framework.getMetrics();
      res.json(metrics);
    } catch (error: any) {
      res.status(500).json({ error: error.message });
    }
  });

  app.use('/api/v1', router);
}
