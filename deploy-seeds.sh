#!/bin/bash

# Script para ejecutar seeder de códigos SII en producción
# Uso: ./deploy-seeds.sh

echo "Iniciando carga de códigos SII..."

php artisan db:seed --class=CodigoSiiSeeder --force

echo "Carga completada con éxito."
