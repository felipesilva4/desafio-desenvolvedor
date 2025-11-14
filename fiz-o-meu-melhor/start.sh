#!/bin/bash

echo "Subindo containers..."
docker compose up -d --build

echo "Ajustando permissões da pasta storage..."
sudo chmod -R 777 storage storage/logs storage/framework

echo "Instalando dependências do Composer dentro do container..."
docker exec -it app-laravel composer install
