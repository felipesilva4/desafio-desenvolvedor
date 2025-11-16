#!/bin/bash

echo "Subindo containers..."
docker compose up -d --build

echo "Ajustando permissões da pasta storage..."
sudo chmod -R 777 storage storage/

echo "Instalando dependências do Composer dentro do container..."
docker exec -u 0 -it app-laravel composer install
docker exec -it app-laravel php artisan migrate --seed
docker exec -i postgres-db psql -U root -d postgres -c "SELECT 1 FROM pg_database WHERE datname = 'app_test'" | grep -q 1 || docker exec -i postgres-db psql -U root -d postgres -c "CREATE DATABASE app_test;"
echo "Banco de testes app_test criado/verificado"
