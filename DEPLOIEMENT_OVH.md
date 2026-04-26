# 🚀 Guide de Déploiement OVH - Ai Job Hunt

## 📋 Prérequis OVH

### Offre recommandée : VPS Starter ou supérieur
- **CPU :** 1 vCore minimum
- **RAM :** 2 GB minimum
- **Stockage :** 20 GB minimum
- **OS :** Ubuntu 22.04 LTS (recommandé) ou Debian 12

---

## 🔧 Étape 1 : Configuration initiale du VPS

### 1.1 Se connecter au VPS via SSH

```bash
ssh ubuntu@votre-ip-vps
# Ou si vous utilisez Debian:
# ssh debian@votre-ip-vps
```

### 1.2 Mettre à jour le système

```bash
sudo apt update && sudo apt upgrade -y
```

### 1.3 Installer les dépendances

```bash
# PHP 8.2
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.2 php8.2-cli php8.2-fpm php8.2-common php8.2-mysql \
    php8.2-pgsql php8.2-xml php8.2-curl php8.2-mbstring php8.2-zip \
    php8.2-intl php8.2-gd php8.2-bcmath

# PostgreSQL 16
sudo apt install -y postgresql postgresql-contrib

# Nginx
sudo apt install -y nginx

# Composer
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer

# Git
sudo apt install -y git

# Certbot (pour SSL/HTTPS gratuit)
sudo apt install -y certbot python3-certbot-nginx
```

---

## 🗄️ Étape 2 : Configuration PostgreSQL

### 2.1 Créer la base de données et l'utilisateur

```bash
sudo -u postgres psql

-- Dans psql:
CREATE DATABASE jobhunt_prod;
CREATE USER jobhunt_user WITH ENCRYPTED PASSWORD 'VotreMotDePasseSecurise123!';
GRANT ALL PRIVILEGES ON DATABASE jobhunt_prod TO jobhunt_user;
\q
```

### 2.2 Noter les informations de connexion

```
DATABASE_URL="postgresql://jobhunt_user:VotreMotDePasseSecurise123!@localhost:5432/jobhunt_prod?serverVersion=16&charset=utf8"
```

---

## 📦 Étape 3 : Déploiement du code

### 3.1 Créer le répertoire de l'application

```bash
sudo mkdir -p /var/www/ai-job-hunt
sudo chown $USER:$USER /var/www/ai-job-hunt
cd /var/www/ai-job-hunt
```

### 3.2 Cloner le repository depuis GitHub

```bash
git clone https://github.com/Braskalyne/ai-job-hunt.git .
```

### 3.3 Installer les dépendances

```bash
composer install --no-dev --optimize-autoloader
```

### 3.4 Configurer l'environnement de production

```bash
# Créer le fichier .env.local
nano .env.local
```

Copiez ce contenu (à adapter) :

```env
APP_ENV=prod
APP_DEBUG=0
APP_SECRET=GENEREZ_UNE_NOUVELLE_CLE_SECRETE_ICI
DATABASE_URL="postgresql://jobhunt_user:VotreMotDePasseSecurise123!@localhost:5432/jobhunt_prod?serverVersion=16&charset=utf8"
DEFAULT_URI=https://votre-domaine.com
MAILER_DSN=null://null
MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0
```

**Pour générer APP_SECRET :**
```bash
php -r "echo bin2hex(random_bytes(32));"
```

### 3.5 Exécuter les migrations

```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

### 3.6 Compiler le cache et les assets

```bash
php bin/console cache:clear
php bin/console cache:warmup
php bin/console asset-map:compile
```

### 3.7 Définir les permissions correctes

```bash
sudo chown -R www-data:www-data /var/www/ai-job-hunt/var
sudo chmod -R 775 /var/www/ai-job-hunt/var
```

---

## 🌐 Étape 4 : Configuration Nginx

### 4.1 Créer le fichier de configuration

```bash
sudo nano /etc/nginx/sites-available/ai-job-hunt
```

Copiez cette configuration :

```nginx
server {
    listen 80;
    server_name votre-domaine.com www.votre-domaine.com;
    root /var/www/ai-job-hunt/public;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        internal;
    }

    location ~ \.php$ {
        return 404;
    }

    error_log /var/log/nginx/ai-job-hunt_error.log;
    access_log /var/log/nginx/ai-job-hunt_access.log;
}
```

### 4.2 Activer le site

```bash
sudo ln -s /etc/nginx/sites-available/ai-job-hunt /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

## 🔒 Étape 5 : Activer HTTPS avec Let's Encrypt

```bash
sudo certbot --nginx -d votre-domaine.com -d www.votre-domaine.com
```

Suivez les instructions et choisissez l'option pour rediriger HTTP vers HTTPS.

---

## 🌍 Étape 6 : Configuration du domaine

### Sur l'interface OVH :

1. Allez dans **Web Cloud** → **Domaines**
2. Sélectionnez votre domaine
3. Onglet **Zone DNS**
4. Ajoutez/modifiez les enregistrements :

```
Type A  | Sous-domaine: @   | Cible: [IP de votre VPS]
Type A  | Sous-domaine: www | Cible: [IP de votre VPS]
```

**Propagation DNS :** 1-24 heures (généralement 1-2h)

---

## ✅ Étape 7 : Vérifications finales

### 7.1 Créer un utilisateur admin

```bash
cd /var/www/ai-job-hunt
# Créez-vous un compte via l'interface web /register
```

### 7.2 Importer des offres d'emploi (optionnel)

```bash
php bin/console app:jobs:fetch-all
```

### 7.3 Configurer un CRON pour les offres (optionnel)

```bash
sudo crontab -e -u www-data
```

Ajoutez :
```cron
# Récupérer les offres tous les jours à 2h du matin
0 2 * * * cd /var/www/ai-job-hunt && php bin/console app:jobs:fetch-all >> /var/log/jobhunt-cron.log 2>&1
```

---

## 🔍 Dépannage

### Erreur 500
```bash
tail -f /var/log/nginx/ai-job-hunt_error.log
tail -f /var/www/ai-job-hunt/var/log/prod.log
```

### Problèmes de permissions
```bash
sudo chown -R www-data:www-data /var/www/ai-job-hunt/var
sudo chmod -R 775 /var/www/ai-job-hunt/var
```

### Cache
```bash
cd /var/www/ai-job-hunt
php bin/console cache:clear --env=prod
sudo chown -R www-data:www-data var/
```

---

## 📊 Monitoring (optionnel mais recommandé)

### Installer Sentry pour les erreurs

```bash
composer require sentry/sentry-symfony
```

Dans `.env.local` :
```env
SENTRY_DSN=votre_dsn_sentry
```

---

## 🔐 Sécurité renforcée

### Pare-feu UFW

```bash
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

### Fail2ban (protection contre les attaques brute-force)

```bash
sudo apt install -y fail2ban
sudo systemctl enable fail2ban
sudo systemctl start fail2ban
```

---

## 📝 Checklist de mise en production

- [ ] VPS provisionné et configuré
- [ ] PHP 8.2, PostgreSQL, Nginx installés
- [ ] Base de données créée
- [ ] Code déployé depuis GitHub
- [ ] Dépendances Composer installées
- [ ] `.env.local` configuré avec APP_SECRET et DATABASE_URL
- [ ] Migrations exécutées
- [ ] Cache compilé
- [ ] Permissions définies correctement
- [ ] Nginx configuré
- [ ] HTTPS activé avec Let's Encrypt
- [ ] DNS configuré (A records)
- [ ] Site accessible sur votre domaine
- [ ] Compte utilisateur créé
- [ ] Tests effectués (login, candidature, etc.)

---

## 🎉 Votre site est en ligne !

Accédez à : **https://votre-domaine.com**

---

**Support :**
- Documentation Symfony : https://symfony.com/doc/current/deployment.html
- Documentation OVH : https://docs.ovh.com/fr/
