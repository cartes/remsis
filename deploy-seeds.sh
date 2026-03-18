#!/bin/bash

# Script para ejecutar seeder de códigos SII en producción
# Uso: ./deploy-seeds.sh

echo "Iniciando ejecución de migraciones en Producción..."
php artisan migrate --force

echo "Iniciando carga de códigos SII..."
php artisan db:seed --class="Modules\Core\Database\Seeders\EconomicActivitySeeder" --force

echo "Iniciando carga de Catálogo Haberes Imponibles..."
php artisan db:seed --class="Modules\AdminPanel\Database\Seeders\TaxableItemsSeeder" --force

echo "Despliegue de DB completado con éxito."
