@echo off
setlocal enabledelayedexpansion

REM Brown Bear Project - Windows Setup Script
REM This script automates the initial setup of the Brown Bear ALM platform on Windows

echo.
echo ╔══════════════════════════════════════════════════════════════╗
echo ║                                                              ║
echo ║               Brown Bear ALM Platform                       ║
echo ║                  Windows Setup                              ║
echo ║                                                              ║
echo ║  Comprehensive Application Lifecycle Management Platform    ║
echo ║  Integrating Tuleap, GitLab, Jenkins, SonarQube & More     ║
echo ║                                                              ║
echo ╚══════════════════════════════════════════════════════════════╝
echo.

REM Check if running as administrator
net session >nul 2>&1
if %errorLevel% == 0 (
    echo [WARNING] Running as administrator. This is not recommended for security reasons.
    echo Press any key to continue or Ctrl+C to exit...
    pause >nul
)

echo [INFO] Checking system requirements...

REM Check prerequisites
echo [INFO] Checking prerequisites...

REM Check Docker
docker --version >nul 2>&1
if %errorLevel% neq 0 (
    echo [ERROR] Docker is not installed or not in PATH
    echo Please install Docker Desktop from: https://docs.docker.com/desktop/windows/install/
    exit /b 1
) else (
    for /f "tokens=3" %%i in ('docker --version') do set DOCKER_VERSION=%%i
    echo [SUCCESS] Docker !DOCKER_VERSION! detected
)

REM Check Docker Compose
docker-compose --version >nul 2>&1
if %errorLevel% neq 0 (
    echo [ERROR] Docker Compose is not installed or not in PATH
    exit /b 1
) else (
    for /f "tokens=3" %%i in ('docker-compose --version') do set COMPOSE_VERSION=%%i
    echo [SUCCESS] Docker Compose !COMPOSE_VERSION! detected
)

REM Check Node.js
node --version >nul 2>&1
if %errorLevel% neq 0 (
    echo [ERROR] Node.js is not installed or not in PATH
    echo Please install Node.js from: https://nodejs.org/
    exit /b 1
) else (
    for /f %%i in ('node --version') do set NODE_VERSION=%%i
    echo [SUCCESS] Node.js !NODE_VERSION! detected
)

REM Check/Install pnpm
pnpm --version >nul 2>&1
if %errorLevel% neq 0 (
    echo [WARNING] pnpm not found. Installing...
    npm install -g pnpm@8.15.0
    if %errorLevel% neq 0 (
        echo [ERROR] Failed to install pnpm
        exit /b 1
    )
    echo [SUCCESS] pnpm installed
) else (
    for /f %%i in ('pnpm --version') do set PNPM_VERSION=%%i
    echo [SUCCESS] pnpm !PNPM_VERSION! detected
)

REM Check Git
git --version >nul 2>&1
if %errorLevel% neq 0 (
    echo [ERROR] Git is not installed or not in PATH
    echo Please install Git from: https://git-scm.com/downloads
    exit /b 1
) else (
    for /f "tokens=3" %%i in ('git --version') do set GIT_VERSION=%%i
    echo [SUCCESS] Git !GIT_VERSION! detected
)

echo [SUCCESS] All prerequisites are installed!
echo.

REM Setup environment
echo [INFO] Setting up environment...

if not exist .env (
    if exist .env.example (
        copy .env.example .env >nul
        echo [SUCCESS] Environment file created from .env.example
    ) else (
        echo [ERROR] .env.example file not found!
        exit /b 1
    )
) else (
    echo [INFO] Environment file already exists
)

REM Generate SSL certificates
echo [INFO] Generating SSL certificates for local development...

if not exist tools\docker\reverse-proxy\ssl mkdir tools\docker\reverse-proxy\ssl

if not exist tools\docker\reverse-proxy\ssl\brownbear.local.crt (
    openssl req -x509 -nodes -days 365 -newkey rsa:2048 ^
        -keyout tools\docker\reverse-proxy\ssl\brownbear.local.key ^
        -out tools\docker\reverse-proxy\ssl\brownbear.local.crt ^
        -subj "/C=US/ST=Dev/L=Local/O=BrownBear/CN=*.brownbear.local" >nul 2>&1
    if %errorLevel% == 0 (
        echo [SUCCESS] SSL certificates generated
    ) else (
        echo [WARNING] OpenSSL not found or failed. SSL certificates not generated.
    )
) else (
    echo [INFO] SSL certificates already exist
)

REM Setup hosts file entries
echo [INFO] Setting up local DNS entries...
echo [WARNING] This requires administrator privileges

set HOSTS_FILE=%SystemRoot%\System32\drivers\etc\hosts

echo [INFO] Adding entries to hosts file: %HOSTS_FILE%

REM Backup hosts file
copy "%HOSTS_FILE%" "%HOSTS_FILE%.backup" >nul 2>&1

REM Add entries if they don't exist
findstr /C:"brownbear.local" "%HOSTS_FILE%" >nul 2>&1
if %errorLevel% neq 0 (
    echo 127.0.0.1 brownbear.local >> "%HOSTS_FILE%" 2>nul
    echo 127.0.0.1 gitlab.brownbear.local >> "%HOSTS_FILE%" 2>nul
    echo 127.0.0.1 jenkins.brownbear.local >> "%HOSTS_FILE%" 2>nul
    echo 127.0.0.1 sonar.brownbear.local >> "%HOSTS_FILE%" 2>nul
    echo 127.0.0.1 nexus.brownbear.local >> "%HOSTS_FILE%" 2>nul
    echo 127.0.0.1 gerrit.brownbear.local >> "%HOSTS_FILE%" 2>nul
    echo 127.0.0.1 grafana.brownbear.local >> "%HOSTS_FILE%" 2>nul
    echo 127.0.0.1 ldap.brownbear.local >> "%HOSTS_FILE%" 2>nul
    echo 127.0.0.1 mail.brownbear.local >> "%HOSTS_FILE%" 2>nul

    if %errorLevel% == 0 (
        echo [SUCCESS] Hosts file entries added
    ) else (
        echo [ERROR] Failed to add entries to hosts file
        echo Please run this script as administrator or manually add the following to %HOSTS_FILE%:
        echo   127.0.0.1 brownbear.local
        echo   127.0.0.1 gitlab.brownbear.local
        echo   127.0.0.1 jenkins.brownbear.local
        echo   127.0.0.1 sonar.brownbear.local
        echo   127.0.0.1 nexus.brownbear.local
        echo   127.0.0.1 gerrit.brownbear.local
        echo   127.0.0.1 grafana.brownbear.local
        echo   127.0.0.1 ldap.brownbear.local
        echo   127.0.0.1 mail.brownbear.local
    )
) else (
    echo [INFO] Hosts file entries already exist
)

REM Install dependencies
echo [INFO] Installing dependencies...

echo [INFO] Installing JavaScript dependencies...
pnpm install --frozen-lockfile
if %errorLevel% neq 0 (
    echo [ERROR] Failed to install JavaScript dependencies
    exit /b 1
)
echo [SUCCESS] JavaScript dependencies installed

REM Build Docker images
echo [INFO] Building Docker images...
echo [WARNING] This may take several minutes...

docker-compose -f docker-compose-enhanced.yml build --parallel
if %errorLevel% neq 0 (
    echo [ERROR] Failed to build Docker images
    exit /b 1
)
echo [SUCCESS] Docker images built successfully

REM Start services
echo [INFO] Starting Brown Bear services...
echo [WARNING] This may take a few minutes for all services to be ready...

docker-compose -f docker-compose-enhanced.yml up -d
if %errorLevel% neq 0 (
    echo [ERROR] Failed to start services
    exit /b 1
)

echo [INFO] Waiting for services to start...
timeout /t 30 /nobreak >nul

echo [SUCCESS] Brown Bear ALM Platform setup completed!
echo.
echo 🌐 Access URLs:
echo    • Tuleap (Main):     https://brownbear.local
echo    • GitLab (SCM):      https://gitlab.brownbear.local
echo    • Jenkins (CI/CD):   https://jenkins.brownbear.local
echo    • SonarQube:         https://sonar.brownbear.local
echo    • Nexus:             https://nexus.brownbear.local
echo    • Gerrit:            https://gerrit.brownbear.local
echo    • Grafana:           https://grafana.brownbear.local
echo    • LDAP Admin:        https://ldap.brownbear.local
echo    • MailHog:           https://mail.brownbear.local
echo.
echo 📚 Documentation:
echo    • Setup Guide: .\SETUP.md
echo    • Architecture: .\README.md
echo.
echo 🛠️ Useful Commands:
echo    • make stack-status   - Check service status
echo    • make stack-logs     - View service logs
echo    • make health-check   - Run health checks
echo    • make dev-down       - Stop all services
echo    • make help           - Show all available commands
echo.
echo [INFO] It may take a few more minutes for all services to be fully ready.
echo [INFO] Use 'make health-check' to verify service status.

pause
