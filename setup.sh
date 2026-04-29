#!/bin/bash
set -e

echo ">>> Building Docker images..."
docker compose build

if [ ! -f artisan ]; then
  echo ">>> Creating Laravel project..."
  docker run --rm \
    -v "$(pwd):/var/www" \
    composer:latest \
    sh -c "composer create-project laravel/laravel /tmp/laravel --prefer-dist --no-install --no-scripts && cp -rn /tmp/laravel/. /var/www/ && rm -rf /tmp/laravel"
else
  echo ">>> Laravel already installed, skipping create-project."
fi

echo ">>> Ensuring vendor dependencies are installed..."
rm -f composer.lock
docker compose run --rm app composer install

echo ">>> Installing Laravel Breeze..."
docker compose run --rm app composer require laravel/breeze --dev

echo ">>> Scaffolding Breeze with React + Inertia..."
docker compose run --rm app php artisan breeze:install react --no-interaction

echo ">>> Configuring .env for MariaDB..."
[ ! -f .env ] && cp .env.example .env
sed -i.bak \
  -e 's/DB_CONNECTION=sqlite/DB_CONNECTION=mysql/' \
  -e 's/# DB_HOST=127.0.0.1/DB_HOST=db/' \
  -e 's/# DB_PORT=3306/DB_PORT=3306/' \
  -e 's/# DB_DATABASE=laravel/DB_DATABASE=laboratorio/' \
  -e 's/# DB_USERNAME=root/DB_USERNAME=laboratorio/' \
  -e 's/# DB_PASSWORD=/DB_PASSWORD=secret/' \
  .env && rm -f .env.bak

echo ">>> Generating application key..."
docker compose run --rm app php artisan key:generate --force

echo ">>> Starting all containers..."
docker compose up -d

echo ">>> Waiting for MariaDB to be ready..."
sleep 10

echo ">>> Running migrations..."
docker compose exec app php artisan migrate --force

echo ""
echo "Done! Visit http://localhost:8000"
echo "Vite (React) is running on http://localhost:5173"
