#!/bin/bash

# AMT CRM Backend - Production Monitoring Script
# Industry standard monitoring for HTTP-only deployment

set -euo pipefail

# Configuration
SRV_DIR="/srv"
LARAVEL_DIR="$SRV_DIR/laravel-backend"
DATABASE_DIR="$SRV_DIR/database"
NGINX_DIR="$SRV_DIR/nginx"
LOG_FILE="$SRV_DIR/monitoring.log"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m'

# Logging functions
log_info() {
    echo -e "${BLUE}[INFO]${NC} $(date '+%Y-%m-%d %H:%M:%S') - $1" | tee -a "$LOG_FILE"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $(date '+%Y-%m-%d %H:%M:%S') - $1" | tee -a "$LOG_FILE"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $(date '+%Y-%m-%d %H:%M:%S') - $1" | tee -a "$LOG_FILE"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $(date '+%Y-%m-%d %H:%M:%S') - $1" | tee -a "$LOG_FILE"
}

log_alert() {
    echo -e "${PURPLE}[ALERT]${NC} $(date '+%Y-%m-%d %H:%M:%S') - $1" | tee -a "$LOG_FILE"
}

# Check container health
check_containers() {
    log_info "Checking container health..."
    
    local all_healthy=true
    
    # Check database container
    if cd "$DATABASE_DIR" && docker-compose ps | grep -q "Up"; then
        log_success "Database container is running"
    else
        log_error "Database container is not running"
        all_healthy=false
    fi
    
    # Check Laravel container
    if cd "$LARAVEL_DIR" && docker-compose ps | grep -q "Up"; then
        log_success "Laravel container is running"
    else
        log_error "Laravel container is not running"
        all_healthy=false
    fi
    
    # Check Nginx container
    if cd "$NGINX_DIR" && docker-compose ps | grep -q "Up"; then
        log_success "Nginx container is running"
    else
        log_error "Nginx container is not running"
        all_healthy=false
    fi
    
    if [[ "$all_healthy" == true ]]; then
        log_success "All containers are healthy"
    else
        log_alert "Container health check failed"
        return 1
    fi
}

# Check application health
check_application_health() {
    log_info "Checking application health..."
    
    local all_healthy=true
    
    # Check health endpoint
    if curl -f -s http://localhost/health > /dev/null; then
        log_success "Health endpoint is responding"
    else
        log_error "Health endpoint is not responding"
        all_healthy=false
    fi
    
    # Check API routes
    if curl -f -s http://localhost/api/routes > /dev/null; then
        log_success "API routes endpoint is responding"
    else
        log_error "API routes endpoint is not responding"
        all_healthy=false
    fi
    
    # Check response time
    local response_time=$(curl -w "%{time_total}" -o /dev/null -s http://localhost/health)
    if (( $(echo "$response_time < 2.0" | bc -l) )); then
        log_success "Response time is good: ${response_time}s"
    else
        log_warning "Response time is slow: ${response_time}s"
    fi
    
    if [[ "$all_healthy" == true ]]; then
        log_success "Application health check passed"
    else
        log_alert "Application health check failed"
        return 1
    fi
}

# Check system resources
check_system_resources() {
    log_info "Checking system resources..."
    
    # Check disk usage
    local disk_usage=$(df -h / | awk 'NR==2 {print $5}' | sed 's/%//')
    if [[ $disk_usage -lt 80 ]]; then
        log_success "Disk usage is acceptable: ${disk_usage}%"
    else
        log_warning "Disk usage is high: ${disk_usage}%"
    fi
    
    # Check memory usage
    local memory_usage=$(free | awk 'NR==2{printf "%.2f", $3*100/$2}')
    if (( $(echo "$memory_usage < 80" | bc -l) )); then
        log_success "Memory usage is acceptable: ${memory_usage}%"
    else
        log_warning "Memory usage is high: ${memory_usage}%"
    fi
    
    # Check CPU usage
    local cpu_usage=$(top -bn1 | grep "Cpu(s)" | awk '{print $2}' | sed 's/%us,//')
    if (( $(echo "$cpu_usage < 80" | bc -l) )); then
        log_success "CPU usage is acceptable: ${cpu_usage}%"
    else
        log_warning "CPU usage is high: ${cpu_usage}%"
    fi
    
    # Check Docker disk usage
    local docker_usage=$(docker system df --format "table {{.Type}}\t{{.TotalCount}}\t{{.Size}}\t{{.Reclaimable}}" | grep -E "(Images|Containers|Volumes)" | awk '{sum+=$3} END {print sum}')
    log_info "Docker disk usage: ${docker_usage}"
}

# Check database performance
check_database_performance() {
    log_info "Checking database performance..."
    
    cd "$DATABASE_DIR"
    
    # Check database connectivity
    if docker-compose exec -T db mysqladmin ping -h localhost --silent; then
        log_success "Database is accessible"
    else
        log_error "Database is not accessible"
        return 1
    fi
    
    # Check slow queries (if enabled)
    if docker-compose exec -T db test -f /var/log/mysql/slow.log; then
        local slow_queries=$(docker-compose exec -T db tail -n 10 /var/log/mysql/slow.log | wc -l)
        if [[ $slow_queries -gt 0 ]]; then
            log_warning "Found $slow_queries slow queries in recent logs"
        else
            log_success "No recent slow queries detected"
        fi
    fi
    
    # Check database size
    local db_size=$(docker-compose exec -T db mysql -u root -p"$(grep MYSQL_ROOT_PASSWORD .env | cut -d'=' -f2)" -e "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS 'DB Size in MB' FROM information_schema.tables WHERE table_schema = 'amt_crm_backend';" 2>/dev/null | tail -n 1)
    log_info "Database size: ${db_size}MB"
}

# Check logs for errors
check_logs() {
    log_info "Checking logs for errors..."
    
    local error_count=0
    
    # Check Nginx error logs
    if [[ -f "$NGINX_DIR/logs/error.log" ]]; then
        local nginx_errors=$(tail -n 100 "$NGINX_DIR/logs/error.log" | grep -c "error\|ERROR" || true)
        if [[ $nginx_errors -gt 0 ]]; then
            log_warning "Found $nginx_errors errors in Nginx logs"
            error_count=$((error_count + nginx_errors))
        fi
    fi
    
    # Check Laravel logs
    if cd "$LARAVEL_DIR" && docker-compose exec -T app test -f /var/www/storage/logs/laravel.log; then
        local laravel_errors=$(docker-compose exec -T app tail -n 100 /var/www/storage/logs/laravel.log | grep -c "ERROR\|CRITICAL\|EMERGENCY" || true)
        if [[ $laravel_errors -gt 0 ]]; then
            log_warning "Found $laravel_errors errors in Laravel logs"
            error_count=$((error_count + laravel_errors))
        fi
    fi
    
    # Check database logs
    if cd "$DATABASE_DIR" && docker-compose exec -T db test -f /var/log/mysql/error.log; then
        local db_errors=$(docker-compose exec -T db tail -n 100 /var/log/mysql/error.log | grep -c "ERROR\|CRITICAL" || true)
        if [[ $db_errors -gt 0 ]]; then
            log_warning "Found $db_errors errors in database logs"
            error_count=$((error_count + db_errors))
        fi
    fi
    
    if [[ $error_count -eq 0 ]]; then
        log_success "No recent errors found in logs"
    else
        log_alert "Found $error_count total errors across all logs"
    fi
}

# Check security
check_security() {
    log_info "Checking security status..."
    
    # Check for exposed sensitive files
    if curl -f -s http://localhost/.env > /dev/null 2>&1; then
        log_error "CRITICAL: .env file is exposed!"
        return 1
    fi
    
    if curl -f -s http://localhost/storage/logs/laravel.log > /dev/null 2>&1; then
        log_error "CRITICAL: Laravel logs are exposed!"
        return 1
    fi
    
    # Check container security
    local privileged_containers=$(docker ps --format "table {{.Names}}\t{{.Status}}" | grep -c "Up" || true)
    log_info "Running containers: $privileged_containers"
    
    # Check for root containers
    local root_containers=$(docker ps --format "table {{.Names}}\t{{.Status}}" | grep -c "root" || true)
    if [[ $root_containers -gt 0 ]]; then
        log_warning "Found $root_containers containers running as root"
    else
        log_success "No containers running as root"
    fi
    
    log_success "Security check completed"
}

# Check backups
check_backups() {
    log_info "Checking backup status..."
    
    local backup_dir="$SRV_DIR/backups"
    if [[ -d "$backup_dir" ]]; then
        local backup_count=$(find "$backup_dir" -name "*.sql" -mtime -7 | wc -l)
        if [[ $backup_count -gt 0 ]]; then
            log_success "Found $backup_count recent backups"
        else
            log_warning "No recent backups found"
        fi
    else
        log_warning "Backup directory does not exist"
    fi
}

# Performance monitoring
check_performance() {
    log_info "Checking performance metrics..."
    
    # Check response times for key endpoints
    local endpoints=("/health" "/api/routes" "/api/login")
    
    for endpoint in "${endpoints[@]}"; do
        local response_time=$(curl -w "%{time_total}" -o /dev/null -s "http://localhost$endpoint" || echo "999")
        if (( $(echo "$response_time < 1.0" | bc -l) )); then
            log_success "$endpoint response time: ${response_time}s"
        elif (( $(echo "$response_time < 3.0" | bc -l) )); then
            log_warning "$endpoint response time: ${response_time}s"
        else
            log_error "$endpoint response time: ${response_time}s (too slow)"
        fi
    done
    
    # Check Docker container resource usage
    log_info "Container resource usage:"
    docker stats --no-stream --format "table {{.Container}}\t{{.CPUPerc}}\t{{.MemUsage}}\t{{.MemPerc}}"
}

# Generate monitoring report
generate_report() {
    local report_file="$SRV_DIR/monitoring_report_$(date +%Y%m%d_%H%M%S).txt"
    
    log_info "Generating monitoring report: $report_file"
    
    {
        echo "=== AMT CRM Backend Monitoring Report ==="
        echo "Generated at: $(date)"
        echo "========================================"
        echo ""
        
        echo "=== Container Status ==="
        cd "$DATABASE_DIR" && docker-compose ps
        echo ""
        
        echo "=== System Resources ==="
        df -h /
        echo ""
        free -h
        echo ""
        
        echo "=== Application Health ==="
        curl -s http://localhost/health || echo "Health check failed"
        echo ""
        
        echo "=== Recent Logs ==="
        echo "Nginx access logs (last 10 lines):"
        tail -n 10 "$NGINX_DIR/logs/access.log" 2>/dev/null || echo "No access logs found"
        echo ""
        
        echo "=== Docker Stats ==="
        docker stats --no-stream
        echo ""
        
    } > "$report_file"
    
    log_success "Monitoring report generated: $report_file"
}

# Main monitoring function
main() {
    local action="${1:-all}"
    
    log_info "Starting monitoring check: $action"
    
    case "$action" in
        "containers")
            check_containers
            ;;
        "health")
            check_application_health
            ;;
        "resources")
            check_system_resources
            ;;
        "database")
            check_database_performance
            ;;
        "logs")
            check_logs
            ;;
        "security")
            check_security
            ;;
        "backups")
            check_backups
            ;;
        "performance")
            check_performance
            ;;
        "report")
            generate_report
            ;;
        "all")
            check_containers
            check_application_health
            check_system_resources
            check_database_performance
            check_logs
            check_security
            check_backups
            check_performance
            ;;
        *)
            echo "Usage: $0 {containers|health|resources|database|logs|security|backups|performance|report|all}"
            exit 1
            ;;
    esac
    
    log_success "Monitoring check completed: $action"
}

# Run main function
main "$@" 