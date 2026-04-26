# 🎯 Ai Job Hunt

Application web de gestion et suivi de candidatures pour simplifier votre recherche d'emploi.

## ✨ Fonctionnalités

- 🔍 **Agrégation d'offres d'emploi** depuis plusieurs sources (Welcome to the Jungle, JSearch, Indeed)
- 📊 **Tableau Kanban** pour suivre vos candidatures (drag & drop)
- 🎨 **Interface moderne** avec thème clair/sombre
- 🔐 **Authentification sécurisée** avec Symfony Security
- 📱 **Responsive design**

## 🛠️ Technologies

- **Backend:** Symfony 7.2 (PHP 8.2+)
- **Base de données:** PostgreSQL
- **Frontend:** Stimulus, Turbo, CSS moderne
- **Sécurité:** CSRF protection, mot de passe hashés (bcrypt/argon2)

## 📋 Prérequis

- PHP 8.2 ou supérieur
- Composer
- PostgreSQL 16
- Symfony CLI (optionnel)

## 🚀 Installation

1. **Cloner le repository**
   ```bash
   git clone https://github.com/votre-username/ai-job-hunt.git
   cd ai-job-hunt
   ```

2. **Installer les dépendances**
   ```bash
   composer install
   ```

3. **Configurer l'environnement**
   ```bash
   cp .env.example .env
   ```
   
   Éditez le fichier `.env` et configurez :
   - `APP_SECRET` : Générez une clé avec `php -r "echo bin2hex(random_bytes(32));"`
   - `DATABASE_URL` : Vos identifiants PostgreSQL
   - `RAPIDAPI_KEY` : Votre clé RapidAPI (optionnel)

4. **Créer la base de données**
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

5. **Lancer le serveur**
   ```bash
   symfony server:start
   # OU
   php -S localhost:8000 -t public/
   ```

6. **Accéder à l'application**
   Ouvrez votre navigateur sur `http://localhost:8000`

## 📊 Utilisation

### Récupérer des offres d'emploi

```bash
# Récupérer toutes les offres
php bin/console app:jobs:fetch-all

# Ou par source spécifique
php bin/console app:jobs:fetch-wttj
php bin/console app:jobs:fetch-jsearch
php bin/console app:jobs:fetch-indeed
```

### Créer un compte utilisateur

1. Accédez à `/register`
2. Remplissez le formulaire d'inscription
3. Connectez-vous et commencez à suivre vos candidatures !

## 🔐 Sécurité

- Mots de passe hashés avec algorithme sécurisé (bcrypt/argon2)
- Protection CSRF sur tous les formulaires
- Sessions sécurisées
- Politique de confidentialité et mentions légales conformes RGPD

## 📝 Commandes utiles

```bash
# Vider le cache
php bin/console cache:clear

# Créer une migration
php bin/console make:migration

# Lancer les tests (si configurés)
php bin/phpunit
```

## 📄 Licence

Ce projet est un projet personnel à but non commercial.

## 👤 Auteur

Augustin Gantelmi d'Ille
- Email: augustin.dille@yahoo.fr
- Localisation: Lyon, France

## 🙏 Remerciements

- Welcome to the Jungle, JSearch et Indeed pour leurs APIs publiques
- La communauté Symfony

---

**Note:** Ce projet est à but éducatif et personnel. Les offres d'emploi affichées proviennent de sources externes et leur exactitude n'est pas garantie.
