#!/bin/bash

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Function to print colored messages
print_message() {
    echo -e "${GREEN}==>${NC} ${YELLOW}$1${NC}"
}

# Function to check if command was successful
check_status() {
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Success${NC}"
    else
        echo -e "${RED}✗ Failed${NC}"
        return 1
    fi
}

# Confirm before proceeding
echo -e "${YELLOW}This will stop all containers and remove all related data. Are you sure? (y/N)${NC}"
read -r response
if [[ ! "$response" =~ ^([yY][eE][sS]|[yY])+$ ]]; then
    echo -e "${YELLOW}Operation cancelled.${NC}"
    exit 0
fi

# Display current containers
print_message "Current containers:"
docker compose ps
echo ""

# Stop and remove containers
print_message "Stopping and removing containers..."
docker compose down
check_status

# Remove volumes
print_message "Removing volumes..."
docker compose down -v
check_status

# Remove generated configuration files
print_message "Removing generated configuration files..."
if [ -f "docker/scheduler.sh" ]; then
    rm -f docker/scheduler.sh
    [ -d "docker" ] && rmdir docker 2>/dev/null
fi
if [ -f "nginx.conf" ]; then
    rm -f nginx.conf
fi
check_status

# Clean up Docker system resources (optional)
echo -e "${YELLOW}Would you like to clean up unused Docker resources? (y/N)${NC}"
read -r cleanup_response
if [[ "$cleanup_response" =~ ^([yY][eE][sS]|[yY])+$ ]]; then
    print_message "Cleaning up Docker system..."
    docker system prune -f
    check_status
fi

# Check if there are any remaining Docker containers
remaining_containers=$(docker ps -a | grep news-aggregator)
if [ ! -z "$remaining_containers" ]; then
    print_message "Remaining containers found. Removing..."
    docker ps -a | grep news-aggregator | awk '{print $1}' | xargs docker rm -f
    check_status
fi

# Check if there are any remaining Docker images
remaining_images=$(docker images | grep news-aggregator)
if [ ! -z "$remaining_images" ]; then
    print_message "Remaining images found. Removing..."
    docker images | grep news-aggregator | awk '{print $3}' | xargs docker rmi -f
    check_status
fi

# Final status
echo -e "\n${GREEN}Teardown completed!${NC}"
echo -e "\n${YELLOW}Summary of actions:${NC}"
echo "✓ Stopped and removed all containers"
echo "✓ Removed Docker volumes"
echo "✓ Removed configuration files"
[ "$cleanup_response" == "y" ] && echo "✓ Cleaned up Docker system"
[ "$vendor_response" == "y" ] && echo "✓ Removed vendor directory"
[ "$env_response" == "y" ] && echo "✓ Removed .env file"
[ "$storage_response" == "y" ] && echo "✓ Cleared storage directory contents"

# Verify clean state
if [ "$(docker ps -a | grep news-aggregator)" ] || [ "$(docker images | grep news-aggregator)" ]; then
    echo -e "\n${RED}Warning: Some Docker resources may still remain. Run 'docker system prune' manually if needed.${NC}"
else
    echo -e "\n${GREEN}Environment is clean!${NC}"
fi
