#!/bin/bash

# Navigate to the project directory
PROJECT_DIR="$(pwd)"
if [ -d "/root/goya" ]; then
    PROJECT_DIR="/root/goya"
fi
cd "$PROJECT_DIR"

echo "Using project directory: $PROJECT_DIR"

# 1. Handle .env file
if [ ! -f .env ]; then
    if [ -f .env.example ]; then
        echo "Creating .env from .env.example..."
        cp .env.example .env
    else
        echo "Creating basic .env file..."
        cat <<EOT >> .env
APP_NAME=Goya
APP_ENV=development
APP_DEBUG=true
APP_URL=http://127.0.0.1
LOG_CHANNEL=stack
LOG_LEVEL=debug
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=secret
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
SESSION_DRIVER=redis
CACHE_DRIVER=redis
EOT
    fi
fi

# 2. Fix/Generate APP_KEY
if ! grep -q "APP_KEY=base64:" .env || grep -q "APP_KEY=base64:\$(openssl" .env; then
    echo "Generating new APP_KEY..."
    sed -i '/APP_KEY=/d' .env
    NEW_KEY="base64:$(openssl rand -base64 32)"
    echo "APP_KEY=$NEW_KEY" >> .env
    echo "New APP_KEY generated."
fi

# 3. Docker Deployment
echo "Restarting Docker containers..."
docker compose down
docker compose up -d

# 4. Post-deployment tasks
echo "Waiting for containers to start..."
sleep 15

INSTALL_MARKER=".installed"

if [ ! -f "$INSTALL_MARKER" ]; then
    echo "First deployment detected. Initializing database..."

    echo "Ensuring database exists..."
    docker exec goya-db mysql -u root -proot -e "CREATE DATABASE IF NOT EXISTS laravel; CREATE USER IF NOT EXISTS 'laravel'@'%' IDENTIFIED BY 'secret'; GRANT ALL PRIVILEGES ON laravel.* TO 'laravel'@'%'; FLUSH PRIVILEGES;"

    echo "Importing base SQL..."
    # Check if database.sql exists (the one with data)
    if [ -f "installation/backup/database.sql" ]; then
        echo "Importing installation/backup/database.sql..."
        docker exec -i goya-db mysql -u root -proot laravel < installation/backup/database.sql
    elif [ -f "installation/backup/database_v3.5.sql" ]; then
        echo "Importing installation/backup/database_v3.5.sql..."
        docker exec -i goya-db mysql -u root -proot laravel < installation/backup/database_v3.5.sql
    fi

    echo "Importing base images..."
    if [ -f "installation/public.zip" ]; then
        echo "Unzipping installation/public.zip..."
        docker exec goya-app unzip -o installation/public.zip -d storage/app/public
        # Move files if they are nested in a 'public' folder inside the zip
        docker exec goya-app sh -c 'if [ -d "storage/app/public/public" ]; then mv storage/app/public/public/* storage/app/public/ && rm -rf storage/app/public/public; fi'
        docker exec goya-app rm -rf storage/app/public/__MACOSX
    fi

    echo "Running migrations..."
    docker exec goya-app php artisan migrate --force

    echo "Running seeders..."
    docker exec goya-app php artisan db:seed --force

    echo "Installing Passport..."
    docker exec goya-app php artisan passport:install --force

    echo "Creating installation marker..."
    touch "$INSTALL_MARKER"
else
    echo "Subsequent deployment detected. Running migrations only..."
    docker exec goya-app php artisan migrate --force
fi

echo "Setting permissions..."
docker exec goya-app chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache /var/www/config
docker exec goya-app chmod -R 775 /var/www/storage /var/www/bootstrap/cache /var/www/config

echo "Creating storage link..."
docker exec goya-app php artisan storage:link --force

echo "Optimizing application..."
docker exec goya-app php artisan optimize:clear
docker exec goya-app php artisan optimize

echo "Deployment finished successfully!"
