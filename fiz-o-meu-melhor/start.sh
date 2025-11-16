#!/bin/bash

echo "Subindo containers..."
docker compose up -d --build

echo "Ajustando permissões da pasta storage..."
sudo chmod -R 777 storage storage/

echo "Instalando dependências do Composer dentro do container..."
docker exec -u 0 -it app-laravel composer install
docker exec -it app-laravel php artisan migrate --seed


