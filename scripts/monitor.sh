#!/bin/bash

# AMT CRM Backend - System Monitoring Script
# Usage: ./scripts/monitor.sh [health|performance|logs|backup]

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PROJECT_NAME="AMT CRM Backend"
DOMAIN="api.himmanav.com"

# Function to print colored output
print_status() {
    local status=$1
    local message=$2
    case $status in
        "OK") echo -e "${GREEN}✅ $message${NC}" ;;
        "WARN") echo -e "${YELLOW}⚠️  $message${NC}" ;;
        "ERROR") echo -e "${RED}❌ $message${NC}" ;;
        "INFO") echo -e "${BLUE}ℹ️  $message${NC}" ;;
    esac
}

# Health check function
check_health() {
    echo "🔍 Checking system health..."
    
    # Check if containers are running
    if docker compose ps | grep -q "Up"; then
        print_status "OK" "All containers are running"
    else
        print_status "ERROR" "Some containers are not running"
        docker compose ps
        return 1
    fi
    
    # Check application health endpoint
    if curl -f -s http://localhost/health > /dev/null; then
        print_status "OK" "Application health endpoint responding"
    else
        print_status "ERROR" "Application health endpoint not responding"
    fi
    
    # Check database connection
    if docker compose exec -T db mysqladmin ping -u root -p${DB_ROOT_PASSWORD:-root} > /dev/null 2>&1; then
        print_status "OK" "Database connection healthy"
    else
        print_status "ERROR" "Database connection failed"
    fi
    
    # Check disk space
    DISK_USAGE=$(df -h . | awk 'NR==2 {print $5}' | sed 's/%//')
    if [ "$DISK_USAGE" -lt 80 ]; then
        print_status "OK" "Disk usage: ${DISK_USAGE}%"
    elif [ "$DISK_USAGE" -lt 90 ]; then
        print_status "WARN" "Disk usage: ${DISK_USAGE}%"
    else
        print_status "ERROR" "Disk usage: ${DISK_USAGE}% (Critical)"
    fi
    
    # Check memory usage
    MEMORY_USAGE=$(free | awk 'NR==2{printf "%.0f", $3*100/$2}')
    if [ "$MEMORY_USAGE" -lt 80 ]; then
        print_status "OK" "Memory usage: ${MEMORY_USAGE}%"
    elif [ "$MEMORY_USAGE" -lt 90 ]; then
        print_status "WARN" "Memory usage: ${MEMORY_USAGE}%"
    else
        print_status "ERROR" "Memory usage: ${MEMORY_USAGE}% (Critical)"
    fi
}

# Performance check function
check_performance() {
    echo "⚡ Checking system performance..."
    
    # Check container resource usage
    print_status "INFO" "Container resource usage:"
    docker stats --no-stream --format "table {{.Container}}\t{{.CPUPerc}}\t{{.MemUsage}}\t{{.NetIO}}"
    
    # Check MySQL slow queries
    SLOW_QUERIES=$(docker compose exec -T db mysql -u root -p${DB_ROOT_PASSWORD:-root} -e "SHOW GLOBAL STATUS LIKE 'Slow_queries';" 2>/dev/null | tail -1 | awk '{print $2}')
    print_status "INFO" "MySQL slow queries: $SLOW_QUERIES"
    
    # Check active connections
    ACTIVE_CONNECTIONS=$(docker compose exec -T db mysql -u root -p${DB_ROOT_PASSWORD:-root} -e "SHOW STATUS LIKE 'Threads_connected';" 2>/dev/null | tail -1 | awk '{print $2}')
    print_status "INFO" "Active database connections: $ACTIVE_CONNECTIONS"
    
    # Check response time
    RESPONSE_TIME=$(curl -w "%{time_total}" -o /dev/null -s http://localhost/health)
    print_status "INFO" "API response time: ${RESPONSE_TIME}s"
}

# Logs check function
check_logs() {
    echo "📋 Checking recent logs..."
    
    # Check application logs
    print_status "INFO" "Recent Laravel logs:"
    docker compose logs --tail=20 app | grep -E "(ERROR|CRITICAL|Exception)" || print_status "OK" "No recent errors in application logs"
    
    # Check Nginx logs
    print_status "INFO" "Recent Nginx access logs:"
    docker compose logs --tail=10 nginx-proxy | grep -E "(4[0-9]{2}|5[0-9]{2})" || print_status "OK" "No recent errors in Nginx logs"
    
    # Check MySQL logs
    print_status "INFO" "Recent MySQL logs:"
    docker compose logs --tail=10 db | grep -E "(ERROR|Warning)" || print_status "OK" "No recent errors in MySQL logs"
}

# Backup check function
check_backup() {
    echo "💾 Checking backup directory..."
    
    # Check if backup directory exists
    if [ -d "backups" ]; then
        print_status "OK" "Backup directory exists"
        
        # Check backup count
        BACKUP_COUNT=$(ls backups/*.sql 2>/dev/null | wc -l)
        if [ "$BACKUP_COUNT" -gt 0 ]; then
            print_status "INFO" "Found $BACKUP_COUNT backup file(s)"
            
            # Show latest backup
            LATEST_BACKUP=$(ls -t backups/*.sql 2>/dev/null | head -1)
            if [ -n "$LATEST_BACKUP" ]; then
                BACKUP_SIZE=$(du -h "$LATEST_BACKUP" | cut -f1)
                BACKUP_DATE=$(stat -c %y "$LATEST_BACKUP" | cut -d' ' -f1)
                print_status "INFO" "Latest backup: $LATEST_BACKUP ($BACKUP_DATE, $BACKUP_SIZE)"
            fi
        else
            print_status "INFO" "No backup files found (manual backups only)"
        fi
    else
        print_status "WARN" "Backup directory does not exist"
    fi
}

# Main function
main() {
    echo "🚀 $PROJECT_NAME - System Monitor"
    echo "=================================="
    
    case "${1:-health}" in
        "health")
            check_health
            ;;
        "performance")
            check_performance
            ;;
        "logs")
            check_logs
            ;;
        "backup")
            check_backup
            ;;
        "all")
            check_health
            echo ""
            check_performance
            echo ""
            check_logs
            echo ""
            check_backup
            ;;
        *)
            echo "Usage: $0 [health|performance|logs|backup|all]"
            echo "  health      - Check system health"
            echo "  performance - Check system performance"
            echo "  logs        - Check recent logs"
            echo "  backup      - Check backup status"
            echo "  all         - Run all checks"
            exit 1
            ;;
    esac
    
    echo ""
    print_status "INFO" "Monitor completed at $(date)"
}

# Run main function
main "$@" 