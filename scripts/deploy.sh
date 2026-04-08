#!/bin/bash

# Deployment script for Goyaa
# This script is called by GitHub Actions after files are copied to the server.

set -e

PROJECT_DIR="$1"
if [ -z "$PROJECT_DIR" ]; then
    PROJECT_DIR="~/goya"
fi

cd "$PROJECT_DIR"

echo "🚀 Starting deployment in $PROJECT_DIR..."

# 1. Ensure .env exists
if [ ! -f .env ]; then
    echo "📄 Creating .env from .env.example..."
    cp .env.example .env
fi

# 2. Update environment variables using our helper script
chmod +x scripts/update-env.sh
./scripts/update-env.sh DB_CONNECTION mysql
./scripts/update-env.sh DB_HOST db
./scripts/update-env.sh DB_PORT 3306
./scripts/update-env.sh DB_DATABASE laravel
./scripts/update-env.sh DB_USERNAME root
./scripts/update-env.sh DB_PASSWORD secret
./scripts/update-env.sh REDIS_HOST redis

# 3. Ensure APP_KEY exists in .env for Docker to start properly
if ! grep -q "APP_KEY=base64:" .env; then
    echo "Generating temporary APP_KEY..."
    echo "APP_KEY=" >> .env
fi

# 4. Pull and Start Containers
echo "🐳 Restarting Docker containers..."
docker compose pull
docker compose up -d --remove-orphans

# 5. Wait for DB to be ready
echo "⏳ Waiting for database to start..."
sleep 15

# 6. Initialize Database if needed (Logic from setup.sh)
INSTALL_MARKER=".installed"
if [ ! -f "$INSTALL_MARKER" ]; then
    echo "🆕 First deployment detected. Initializing database..."

    echo "Ensuring database exists..."
    docker exec goya-db mysql -u root -proot -e "CREATE DATABASE IF NOT EXISTS laravel; CREATE USER IF NOT EXISTS 'laravel'@'%' IDENTIFIED BY 'secret'; GRANT ALL PRIVILEGES ON laravel.* TO 'laravel'@'%'; FLUSH PRIVILEGES;"

    echo "Importing base SQL..."
    if [ -f "installation/backup/database.sql" ]; then
        docker exec goya-app cat /var/www/installation/backup/database.sql | docker exec -i goya-db mysql -u root -proot laravel
    fi

    echo "Importing base images..."
    if [ -f "installation/public.zip" ]; then
        docker exec goya-app unzip -o /var/www/installation/public.zip -d /var/www/storage/app/public
        docker exec goya-app sh -c 'if [ -d "/var/www/storage/app/public/public" ]; then mv /var/www/storage/app/public/public/* /var/www/storage/app/public/ && rm -rf /var/www/storage/app/public/public; fi'
        docker exec goya-app rm -rf /var/www/storage/app/public/__MACOSX
    fi

    touch "$INSTALL_MARKER"
fi

# 7. Post-Deployment Artisan Commands
echo "🛠️ Running Laravel maintenance tasks..."

# Generate key if still empty
if [ "$(grep "APP_KEY=" .env | cut -d'=' -f2)" = "" ]; then
    docker compose exec -T app php artisan key:generate --force
fi

docker compose exec -T app php artisan migrate --force
docker compose exec -T app php artisan db:seed --class=GoyaaGhanaSeeder --force
docker compose exec -T app php artisan passport:install --force
docker compose exec -T app php artisan storage:link --force

# 8. Permissions and Optimization
echo "🔒 Setting permissions..."
docker exec goya-app chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache /var/www/config
docker exec goya-app chmod -R 775 /var/www/storage /var/www/bootstrap/cache /var/www/config

echo "✨ Optimizing application..."
docker compose exec -T app php artisan optimize:clear
docker compose exec -T app php artisan optimize

echo "🎉 Deployment finished successfully!"
