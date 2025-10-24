/**
 * Brown Bear AI Agents API Server
 * Express + GraphQL + WebSocket server
 */

import express from 'express';
import { ApolloServer } from 'apollo-server-express';
import { WebSocketServer } from 'ws';
import helmet from 'helmet';
import cors from 'cors';
import compression from 'compression';
import rateLimit from 'express-rate-limit';
import swaggerUi from 'swagger-ui-express';
import swaggerJsdoc from 'swagger-jsdoc';
import dotenv from 'dotenv';
import { AgentFramework } from '@brownbear/ai-agents-framework';
import { setupRESTRoutes } from './routes';
import { typeDefs, resolvers } from './graphql';
import { authenticateToken } from './middleware/auth';

dotenv.config();

const PORT = process.env.PORT || 8080;
const HOST = process.env.HOST || '0.0.0.0';

/**
 * Initialize the API server
 */
async function startServer() {
  const app = express();

  // Middleware
  app.use(helmet());
  app.use(cors());
  app.use(compression());
  app.use(express.json());
  app.use(express.urlencoded({ extended: true }));

  // Rate limiting
  const limiter = rateLimit({
    windowMs: 15 * 60 * 1000, // 15 minutes
    max: 100, // Limit each IP to 100 requests per windowMs
    message: 'Too many requests from this IP',
  });
  app.use('/api/', limiter);

  // Initialize AI Agent Framework
  const framework = new AgentFramework({
    apiKey: process.env.OPENAI_API_KEY,
    orchestratorUrl: process.env.ORCHESTRATOR_URL || 'http://localhost:8080',
    provider: (process.env.AI_PROVIDER as any) || 'openai',
    model: process.env.AI_MODEL || 'gpt-4-turbo',
    redis: {
      host: process.env.REDIS_HOST || 'localhost',
      port: parseInt(process.env.REDIS_PORT || '6379'),
      password: process.env.REDIS_PASSWORD,
    },
    logging: {
      level: (process.env.LOG_LEVEL as any) || 'info',
      format: (process.env.LOG_FORMAT as any) || 'pretty',
    },
  });

  await framework.initialize();

  // Store framework in app context
  app.locals.framework = framework;

  // Health check
  app.get('/health', (req, res) => {
    res.json({
      status: 'healthy',
      timestamp: new Date().toISOString(),
      framework: framework.isInitialized(),
    });
  });

  // REST API routes
  setupRESTRoutes(app);

  // GraphQL setup
  const apolloServer = new ApolloServer({
    typeDefs,
    resolvers,
    context: ({ req }) => ({
      framework,
      user: (req as any).user, // From auth middleware
    }),
  });

  await apolloServer.start();
  apolloServer.applyMiddleware({ app, path: '/graphql' });

  // Swagger API documentation
  const swaggerSpec = swaggerJsdoc({
    definition: {
      openapi: '3.0.0',
      info: {
        title: 'Brown Bear AI Agents API',
        version: '1.0.0',
        description: 'REST and GraphQL API for AI Multi-Agent System',
      },
      servers: [{ url: `http://localhost:${PORT}` }],
    },
    apis: ['./src/routes/*.ts'],
  });

  app.use('/docs', swaggerUi.serve, swaggerUi.setup(swaggerSpec));

  // Start HTTP server
  const server = app.listen(PORT, HOST, () => {
    console.log(`🚀 API Server running at http://${HOST}:${PORT}`);
    console.log(`📊 GraphQL endpoint: http://${HOST}:${PORT}/graphql`);
    console.log(`📚 API docs: http://${HOST}:${PORT}/docs`);
  });

  // WebSocket server for real-time updates
  const wss = new WebSocketServer({ server, path: '/ws' });

  wss.on('connection', (ws) => {
    console.log('WebSocket client connected');

    // Subscribe to framework events
    const handlers = {
      'agent:status': (data: any) => ws.send(JSON.stringify({ type: 'agent:status', data })),
      'task:update': (data: any) => ws.send(JSON.stringify({ type: 'task:update', data })),
      'system:event': (data: any) => ws.send(JSON.stringify({ type: 'system:event', data })),
    };

    Object.entries(handlers).forEach(([event, handler]) => {
      framework.on(event, handler);
    });

    ws.on('close', () => {
      console.log('WebSocket client disconnected');
      // Remove event listeners
      Object.entries(handlers).forEach(([event, handler]) => {
        framework.off(event, handler);
      });
    });

    ws.on('error', (error) => {
      console.error('WebSocket error:', error);
    });
  });

  // Graceful shutdown
  process.on('SIGTERM', async () => {
    console.log('SIGTERM received, shutting down gracefully...');
    server.close(() => {
      console.log('HTTP server closed');
    });
    await framework.shutdown();
    wss.close(() => {
      console.log('WebSocket server closed');
    });
    process.exit(0);
  });
}

// Start the server
startServer().catch((error) => {
  console.error('Failed to start server:', error);
  process.exit(1);
});
