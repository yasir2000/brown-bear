# Brown Bear Project - Enhanced Setup Guide

Welcome to the Brown Bear ALM Platform! This guide will help you set up and run the complete integrated development environment.

## 🚀 Quick Start

### Prerequisites

Before you begin, ensure you have the following installed:

- **Docker & Docker Compose** (v20.10+ recommended)
- **Node.js** (v18+ recommended)
- **pnpm** (v8.15.0+): `npm install -g pnpm`
- **PHP** (v8.0+) with Composer
- **Git** (v2.30+)
- **Make** (available on most Unix systems)

### 1. Environment Setup

```bash
# Clone the repository
git clone https://github.com/yasir2000/brown-bear.git
cd brown-bear

# Setup environment
make setup-env

# Edit .env file with your preferences
nano .env
```

### 2. Complete Development Setup

```bash
# This will setup everything you need for development
make dev-setup

# Start the complete stack
make dev-up
```

### 3. Access Your Services

After successful startup, you can access:

- **🏠 Tuleap (Main ALM)**: https://brownbear.local
- **🦊 GitLab (Git SCM)**: https://gitlab.brownbear.local  
- **🔧 Jenkins (CI/CD)**: https://jenkins.brownbear.local
- **📊 SonarQube (Quality)**: https://sonar.brownbear.local
- **📦 Nexus (Registry)**: https://nexus.brownbear.local
- **👁️ Gerrit (Code Review)**: https://gerrit.brownbear.local
- **📈 Grafana (Monitoring)**: https://grafana.brownbear.local
- **👥 LDAP Admin**: https://ldap.brownbear.local
- **📧 MailHog (Dev Mail)**: https://mail.brownbear.local

## 📋 Available Commands

### Environment Management
```bash
make setup-env          # Setup environment configuration
make check-env           # Check prerequisites
make dev-setup           # Complete development setup
make dev-up              # Start development environment
make dev-down            # Stop development environment
make dev-reset           # Reset development environment
```

### Docker Management
```bash
make docker-build        # Build all Docker images
make docker-pull         # Pull latest images
make docker-clean        # Clean Docker resources
make stack-up            # Start the complete stack
make stack-down          # Stop the complete stack
make stack-restart       # Restart the complete stack
make stack-logs          # Show logs from all services
make stack-status        # Show status of all services
```

### Build & Development
```bash
make build-all           # Build all components
make js-deps             # Install JavaScript dependencies
make js-build            # Build JavaScript components
make js-watch            # Watch and rebuild JavaScript
make js-test             # Run JavaScript tests
make composer            # Install PHP dependencies
```

### Code Quality
```bash
make lint                # Run all linting tools
make typecheck           # Run TypeScript type checking
make security-check      # Run security checks
make test-all            # Run all tests
make test-unit           # Run unit tests
make test-integration    # Run integration tests
make test-api            # Run API tests
make test-e2e            # Run end-to-end tests
```

### CI/CD
```bash
make ci-setup            # Setup CI environment
make ci-test             # Run CI test suite
make ci-build            # Build for CI/CD
```

### Monitoring & Health
```bash
make health-check        # Check health of all services
make monitor-logs        # Monitor real-time logs
make performance-test    # Run performance tests
```

### Database Management
```bash
make db-backup           # Backup database
make db-restore BACKUP_FILE=path/to/backup.sql  # Restore database
make db-migrate          # Run database migrations
```

### Integration Setup
```bash
make integration-setup   # Setup all service integrations
make setup-gitlab-integration    # Setup GitLab integration
make setup-jenkins-integration   # Setup Jenkins integration
make setup-sonarqube-integration # Setup SonarQube integration
make setup-nexus-integration     # Setup Nexus integration
```

### Maintenance
```bash
make clean-all           # Clean all build artifacts
make update-deps         # Update all dependencies
make generate-ssl-certs  # Generate SSL certificates
```

## 🏗️ Architecture Overview

### Core Components

1. **Tuleap Core**: Main ALM platform (PHP/Symfony)
2. **GitLab**: Git source control management
3. **Jenkins**: CI/CD automation
4. **SonarQube**: Code quality analysis
5. **Nexus**: Artifact repository
6. **Gerrit**: Code review system
7. **Grafana**: Monitoring dashboards
8. **Prometheus**: Metrics collection
9. **LDAP**: Authentication directory
10. **Redis**: Caching layer
11. **MySQL**: Primary database

### Technology Stack

- **Backend**: PHP 8.0, Symfony Components
- **Frontend**: Vue.js 3, TypeScript, SCSS
- **Build System**: pnpm, Turbo, Webpack, Vite
- **Testing**: PHPUnit, Jest, Cypress
- **Code Quality**: ESLint, Stylelint, Psalm
- **Containerization**: Docker, Docker Compose
- **Orchestration**: Make, Turbo (monorepo)

## 🔧 Configuration

### Environment Variables

Key environment variables in `.env`:

```bash
# Core Application
TULEAP_ENVIRONMENT=development
MYSQL_ROOT_PASSWORD=secure_password
MYSQL_USER=tuleap
MYSQL_PASSWORD=secure_password

# Service Passwords
LDAP_MANAGER_PASSWORD=secure_password
GITLAB_ROOT_PASSWORD=secure_password
JENKINS_ADMIN_PASSWORD=secure_password
NEXUS_ADMIN_PASSWORD=secure_password

# Integration
SONAR_PGUSER=sonar
SONAR_PGPASS=secure_password
```

### Network Configuration

The system uses a custom Docker network (`brownbear`) with fixed IP addresses:

- Reverse Proxy: 172.20.0.2
- Tuleap Web: 172.20.0.10
- Database: 172.20.0.20
- GitLab: 172.20.0.40
- Jenkins: 172.20.0.60
- SonarQube: 172.20.0.70

### SSL Certificates

SSL certificates are automatically generated for local development:
```bash
make generate-ssl-certs
```

## 🧪 Testing Strategy

### Test Levels

1. **Unit Tests**: Test individual components
2. **Integration Tests**: Test component interactions
3. **API Tests**: Test REST/SOAP endpoints
4. **E2E Tests**: Test complete user workflows
5. **Performance Tests**: Test system performance

### Running Tests

```bash
# Run all tests
make test-all

# Run specific test types
make test-unit
make test-integration
make test-api
make test-e2e
```

### Coverage Reports

Test coverage reports are generated in `reports/coverage/`:
- PHP coverage via PHPUnit
- JavaScript coverage via Jest
- Combined coverage in SonarQube

## 🚀 CI/CD Pipeline

The Jenkins pipeline includes:

1. **Initialize**: Environment setup and checkout
2. **Dependencies**: Install PHP/JS dependencies
3. **Code Quality**: Linting, type checking, security
4. **Build**: Compile and bundle assets
5. **Testing**: Unit, integration, API tests
6. **SonarQube**: Static analysis and quality gate
7. **E2E Tests**: End-to-end testing
8. **Performance**: Performance testing
9. **Package**: Docker images and artifacts
10. **Deploy**: Staging and production deployment

### Pipeline Triggers

- **Push to main**: Full pipeline + deployment
- **Pull requests**: Build, test, quality checks
- **Tags**: Release pipeline
- **Scheduled**: Nightly builds and security scans

## 📊 Monitoring & Observability

### Prometheus Metrics

Key metrics collected:
- Application performance
- Database queries
- User activities
- System resources
- Build metrics

### Grafana Dashboards

Pre-configured dashboards for:
- Application overview
- Database performance
- CI/CD metrics
- User activity
- System health

### Log Aggregation

Centralized logging via:
```bash
make monitor-logs  # Real-time log monitoring
make stack-logs    # View all service logs
```

## 🔐 Security

### Authentication

- **LDAP**: Centralized user management
- **OAuth2**: Service-to-service auth
- **JWT**: API authentication
- **SSL/TLS**: Encrypted communications

### Security Practices

- Regular security audits
- Dependency vulnerability scanning
- Container security scanning
- Static code analysis
- Penetration testing

## 🐛 Troubleshooting

### Common Issues

1. **Port Conflicts**: Check if ports 80, 443, 3306 are available
2. **Memory Issues**: Ensure at least 8GB RAM available
3. **Docker Issues**: Restart Docker daemon
4. **Permission Issues**: Check file permissions and Docker access

### Health Checks

```bash
# Check overall system health
make health-check

# Check individual service status
make stack-status

# View service logs
docker-compose -f docker-compose-enhanced.yml logs <service-name>
```

### Reset Environment

If you encounter persistent issues:
```bash
make dev-reset  # Complete environment reset
```

## 📚 Development Workflow

### Daily Development

1. Start your development environment:
   ```bash
   make dev-up
   ```

2. Make your changes and test:
   ```bash
   make js-watch  # Watch for JavaScript changes
   make test-unit # Run unit tests
   ```

3. Run quality checks:
   ```bash
   make lint      # Code linting
   make typecheck # Type checking
   ```

4. Commit your changes and push

### Code Quality Standards

- **PHP**: PSR-12 coding standard
- **JavaScript**: ESLint with recommended rules
- **CSS**: Stylelint with SCSS guidelines
- **TypeScript**: Strict mode enabled
- **Testing**: Minimum 80% coverage required

### Git Workflow

1. Create feature branch from `main`
2. Make changes with descriptive commits
3. Run full test suite before push
4. Create pull request with description
5. Code review and quality checks
6. Merge after approval

## 🚀 Deployment

### Staging Deployment

Automatic deployment to staging on merge to `main`:
```bash
# Manual staging deployment
make deploy-staging
```

### Production Deployment

Production deployments require manual approval in Jenkins pipeline.

### Rollback

In case of issues:
```bash
make rollback ENVIRONMENT=production VERSION=previous
```

## 📞 Support

### Getting Help

1. Check this documentation
2. Review logs: `make monitor-logs`
3. Check service health: `make health-check`
4. Create issue in GitLab
5. Contact the team via Slack

### Useful Resources

- [Tuleap Documentation](https://docs.tuleap.org/)
- [Docker Documentation](https://docs.docker.com/)
- [Jenkins Documentation](https://www.jenkins.io/doc/)
- [GitLab Documentation](https://docs.gitlab.com/)

---

**Happy coding! 🎉**

The Brown Bear team is here to help you build amazing software with our integrated ALM platform.
