#!/bin/bash

#
# Brown Bear Project - Automated Setup Script
# This script automates the initial setup of the Brown Bear ALM platform
#

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging functions
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Banner
show_banner() {
    echo -e "${BLUE}"
    cat << "EOF"
╔══════════════════════════════════════════════════════════════╗
║                                                              ║
║               Brown Bear ALM Platform                       ║
║                  Automated Setup                            ║
║                                                              ║
║  Comprehensive Application Lifecycle Management Platform    ║
║  Integrating Tuleap, GitLab, Jenkins, SonarQube & More     ║
║                                                              ║
╚══════════════════════════════════════════════════════════════╝
EOF
    echo -e "${NC}"
}

# Check if running as root
check_root() {
    if [[ $EUID -eq 0 ]]; then
        log_error "This script should not be run as root for security reasons."
        exit 1
    fi
}

# Check system requirements
check_system_requirements() {
    log_info "Checking system requirements..."

    # Check OS
    if [[ "$OSTYPE" == "linux-gnu"* ]]; then
        log_success "Linux detected"
    elif [[ "$OSTYPE" == "darwin"* ]]; then
        log_success "macOS detected"
    elif [[ "$OSTYPE" == "msys" ]] || [[ "$OSTYPE" == "cygwin" ]]; then
        log_success "Windows detected"
    else
        log_warning "Unknown OS: $OSTYPE"
    fi

    # Check available memory (minimum 8GB recommended)
    if command -v free >/dev/null 2>&1; then
        TOTAL_MEM=$(free -g | awk 'NR==2{print $2}')
        if [ "$TOTAL_MEM" -lt 8 ]; then
            log_warning "Only ${TOTAL_MEM}GB RAM detected. 8GB+ recommended for optimal performance."
        else
            log_success "${TOTAL_MEM}GB RAM detected"
        fi
    fi

    # Check available disk space (minimum 20GB recommended)
    AVAILABLE_SPACE=$(df -BG . | awk 'NR==2{print $4}' | sed 's/G//')
    if [ "$AVAILABLE_SPACE" -lt 20 ]; then
        log_warning "Only ${AVAILABLE_SPACE}GB disk space available. 20GB+ recommended."
    else
        log_success "${AVAILABLE_SPACE}GB disk space available"
    fi
}

# Check prerequisites
check_prerequisites() {
    log_info "Checking prerequisites..."

    local missing_tools=()

    # Check Docker
    if ! command -v docker >/dev/null 2>&1; then
        missing_tools+=("Docker")
    else
        DOCKER_VERSION=$(docker --version | grep -oE '[0-9]+\.[0-9]+\.[0-9]+' | head -1)
        log_success "Docker $DOCKER_VERSION detected"
    fi

    # Check Docker Compose
    if ! command -v docker-compose >/dev/null 2>&1; then
        missing_tools+=("Docker Compose")
    else
        COMPOSE_VERSION=$(docker-compose --version | grep -oE '[0-9]+\.[0-9]+\.[0-9]+' | head -1)
        log_success "Docker Compose $COMPOSE_VERSION detected"
    fi

    # Check Node.js
    if ! command -v node >/dev/null 2>&1; then
        missing_tools+=("Node.js")
    else
        NODE_VERSION=$(node --version)
        log_success "Node.js $NODE_VERSION detected"
    fi

    # Check pnpm
    if ! command -v pnpm >/dev/null 2>&1; then
        log_warning "pnpm not found. Installing..."
        npm install -g pnpm@8.15.0
        log_success "pnpm installed"
    else
        PNPM_VERSION=$(pnpm --version)
        log_success "pnpm $PNPM_VERSION detected"
    fi

    # Check PHP
    if ! command -v php >/dev/null 2>&1; then
        missing_tools+=("PHP 8.0+")
    else
        PHP_VERSION=$(php --version | head -1)
        log_success "$PHP_VERSION detected"
    fi

    # Check Composer
    if ! command -v composer >/dev/null 2>&1; then
        missing_tools+=("Composer")
    else
        COMPOSER_VERSION=$(composer --version 2>/dev/null | head -1)
        log_success "$COMPOSER_VERSION detected"
    fi

    # Check Git
    if ! command -v git >/dev/null 2>&1; then
        missing_tools+=("Git")
    else
        GIT_VERSION=$(git --version)
        log_success "$GIT_VERSION detected"
    fi

    # Check Make
    if ! command -v make >/dev/null 2>&1; then
        missing_tools+=("Make")
    else
        log_success "Make detected"
    fi

    # Report missing tools
    if [ ${#missing_tools[@]} -ne 0 ]; then
        log_error "Missing required tools:"
        for tool in "${missing_tools[@]}"; do
            echo -e "  ${RED}✗${NC} $tool"
        done
        echo ""
        log_info "Please install the missing tools and run this script again."
        echo ""
        log_info "Installation guides:"
        echo "  • Docker: https://docs.docker.com/get-docker/"
        echo "  • Node.js: https://nodejs.org/en/download/"
        echo "  • PHP: https://www.php.net/downloads.php"
        echo "  • Composer: https://getcomposer.org/download/"
        echo "  • Git: https://git-scm.com/downloads"
        exit 1
    fi

    log_success "All prerequisites are installed!"
}

# Setup environment
setup_environment() {
    log_info "Setting up environment..."

    # Create .env file if it doesn't exist
    if [ ! -f .env ]; then
        if [ -f .env.example ]; then
            cp .env.example .env
            log_success "Environment file created from .env.example"
        else
            log_error ".env.example file not found!"
            exit 1
        fi
    else
        log_info "Environment file already exists"
    fi

    # Generate random passwords for security
    log_info "Generating secure passwords..."

    # Generate random password function
    generate_password() {
        openssl rand -base64 12 | tr -d "=+/" | cut -c1-12
    }

    # Update passwords in .env file
    if command -v openssl >/dev/null 2>&1; then
        sed -i.bak "s/tuleap123!/$(generate_password)/g" .env
        log_success "Secure passwords generated"
    else
        log_warning "OpenSSL not found. Using default passwords (change them manually!)"
    fi
}

# Generate SSL certificates
generate_ssl_certificates() {
    log_info "Generating SSL certificates for local development..."

    mkdir -p tools/docker/reverse-proxy/ssl

    if [ ! -f tools/docker/reverse-proxy/ssl/brownbear.local.crt ]; then
        if command -v openssl >/dev/null 2>&1; then
            openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
                -keyout tools/docker/reverse-proxy/ssl/brownbear.local.key \
                -out tools/docker/reverse-proxy/ssl/brownbear.local.crt \
                -subj "/C=US/ST=Dev/L=Local/O=BrownBear/CN=*.brownbear.local" \
                >/dev/null 2>&1
            log_success "SSL certificates generated"
        else
            log_warning "OpenSSL not found. SSL certificates not generated."
        fi
    else
        log_info "SSL certificates already exist"
    fi
}

# Setup hosts file entries (requires sudo)
setup_hosts_file() {
    log_info "Setting up local DNS entries..."

    # Hosts entries needed
    HOSTS_ENTRIES=(
        "127.0.0.1 brownbear.local"
        "127.0.0.1 gitlab.brownbear.local"
        "127.0.0.1 jenkins.brownbear.local"
        "127.0.0.1 sonar.brownbear.local"
        "127.0.0.1 nexus.brownbear.local"
        "127.0.0.1 gerrit.brownbear.local"
        "127.0.0.1 grafana.brownbear.local"
        "127.0.0.1 ldap.brownbear.local"
        "127.0.0.1 mail.brownbear.local"
    )

    # Determine hosts file location
    if [[ "$OSTYPE" == "msys" ]] || [[ "$OSTYPE" == "cygwin" ]]; then
        HOSTS_FILE="/c/Windows/System32/drivers/etc/hosts"
    else
        HOSTS_FILE="/etc/hosts"
    fi

    log_info "Adding entries to hosts file: $HOSTS_FILE"
    log_warning "This may require administrator/sudo privileges"

    for entry in "${HOSTS_ENTRIES[@]}"; do
        if ! grep -q "$entry" "$HOSTS_FILE" 2>/dev/null; then
            echo "$entry" | sudo tee -a "$HOSTS_FILE" >/dev/null 2>&1 || {
                log_error "Failed to add $entry to hosts file"
                log_info "Please manually add the following entries to your hosts file:"
                for e in "${HOSTS_ENTRIES[@]}"; do
                    echo "  $e"
                done
                return 1
            }
        fi
    done

    log_success "Hosts file entries added"
}

# Install dependencies
install_dependencies() {
    log_info "Installing dependencies..."

    # Install JavaScript dependencies
    log_info "Installing JavaScript dependencies..."
    pnpm install --frozen-lockfile
    log_success "JavaScript dependencies installed"

    # Install PHP dependencies
    log_info "Installing PHP dependencies..."
    make composer
    log_success "PHP dependencies installed"
}

# Build Docker images
build_docker_images() {
    log_info "Building Docker images..."
    log_warning "This may take several minutes..."

    docker-compose -f docker-compose-enhanced.yml build --parallel
    log_success "Docker images built successfully"
}

# Start services
start_services() {
    log_info "Starting Brown Bear services..."
    log_warning "This may take a few minutes for all services to be ready..."

    docker-compose -f docker-compose-enhanced.yml up -d

    # Wait for services to be ready
    log_info "Waiting for services to start..."
    sleep 30

    # Check if main service is responding
    local retries=0
    local max_retries=12 # 2 minutes

    while [ $retries -lt $max_retries ]; do
        if curl -fs http://localhost/ >/dev/null 2>&1; then
            break
        fi
        log_info "Waiting for services to be ready... ($((retries + 1))/$max_retries)"
        sleep 10
        ((retries++))
    done

    if [ $retries -eq $max_retries ]; then
        log_warning "Services may still be starting. Check with 'make stack-status'"
    else
        log_success "Services are ready!"
    fi
}

# Show access information
show_access_info() {
    log_success "Brown Bear ALM Platform setup completed!"
    echo ""
    echo -e "${GREEN}🌐 Access URLs:${NC}"
    echo -e "   • ${BLUE}Tuleap (Main):${NC}     https://brownbear.local"
    echo -e "   • ${BLUE}GitLab (SCM):${NC}      https://gitlab.brownbear.local"
    echo -e "   • ${BLUE}Jenkins (CI/CD):${NC}   https://jenkins.brownbear.local"
    echo -e "   • ${BLUE}SonarQube:${NC}         https://sonar.brownbear.local"
    echo -e "   • ${BLUE}Nexus:${NC}             https://nexus.brownbear.local"
    echo -e "   • ${BLUE}Gerrit:${NC}            https://gerrit.brownbear.local"
    echo -e "   • ${BLUE}Grafana:${NC}           https://grafana.brownbear.local"
    echo -e "   • ${BLUE}LDAP Admin:${NC}        https://ldap.brownbear.local"
    echo -e "   • ${BLUE}MailHog:${NC}           https://mail.brownbear.local"
    echo ""
    echo -e "${GREEN}📚 Documentation:${NC}"
    echo -e "   • Setup Guide: ./SETUP.md"
    echo -e "   • Architecture: ./README.md"
    echo ""
    echo -e "${GREEN}🛠️  Useful Commands:${NC}"
    echo -e "   • ${BLUE}make stack-status${NC}   - Check service status"
    echo -e "   • ${BLUE}make stack-logs${NC}     - View service logs"
    echo -e "   • ${BLUE}make health-check${NC}   - Run health checks"
    echo -e "   • ${BLUE}make dev-down${NC}       - Stop all services"
    echo -e "   • ${BLUE}make help${NC}           - Show all available commands"
    echo ""
    log_info "It may take a few more minutes for all services to be fully ready."
    log_info "Use 'make health-check' to verify service status."
}

# Cleanup function
cleanup() {
    if [ $? -ne 0 ]; then
        log_error "Setup failed! Check the error messages above."
        echo ""
        log_info "You can try running individual setup steps:"
        echo "  • make check-env"
        echo "  • make setup-env"
        echo "  • make docker-build"
        echo "  • make stack-up"
    fi
}

# Main execution
main() {
    trap cleanup EXIT

    show_banner
    check_root
    check_system_requirements
    check_prerequisites
    setup_environment
    generate_ssl_certificates
    setup_hosts_file || log_warning "Hosts file setup failed - you may need to add entries manually"
    install_dependencies
    build_docker_images
    start_services
    show_access_info
}

# Parse command line arguments
case "${1:-}" in
    --help|-h)
        echo "Brown Bear ALM Platform Setup Script"
        echo ""
        echo "Usage: $0 [OPTIONS]"
        echo ""
        echo "Options:"
        echo "  --help, -h     Show this help message"
        echo "  --check-only   Only check prerequisites"
        echo "  --no-hosts     Skip hosts file setup"
        echo ""
        exit 0
        ;;
    --check-only)
        show_banner
        check_system_requirements
        check_prerequisites
        log_success "All checks passed!"
        exit 0
        ;;
    --no-hosts)
        NO_HOSTS=true
        ;;
esac

# Run main function
main "$@"
