# 🌐 Brown Bear AI Agents API

REST and GraphQL API server for the AI Multi-Agent System.

## Features

- **REST API**: Comprehensive RESTful endpoints
- **GraphQL API**: Flexible query language for complex operations
- **WebSocket**: Real-time updates and subscriptions
- **Authentication**: JWT-based auth with API keys
- **Rate Limiting**: Protect against abuse
- **API Documentation**: Auto-generated OpenAPI/Swagger docs

## Quick Start

```bash
# Install dependencies
pnpm install

# Configure environment
cp .env.example .env

# Start the server
pnpm dev

# Production build
pnpm build
pnpm start
```

## API Endpoints

### REST API

- `POST /api/v1/agents/create` - Create agent
- `GET /api/v1/agents` - List agents
- `GET /api/v1/agents/:id` - Get agent
- `PUT /api/v1/agents/:id` - Update agent
- `DELETE /api/v1/agents/:id` - Delete agent
- `POST /api/v1/tasks/create` - Create task
- `GET /api/v1/tasks/:id` - Get task
- `GET /api/v1/metrics` - System metrics

### GraphQL API

Access at `http://localhost:8080/graphql`

### WebSocket

Connect at `ws://localhost:8080/ws`

## Documentation

Interactive API documentation available at `http://localhost:8080/docs`
