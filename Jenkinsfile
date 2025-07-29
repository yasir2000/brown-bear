#!/usr/bin/env groovy

/*
 * Brown Bear ALM Platform - Comprehensive CI/CD Pipeline
 * This pipeline handles build, test, quality analysis, and deployment
 */

pipeline {
    agent {
        label 'docker'
    }

    options {
        buildDiscarder(logRotator(numToKeepStr: '50'))
        timeout(time: 2, unit: 'HOURS')
        timestamps()
        ansiColor('xterm')
        parallelsAlwaysFailFast()
    }

    environment {
        // Project Configuration
        PROJECT_NAME = 'brown-bear'
        COMPOSE_FILE = 'docker-compose-enhanced.yml'

        // Tool Versions
        NODE_VERSION = '18'
        PHP_VERSION = '8.0'
        PNPM_VERSION = '8.15.0'

        // Service URLs
        SONAR_HOST_URL = 'http://sonarqube:9000'
        NEXUS_URL = 'http://nexus:8081'
        GITLAB_URL = 'http://gitlab.brownbear.local'

        // Quality Gates
        COVERAGE_THRESHOLD = '80'
        SONAR_QUALITY_GATE = 'Sonar way'

        // Notification
        SLACK_CHANNEL = '#brownbear-builds'
        EMAIL_RECIPIENTS = 'team@brownbear.local'
    }

    stages {
        stage('🚀 Initialize') {
            steps {
                script {
                    currentBuild.description = "Branch: ${env.BRANCH_NAME} | Build: ${env.BUILD_NUMBER}"
                    echo """
                    ╔══════════════════════════════════════════╗
                    ║          Brown Bear ALM Platform        ║
                    ║              CI/CD Pipeline              ║
                    ╚══════════════════════════════════════════╝

                    Branch: ${env.BRANCH_NAME}
                    Build: ${env.BUILD_NUMBER}
                    Commit: ${env.GIT_COMMIT?.take(8)}
                    """
                }

                // Clean workspace
                cleanWs()

                // Checkout code
                checkout scm

                // Setup environment
                sh '''
                    echo "Setting up build environment..."
                    make check-env || true
                    make setup-env
                '''
            }
        }

        stage('📦 Dependencies') {
            parallel {
                stage('PHP Dependencies') {
                    steps {
                        sh '''
                            echo "Installing PHP dependencies..."
                            make composer
                        '''
                    }
                    post {
                        success {
                            publishHTML([
                                allowMissing: false,
                                alwaysLinkToLastBuild: true,
                                keepAll: true,
                                reportDir: 'vendor',
                                reportFiles: 'composer.lock',
                                reportName: 'Composer Lock Report'
                            ])
                        }
                    }
                }

                stage('JavaScript Dependencies') {
                    steps {
                        sh '''
                            echo "Installing JavaScript dependencies..."
                            make js-deps
                        '''
                    }
                    post {
                        success {
                            publishHTML([
                                allowMissing: false,
                                alwaysLinkToLastBuild: true,
                                keepAll: true,
                                reportDir: '.',
                                reportFiles: 'pnpm-lock.yaml',
                                reportName: 'PNPM Lock Report'
                            ])
                        }
                    }
                }

                stage('Docker Images') {
                    steps {
                        sh '''
                            echo "Building Docker images..."
                            make docker-build
                        '''
                    }
                }
            }
        }

        stage('🔍 Code Quality Analysis') {
            parallel {
                stage('Linting') {
                    steps {
                        sh '''
                            echo "Running linting checks..."
                            make lint
                        '''
                    }
                    post {
                        always {
                            publishHTML([
                                allowMissing: true,
                                alwaysLinkToLastBuild: true,
                                keepAll: true,
                                reportDir: 'reports',
                                reportFiles: 'eslint.html',
                                reportName: 'ESLint Report'
                            ])
                        }
                    }
                }

                stage('Type Checking') {
                    steps {
                        sh '''
                            echo "Running TypeScript type checking..."
                            make typecheck
                        '''
                    }
                }

                stage('Security Check') {
                    steps {
                        sh '''
                            echo "Running security checks..."
                            make security-check
                        '''
                    }
                    post {
                        always {
                            publishHTML([
                                allowMissing: true,
                                alwaysLinkToLastBuild: true,
                                keepAll: true,
                                reportDir: 'reports',
                                reportFiles: 'security-audit.html',
                                reportName: 'Security Audit Report'
                            ])
                        }
                    }
                }
            }
        }

        stage('🏗️ Build') {
            steps {
                sh '''
                    echo "Building application..."
                    make build-all
                '''
            }
            post {
                success {
                    archiveArtifacts artifacts: 'build/**/*', fingerprint: true
                    archiveArtifacts artifacts: 'dist/**/*', fingerprint: true
                }
            }
        }

        stage('🧪 Testing') {
            parallel {
                stage('Unit Tests') {
                    steps {
                        sh '''
                            echo "Running unit tests..."
                            make test-unit
                        '''
                    }
                    post {
                        always {
                            junit 'reports/junit/*.xml'
                            publishHTML([
                                allowMissing: false,
                                alwaysLinkToLastBuild: true,
                                keepAll: true,
                                reportDir: 'reports/coverage',
                                reportFiles: 'index.html',
                                reportName: 'Coverage Report'
                            ])
                        }
                    }
                }

                stage('Integration Tests') {
                    steps {
                        sh '''
                            echo "Running integration tests..."
                            make test-integration
                        '''
                    }
                    post {
                        always {
                            junit 'reports/integration/*.xml'
                        }
                    }
                }

                stage('API Tests') {
                    steps {
                        sh '''
                            echo "Running API tests..."
                            make test-api
                        '''
                    }
                    post {
                        always {
                            junit 'reports/api/*.xml'
                        }
                    }
                }
            }
        }

        stage('📊 SonarQube Analysis') {
            steps {
                withSonarQubeEnv('SonarQube') {
                    sh '''
                        sonar-scanner \
                            -Dsonar.projectKey=brown-bear \
                            -Dsonar.projectName="Brown Bear ALM Platform" \
                            -Dsonar.projectVersion=${BUILD_NUMBER} \
                            -Dsonar.sources=src,plugins \
                            -Dsonar.tests=tests \
                            -Dsonar.php.coverage.reportPaths=reports/coverage/clover.xml \
                            -Dsonar.javascript.lcov.reportPaths=reports/coverage/lcov.info \
                            -Dsonar.exclusions="**/vendor/**,**/node_modules/**,**/build/**,**/dist/**" \
                            -Dsonar.coverage.exclusions="**/tests/**,**/vendor/**,**/node_modules/**"
                    '''
                }
            }
        }

        stage('🚦 Quality Gate') {
            steps {
                timeout(time: 10, unit: 'MINUTES') {
                    waitForQualityGate abortPipeline: true
                }
            }
        }

        stage('🎭 E2E Tests') {
            when {
                anyOf {
                    branch 'main'
                    branch 'develop'
                    changeRequest()
                }
            }
            steps {
                sh '''
                    echo "Running E2E tests..."
                    make test-e2e
                '''
            }
            post {
                always {
                    publishHTML([
                        allowMissing: true,
                        alwaysLinkToLastBuild: true,
                        keepAll: true,
                        reportDir: 'reports/e2e',
                        reportFiles: 'index.html',
                        reportName: 'E2E Test Report'
                    ])
                    archiveArtifacts artifacts: 'reports/e2e/screenshots/**/*', fingerprint: true, allowEmptyArchive: true
                    archiveArtifacts artifacts: 'reports/e2e/videos/**/*', fingerprint: true, allowEmptyArchive: true
                }
            }
        }

        stage('⚡ Performance Tests') {
            when {
                anyOf {
                    branch 'main'
                    branch 'develop'
                }
            }
            steps {
                sh '''
                    echo "Running performance tests..."
                    make performance-test
                '''
            }
            post {
                always {
                    publishHTML([
                        allowMissing: true,
                        alwaysLinkToLastBuild: true,
                        keepAll: true,
                        reportDir: 'reports/performance',
                        reportFiles: 'index.html',
                        reportName: 'Performance Test Report'
                    ])
                }
            }
        }

        stage('📦 Package & Publish') {
            when {
                anyOf {
                    branch 'main'
                    branch 'release/*'
                    tag 'v*'
                }
            }
            parallel {
                stage('Docker Images') {
                    steps {
                        script {
                            def imageTag = env.BRANCH_NAME == 'main' ? 'latest' : env.BRANCH_NAME
                            sh """
                                echo "Building and pushing Docker images..."
                                docker tag brownbear/web:latest nexus.brownbear.local:8082/brownbear/web:${imageTag}
                                docker push nexus.brownbear.local:8082/brownbear/web:${imageTag}
                            """
                        }
                    }
                }

                stage('NPM Packages') {
                    steps {
                        sh '''
                            echo "Publishing NPM packages..."
                            pnpm publish --registry http://nexus.brownbear.local:8081/repository/npm-hosted/
                        '''
                    }
                }

                stage('Composer Packages') {
                    steps {
                        sh '''
                            echo "Publishing Composer packages..."
                            # Add Composer package publishing logic here
                        '''
                    }
                }
            }
        }

        stage('🚀 Deploy') {
            when {
                branch 'main'
            }
            stages {
                stage('Deploy to Staging') {
                    steps {
                        sh '''
                            echo "Deploying to staging environment..."
                            # Add staging deployment logic here
                        '''
                    }
                }

                stage('Smoke Tests') {
                    steps {
                        sh '''
                            echo "Running smoke tests on staging..."
                            # Add smoke test logic here
                        '''
                    }
                }

                stage('Deploy to Production') {
                    input {
                        message "Deploy to production?"
                        ok "Deploy"
                        submitterParameter "DEPLOYER"
                        parameters {
                            choice(name: 'ENVIRONMENT', choices: ['production', 'production-blue', 'production-green'], description: 'Target environment')
                        }
                    }
                    steps {
                        sh '''
                            echo "Deploying to production environment: ${ENVIRONMENT}"
                            echo "Deployed by: ${DEPLOYER}"
                            # Add production deployment logic here
                        '''
                    }
                }
            }
        }
    }

    post {
        always {
            script {
                // Calculate build duration
                def duration = currentBuild.durationString.replace(' and counting', '')

                // Cleanup
                sh '''
                    echo "Cleaning up workspace..."
                    make stack-down || true
                    docker system prune -f || true
                '''
            }
        }

        success {
            script {
                if (env.BRANCH_NAME == 'main') {
                    slackSend(
                        channel: env.SLACK_CHANNEL,
                        color: 'good',
                        message: """✅ Brown Bear Build Successful!
                        Branch: ${env.BRANCH_NAME}
                        Build: ${env.BUILD_NUMBER}
                        Duration: ${currentBuild.durationString}
                        Commit: ${env.GIT_COMMIT?.take(8)}
                        """
                    )
                }
            }

            emailext(
                subject: "✅ Brown Bear Build #${env.BUILD_NUMBER} - SUCCESS",
                body: """
                The Brown Bear build has completed successfully!

                Branch: ${env.BRANCH_NAME}
                Build: ${env.BUILD_NUMBER}
                Duration: ${currentBuild.durationString}
                Commit: ${env.GIT_COMMIT}

                View build: ${env.BUILD_URL}
                """,
                to: env.EMAIL_RECIPIENTS
            )
        }

        failure {
            slackSend(
                channel: env.SLACK_CHANNEL,
                color: 'danger',
                message: """❌ Brown Bear Build Failed!
                Branch: ${env.BRANCH_NAME}
                Build: ${env.BUILD_NUMBER}
                Duration: ${currentBuild.durationString}
                View: ${env.BUILD_URL}
                """
            )

            emailext(
                subject: "❌ Brown Bear Build #${env.BUILD_NUMBER} - FAILED",
                body: """
                The Brown Bear build has failed!

                Branch: ${env.BRANCH_NAME}
                Build: ${env.BUILD_NUMBER}
                Duration: ${currentBuild.durationString}

                Please check the build logs: ${env.BUILD_URL}
                """,
                to: env.EMAIL_RECIPIENTS
            )
        }

        unstable {
            slackSend(
                channel: env.SLACK_CHANNEL,
                color: 'warning',
                message: """⚠️ Brown Bear Build Unstable!
                Branch: ${env.BRANCH_NAME}
                Build: ${env.BUILD_NUMBER}
                Some tests failed but build continued.
                View: ${env.BUILD_URL}
                """
            )
        }

        changed {
            script {
                def status = currentBuild.result ?: 'SUCCESS'
                def previousStatus = currentBuild.previousBuild?.result ?: 'UNKNOWN'

                if (status != previousStatus) {
                    slackSend(
                        channel: env.SLACK_CHANNEL,
                        color: status == 'SUCCESS' ? 'good' : 'danger',
                        message: """🔄 Brown Bear Build Status Changed!
                        From: ${previousStatus} → To: ${status}
                        Branch: ${env.BRANCH_NAME}
                        Build: ${env.BUILD_NUMBER}
                        """
                    )
                }
            }
        }
    }
}
