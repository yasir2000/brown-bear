# Examples

Comprehensive examples demonstrating the AI Multi-Agent System capabilities.

## Basic Examples

### 1. Simple Agent Creation

```typescript
import { AgentFramework } from '@brownbear/ai-agents-framework';

const framework = new AgentFramework({
  apiKey: process.env.OPENAI_API_KEY,
  orchestratorUrl: 'http://localhost:8080',
  provider: 'openai',
  model: 'gpt-4-turbo',
});

await framework.initialize();

const agentId = await framework.registerAgent({
  name: 'my-first-agent',
  type: 'code_review',
  capabilities: ['security', 'performance'],
  model: 'gpt-4-turbo',
});

console.log(`Agent created: ${agentId}`);
```

### 2. Automated Code Review

See [examples/code-review/](../examples/code-review/) for complete implementation.

### 3. Test Generation

See [examples/test-generation/](../examples/test-generation/) for complete implementation.

### 4. Documentation Updates

See [examples/documentation/](../examples/documentation/) for complete implementation.

### 5. Security Scanning

See [examples/security-scan/](../examples/security-scan/) for complete implementation.

### 6. CI/CD Optimization

See [examples/cicd-optimization/](../examples/cicd-optimization/) for complete implementation.

## Advanced Examples

### Multi-Agent Collaboration

```typescript
// Create multiple specialized agents
const codeReviewer = await framework.registerAgent({
  name: 'code-reviewer',
  type: 'code_review',
  capabilities: ['code-quality', 'best-practices'],
  model: 'gpt-4-turbo',
});

const securityAgent = await framework.registerAgent({
  name: 'security-scanner',
  type: 'security',
  capabilities: ['vulnerability-detection', 'compliance'],
  model: 'gpt-4-turbo',
});

const testingAgent = await framework.registerAgent({
  name: 'test-generator',
  type: 'testing',
  capabilities: ['unit-tests', 'integration-tests'],
  model: 'gpt-4-turbo',
});

// Coordinate agents on a pull request
framework.on('git:pull_request:opened', async (pr) => {
  // Parallel execution
  const [codeReview, securityScan, testGen] = await Promise.all([
    framework.createTask({
      type: 'code_review',
      priority: 'high',
      payload: pr,
      assignedTo: codeReviewer,
    }),
    framework.createTask({
      type: 'security_scan',
      priority: 'critical',
      payload: pr,
      assignedTo: securityAgent,
    }),
    framework.createTask({
      type: 'test_generation',
      priority: 'medium',
      payload: pr,
      assignedTo: testingAgent,
    }),
  ]);
  
  // Aggregate results
  const results = await Promise.all([
    framework.getTask(codeReview),
    framework.getTask(securityScan),
    framework.getTask(testGen),
  ]);
  
  // Post comprehensive review
  await pr.postReview(aggregateResults(results));
});
```

## Integration Examples

### GitHub Actions Integration

```yaml
# .github/workflows/ai-review.yml
name: AI Code Review

on:
  pull_request:
    types: [opened, synchronize]

jobs:
  ai-review:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup Node.js
        uses: actions/setup-node@v3
        with:
          node-version: '18'
      
      - name: Install BB AI CLI
        run: npm install -g @brownbear/ai-agents-cli
      
      - name: Run AI Code Review
        env:
          BROWNBEAR_AI_KEY: ${{ secrets.BROWNBEAR_AI_KEY }}
        run: |
          bb-ai tasks create code-review \
            --pr-number ${{ github.event.pull_request.number }} \
            --repository ${{ github.repository }}
```

### GitLab CI Integration

```yaml
# .gitlab-ci.yml
ai-review:
  stage: test
  image: node:18
  script:
    - npm install -g @brownbear/ai-agents-cli
    - bb-ai tasks create code-review --mr-iid $CI_MERGE_REQUEST_IID
  only:
    - merge_requests
```

## See Also

- [SDK Documentation](../sdk/)
- [API Reference](../api/)
- [Integration Guides](../integration/)
