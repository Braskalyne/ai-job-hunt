# Configuration France Travail API (ex-Pôle Emploi)

## 📝 Obtenir vos identifiants API France Travail

### 1. Créer un compte développeur

1. **Allez sur le portail France Travail IO:**
   - URL: https://francetravail.io/inscription
   
2. **Créez un compte** en remplissant le formulaire :
   - Nom, prénom, email professionnel
   - Raison sociale (nom de votre entreprise ou projet)
   - Validez votre email

### 2. Créer une application

1. **Connectez-vous** sur https://francetravail.io/
   
2. **Accédez à "Mes applications"** dans votre espace personnel

3. **Cliquez sur "Créer une application"**

4. **Remplissez le formulaire** :
   - **Nom de l'application** : `Ai Job Hunt` (ou le nom de votre choix)
   - **Description** : `Agrégateur d'offres d'emploi`
   - **URL de l'application** : `https://recherche-jobs.fr` (ou votre domaine)
   - **Type d'application** : `Application web`

5. **Sélectionnez les API** :
   - ✅ Cochez **"Offres d'emploi v2"**
   - Cette API vous permet de rechercher et récupérer les offres d'emploi

6. **Validez la création**

### 3. Récupérer vos identifiants

1. Une fois l'application créée, vous accédez à ses **détails**

2. Vous y trouverez :
   - **Client ID** (Identifiant client)
   - **Client Secret** (Secret client)
   
3. **Copiez ces deux valeurs**

⚠️ **Important** : Le Client Secret ne sera affiché qu'une seule fois. Si vous le perdez, vous devrez le régénérer.

### 4. Configurer les variables d'environnement

#### En développement local :

Éditez le fichier `.env.local` à la racine du projet :

```env
###> France Travail API ###
FRANCETRAVAIL_CLIENT_ID=votre_client_id_ici
FRANCETRAVAIL_CLIENT_SECRET=votre_client_secret_ici
###< France Travail API ###
```

⚠️ **Important** : Ne committez JAMAIS le fichier `.env.local` dans Git !

#### En production :

Sur votre serveur de production, ajoutez ces variables dans `.env.local` :

```env
FRANCETRAVAIL_CLIENT_ID=votre_client_id_prod
FRANCETRAVAIL_CLIENT_SECRET=votre_client_secret_prod
```

## 🚀 Utilisation

### Tester la récupération d'offres

```bash
# Récupérer 30 offres pour "développeur php"
php bin/console app:jobs:fetch-francetravail

# Avec des options personnalisées
php bin/console app:jobs:fetch-francetravail --query="développeur symfony" --location="Paris" --limit=50

# Récupérer depuis toutes les sources (dont France Travail)
php bin/console app:jobs:fetch-all --query="développeur php" --location="Lyon"
```

### Options disponibles

- `--query` : Mots-clés de recherche (ex: "développeur php", "data scientist")
- `--location` : Ville ou localisation (ex: "Paris", "Lyon", "France")
- `--limit` : Nombre maximum d'offres à récupérer (max: 150 par requête)

## 📊 Limites et quotas

### Plan gratuit (par défaut)

- ✅ **200 requêtes par jour**
- ✅ **150 résultats maximum par requête**
- ✅ Accès complet à l'API Offres d'emploi v2

### Si vous dépassez les quotas

- Les requêtes supplémentaires seront refusées (erreur 429)
- Attendez le lendemain ou souscrivez à un plan payant

### Pour augmenter les quotas

Contactez France Travail via votre espace développeur pour demander une augmentation de quota si nécessaire.

## 📖 Documentation officielle

- **Portail développeur** : https://francetravail.io/
- **Documentation API Offres d'emploi** : https://francetravail.io/data/api/offres-emploi
- **Documentation OAuth2** : https://francetravail.io/data/documentation/utilisation-api-pole-emploi/generer-access-token

## 🔍 Exemples de recherche

```bash
# Recherches par métier
php bin/console app:jobs:fetch-francetravail --query="développeur web"
php bin/console app:jobs:fetch-francetravail --query="data scientist"
php bin/console app:jobs:fetch-francetravail --query="chef de projet"

# Recherches par localisation
php bin/console app:jobs:fetch-francetravail --query="développeur" --location="Paris"
php bin/console app:jobs:fetch-francetravail --query="développeur" --location="Marseille"

# Recherches ciblées
php bin/console app:jobs:fetch-francetravail --query="symfony" --location="Île-de-France" --limit=100
```

## ❌ Dépannage

### "No jobs fetched from France Travail"

- Vérifiez que `FRANCETRAVAIL_CLIENT_ID` et `FRANCETRAVAIL_CLIENT_SECRET` sont bien configurés dans `.env.local`
- Vérifiez que vos identifiants sont corrects
- Vérifiez que votre application est bien activée sur francetravail.io
- Vérifiez que l'API "Offres d'emploi v2" est bien sélectionnée

### "Erreur 401 Unauthorized"

- Vos identifiants sont incorrects
- Régénérez vos identifiants sur francetravail.io

### "Erreur 429 Too Many Requests"

- Vous avez dépassé le quota journalier de 200 requêtes
- Attendez le lendemain ou contactez France Travail pour augmenter votre quota

### "Erreur 403 Forbidden"

- L'API "Offres d'emploi v2" n'est pas activée pour votre application
- Retournez dans les paramètres de votre application et cochez cette API
