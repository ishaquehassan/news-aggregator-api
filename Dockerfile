FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    default-mysql-client \
    cron

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql mysqli mbstring exif pcntl bcmath gd

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create system user and setup permissions
RUN useradd -G www-data,root -u 1000 -d /home/docker docker
RUN mkdir -p /home/docker/.composer && \
    chown -R docker:docker /home/docker && \
    mkdir -p /var/run/cron && \
    chown -R docker:docker /var/run/cron && \
    chmod 777 /var/run/cron

# Set working directory
WORKDIR /var/www

CMD ["php-fpm"]
