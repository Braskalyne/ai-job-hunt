#!/bin/bash

# Script de déploiement pour Ai Job Hunt
# À exécuter sur le serveur OVH

set -e

echo "🚀 Déploiement de Ai Job Hunt..."

# Variables (à adapter)
APP_DIR="/var/www/ai-job-hunt"
PHP_VERSION="8.2"

# 1. Mise à jour du code
echo "📥 Récupération du code depuis GitHub..."
cd $APP_DIR
git pull origin main

# 2. Installation des dépendances
echo "📦 Installation des dépendances..."
composer install --no-dev --optimize-autoloader --no-interaction

# 3. Migrations
echo "🗄️ Exécution des migrations..."
php bin/console doctrine:migrations:migrate --no-interaction

# 4. Compilation du cache
echo "⚙️ Compilation du cache..."
php bin/console cache:clear --env=prod --no-warmup
php bin/console cache:warmup --env=prod

# 5. Compilation des assets
echo "🎨 Compilation des assets..."
php bin/console asset-map:compile

# 6. Permissions
echo "🔐 Ajustement des permissions..."
sudo chown -R www-data:www-data $APP_DIR/var
sudo chmod -R 775 $APP_DIR/var

echo "✅ Déploiement terminé !"
echo "🌐 Votre site est à jour !"
