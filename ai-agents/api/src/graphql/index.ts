/**
 * GraphQL Schema and Resolvers
 */

import { gql } from 'apollo-server-express';

export const typeDefs = gql`
  type Agent {
    id: ID!
    name: String!
    type: String!
    status: String!
    capabilities: [String!]!
    metrics: AgentMetrics
  }

  type AgentMetrics {
    tasksCompleted: Int!
    tasksInProgress: Int!
    tasksFailed: Int!
    averageResponseTime: Float!
    successRate: Float!
  }

  type Task {
    id: ID!
    type: String!
    status: String!
    priority: String!
    assignedTo: String
    createdAt: String!
  }

  type SystemMetrics {
    agents: AgentStats!
    tasks: TaskStats!
  }

  type AgentStats {
    total: Int!
    idle: Int!
    active: Int!
    busy: Int!
  }

  type TaskStats {
    total: Int!
    pending: Int!
    inProgress: Int!
    completed: Int!
    failed: Int!
  }

  type Query {
    agents: [Agent!]!
    agent(id: ID!): Agent
    tasks: [Task!]!
    task(id: ID!): Task
    systemMetrics: SystemMetrics!
  }

  type Mutation {
    createAgent(config: AgentConfigInput!): Agent!
    deleteAgent(id: ID!): Boolean!
    createTask(config: TaskConfigInput!): Task!
    cancelTask(id: ID!): Boolean!
  }

  input AgentConfigInput {
    name: String!
    type: String!
    capabilities: [String!]!
    model: String!
    temperature: Float
  }

  input TaskConfigInput {
    type: String!
    priority: String!
    payload: String!
  }

  type Subscription {
    agentStatusChanged: Agent!
    taskUpdated: Task!
  }
`;

export const resolvers = {
  Query: {
    agents: async (_: any, __: any, { framework }: any) => {
      return framework.getAgents();
    },
    agent: async (_: any, { id }: any, { framework }: any) => {
      return framework.getAgent(id);
    },
    tasks: async (_: any, __: any, { framework }: any) => {
      return framework.getTasks();
    },
    task: async (_: any, { id }: any, { framework }: any) => {
      return framework.getTask(id);
    },
    systemMetrics: async (_: any, __: any, { framework }: any) => {
      return framework.getMetrics();
    },
  },
  Mutation: {
    createAgent: async (_: any, { config }: any, { framework }: any) => {
      const agentId = await framework.registerAgent(config);
      return framework.getAgent(agentId);
    },
    deleteAgent: async (_: any, { id }: any, { framework }: any) => {
      await framework.unregisterAgent(id);
      return true;
    },
    createTask: async (_: any, { config }: any, { framework }: any) => {
      const taskId = await framework.createTask({
        ...config,
        payload: JSON.parse(config.payload),
      });
      return framework.getTask(taskId);
    },
    cancelTask: async (_: any, { id }: any, { framework }: any) => {
      await framework.cancelTask(id);
      return true;
    },
  },
};
