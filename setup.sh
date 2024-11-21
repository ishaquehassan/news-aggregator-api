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
        exit 1
    fi
}

# Function to wait for MySQL to be ready
wait_for_mysql() {
    print_message "Waiting for MySQL to be ready..."
    for i in {1..30}; do
        if docker compose exec db mysqladmin ping -h localhost --silent > /dev/null 2>&1; then
            echo -e "${GREEN}✓ MySQL is ready${NC}"
            return 0
        fi
        echo -n "."
        sleep 1
    done
    echo -e "\n${RED}Failed to connect to MySQL${NC}"
    exit 1
}

# Check if docker is running
if ! docker info > /dev/null 2>&1; then
    echo -e "${RED}Error: Docker is not running${NC}"
    exit 1
fi

# Start script execution
print_message "Starting setup..."

# Stop any running containers and remove volumes
print_message "Cleaning up existing containers and volumes..."
docker compose down -v > /dev/null 2>&1

# Create scheduler script directory and file
print_message "Setting up scheduler..."
mkdir -p docker
cat > docker/scheduler.sh <<EOF
#!/bin/bash
echo "* * * * * cd /var/www && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1" | crontab -
cron -f
echo ""
EOF

# Make scheduler script executable
chmod +x docker/scheduler.sh
check_status

# Create nginx.conf if it doesn't exist
print_message "Creating nginx configuration..."
cat > nginx.conf <<EOF
server {
    listen 80;
    index index.php index.html;
    error_log  /var/log/nginx/error.log;
    access_log /var/log/nginx/access.log;
    root /var/www/public;

    location ~ \.php$ {
        try_files \$uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        fastcgi_param PATH_INFO \$fastcgi_path_info;
    }

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
        gzip_static on;
    }
}
EOF
check_status

# Create storage directory if it doesn't exist
print_message "Setting up storage directory..."
mkdir -p storage/framework/{sessions,views,cache}
mkdir -p storage/logs
chmod -R 775 storage
chmod -R 775 bootstrap/cache
check_status

# Copy environment file if it doesn't exist
print_message "Checking .env file..."
if [ ! -f .env ]; then
    cp .env.example .env
    check_status
    # Update database configuration in .env
    sed -i '' 's/DB_HOST=.*/DB_HOST=db/' .env 2>/dev/null || sed -i 's/DB_HOST=.*/DB_HOST=db/' .env
    sed -i '' 's/DB_DATABASE=.*/DB_DATABASE=news_aggregator_api/' .env 2>/dev/null || sed -i 's/DB_DATABASE=.*/DB_DATABASE=news_aggregator_api/' .env
    sed -i '' 's/DB_USERNAME=.*/DB_USERNAME=root/' .env 2>/dev/null || sed -i 's/DB_USERNAME=.*/DB_USERNAME=root/' .env
    sed -i '' 's/DB_PASSWORD=.*/DB_PASSWORD=/' .env 2>/dev/null || sed -i 's/DB_PASSWORD=.*/DB_PASSWORD=/' .env
fi

# Start containers
print_message "Starting Docker containers..."
docker compose up -d --build
check_status

# Wait for MySQL
wait_for_mysql

# Install dependencies
print_message "Installing dependencies..."
docker compose exec app chown -R www-data:www-data /var/www
docker compose exec app chown -R www-data:www-data storage
docker compose exec app mkdir /root/.config/
docker compose exec app mkdir /root/.config/composer
docker compose exec app chown -R "$(id -un)" "$(composer config --global home)"
docker compose exec app composer install
check_status

# Generate application key
print_message "Generating application key..."
docker compose exec -u docker app php artisan key:generate
check_status

# Run migrations
print_message "Running migrations..."
docker compose exec -u docker app php artisan migrate
check_status

# Clear caches
print_message "Clearing caches..."
docker compose exec -u docker app php artisan cache:clear
docker compose exec -u docker app php artisan config:clear
docker compose exec -u docker app php artisan view:clear
check_status

# Create storage link
print_message "Creating storage link..."
docker compose exec -u docker app php artisan storage:link
check_status

# Display information
echo -e "\n${GREEN}Setup completed successfully!${NC}"
echo -e "\nApplication is running at: ${YELLOW}http://localhost:8000${NC}"
echo -e "MySQL is available at: ${YELLOW}localhost:3306${NC}"
echo -e "Database name: ${YELLOW}news_aggregator_api${NC}"
echo -e "MySQL username: ${YELLOW}root${NC}"
echo -e "MySQL password: ${YELLOW}none${NC}"
echo -e "Scheduler is running in the background${NC}"

echo -e "\n${YELLOW}Useful commands:${NC}"
echo "- View logs: docker compose logs -f"
echo "- View scheduler logs: docker compose logs -f scheduler"
echo "- Stop application: docker compose down"
echo "- Run artisan command: docker compose exec app php artisan <command>"
echo "- Access MySQL: docker compose exec db mysql -u root news_aggregator_api"

# Check container status
echo -e "\n${YELLOW}Container Status:${NC}"
docker compose ps

# Run tests
echo -e "\n${YELLOW}Running Tests:${NC}"
docker compose exec app php artisan test

# Fetch initial articles
echo -e "\n${YELLOW}Fetching articles:${NC}"
docker compose exec app php artisan articles:fetch --all
