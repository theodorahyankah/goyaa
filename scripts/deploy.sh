#!/bin/bash

# Deployment script for Goyaa
# This script is called by GitHub Actions after files are copied to the server.

set -e

PROJECT_DIR="$1"
if [ -z "$PROJECT_DIR" ]; then
    PROJECT_DIR="/root/goya"
fi

cd "$PROJECT_DIR"

echo "🚀 Starting deployment in $PROJECT_DIR..."

# 1. Ensure .env exists
if [ ! -f .env ]; then
    if [ -f .env.example ]; then
        echo "📄 Creating .env from .env.example..."
        cp .env.example .env
    else
        touch .env
    fi
fi

# 2. Update environment variables using our helper script
UPDATE_ENV="./scripts/update-env.sh"
[ -f "./update-env.sh" ] && UPDATE_ENV="./update-env.sh"

chmod +x "$UPDATE_ENV"
"$UPDATE_ENV" DB_CONNECTION mysql
"$UPDATE_ENV" DB_HOST db
"$UPDATE_ENV" DB_PORT 3306
"$UPDATE_ENV" DB_DATABASE laravel
"$UPDATE_ENV" DB_USERNAME root
"$UPDATE_ENV" DB_PASSWORD secret
"$UPDATE_ENV" REDIS_HOST redis

# 3. Ensure APP_KEY exists in .env for Docker to start properly
if ! grep -q "APP_KEY=base64:" .env; then
    echo "Generating temporary APP_KEY..."
    echo "APP_KEY=" >> .env
fi

# 4. Pull and Start Containers
echo "🐳 Restarting Docker containers..."
sudo docker compose pull
sudo docker compose up -d --remove-orphans

# 5. Wait for DB to be ready
echo "⏳ Waiting for database to start..."
sleep 15

# 6. Initialize Database if needed (Logic from setup.sh)
INSTALL_MARKER=".installed"
if [ ! -f "$INSTALL_MARKER" ]; then
    echo "🆕 First deployment detected. Initializing database..."

    echo "Ensuring database exists..."
    sudo docker exec goya-db mysql -u root -proot -e "CREATE DATABASE IF NOT EXISTS laravel; CREATE USER IF NOT EXISTS 'laravel'@'%' IDENTIFIED BY 'secret'; GRANT ALL PRIVILEGES ON laravel.* TO 'laravel'@'%'; FLUSH PRIVILEGES;"

    echo "Importing base SQL..."
    # Try multiple paths for database.sql
    SQL_FILE="installation/backup/database.sql"
    if sudo docker exec goya-app ls "/var/www/$SQL_FILE" >/dev/null 2>&1; then
        echo "Importing $SQL_FILE..."
        sudo docker exec goya-app cat "/var/www/$SQL_FILE" | sudo docker exec -i goya-db mysql -u root -proot laravel
    else
        echo "⚠️ Could not find $SQL_FILE inside container."
    fi

    echo "Importing base images..."
    ZIP_FILE="installation/public.zip"
    if sudo docker exec goya-app ls "/var/www/$ZIP_FILE" >/dev/null 2>&1; then
        echo "Unzipping $ZIP_FILE..."
        sudo docker exec goya-app unzip -o "/var/www/$ZIP_FILE" -d /var/www/storage/app/public
        sudo docker exec goya-app sh -c 'if [ -d "/var/www/storage/app/public/public" ]; then mv /var/www/storage/app/public/public/* /var/www/storage/app/public/ && rm -rf /var/www/storage/app/public/public; fi'
        sudo docker exec goya-app rm -rf /var/www/storage/app/public/__MACOSX
    else
        echo "⚠️ Could not find $ZIP_FILE inside container."
    fi

    sudo touch "$INSTALL_MARKER"
fi

# 7. Post-Deployment Artisan Commands
echo "🛠️ Running Laravel maintenance tasks..."

# Generate key if still empty
if [ "$(grep "APP_KEY=" .env | cut -d'=' -f2)" = "" ]; then
    sudo docker compose exec -T app php artisan key:generate --force
fi

sudo docker compose exec -T app php artisan migrate --force
sudo docker compose exec -T app php artisan db:seed --class=GoyaaGhanaSeeder --force
sudo docker compose exec -T app php artisan passport:install --force
sudo docker compose exec -T app php artisan storage:link --force

# 8. Permissions and Optimization
echo "🔒 Setting permissions..."
sudo docker exec goya-app chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache /var/www/config
sudo docker exec goya-app chmod -R 775 /var/www/storage /var/www/bootstrap/cache /var/www/config

echo "✨ Optimizing application..."
sudo docker compose exec -T app php artisan optimize:clear
sudo docker compose exec -T app php artisan optimize

echo "🎉 Deployment finished successfully!"
