# Configuration de l'authentification Google OAuth2

## Prérequis

L'authentification Google OAuth2 a été intégrée au site. Pour l'activer, vous devez configurer un projet Google Cloud.

## Étapes de configuration

### 1. Créer un projet Google Cloud

1. Allez sur [Google Cloud Console](https://console.cloud.google.com/)
2. Créez un nouveau projet ou sélectionnez un projet existant
3. Activez l'API Google+ (nécessaire pour récupérer le profil utilisateur)

### 2. Configurer l'écran de consentement OAuth

1. Dans le menu de gauche, allez dans **API et services** > **Écran de consentement OAuth**
2. Choisissez **Externe** comme type d'utilisateur
3. Remplissez les informations requises :
   - Nom de l'application : **Ai Job Hunt**
   - Email d'assistance utilisateur : votre email
   - Domaine autorisé : (laissez vide pour le développement local)
4. Ajoutez les scopes suivants :
   - `.../auth/userinfo.email`
   - `.../auth/userinfo.profile`
5. Enregistrez et continuez

### 3. Créer les identifiants OAuth 2.0

#### Pour le développement local :

1. Allez dans **API et services** > **Identifiants**
2. Cliquez sur **+ CRÉER DES IDENTIFIANTS** > **ID client OAuth**
3. Type d'application : **Application Web**
4. Nom : **Ai Job Hunt - Dev**
5. **Origines JavaScript autorisées** :
   - `http://localhost:8000`
   - `http://127.0.0.1:8000`
6. **URI de redirection autorisés** :
   - `http://localhost:8000/connect/google/check`
   - `http://127.0.0.1:8000/connect/google/check`
7. Cliquez sur **CRÉER**
8. **Une fenêtre s'affiche avec le Client ID et le Client Secret** - copiez immédiatement les deux valeurs

#### Pour la production (recherche-jobs.fr) :

1. Allez dans **API et services** > **Identifiants**
2. Cliquez sur **+ CRÉER DES IDENTIFIANTS** > **ID client OAuth**
3. Type d'application : **Application Web**
4. Nom : **Ai Job Hunt - Production**
5. **Origines JavaScript autorisées** :
   - `https://recherche-jobs.fr`
   - `https://www.recherche-jobs.fr` (si vous utilisez le sous-domaine www)
6. **URI de redirection autorisés** :
   - `https://recherche-jobs.fr/connect/google/check`
   - `https://www.recherche-jobs.fr/connect/google/check` (si vous utilisez le sous-domaine www)
7. Cliquez sur **CRÉER**
8. **Une fenêtre s'affiche avec le Client ID et le Client Secret** :
   - Copiez immédiatement les deux valeurs
   - ⚠️ **Le secret ne sera plus affiché après fermeture de cette fenêtre**
   
   **Si vous avez fermé la fenêtre sans copier le secret :**
   - Retournez dans **API et services** > **Identifiants**
   - Cliquez sur le nom de votre client OAuth (ex: "Ai Job Hunt - Production")
   - Le **Client ID** est visible en permanence
   - Pour le **Client Secret**, vous devrez le régénérer :
     - Cliquez sur **Ajouter un secret** (ou **Réinitialiser le secret** si un ancien existe)
     - Copiez le nouveau secret généré

### 4. Configurer les variables d'environnement

#### En développement local :

1. Créez un fichier `.env.local` à la racine du projet (s'il n'existe pas déjà)
2. Ajoutez les variables suivantes avec vos identifiants **de développement** :

```env
GOOGLE_CLIENT_ID=votre_client_id_dev.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=votre_client_secret_dev
```

⚠️ **Important** : Ne committez JAMAIS le fichier `.env.local` dans Git !

#### En production :

Sur votre serveur de production, configurez les variables d'environnement avec les identifiants **de production** :

```env
GOOGLE_CLIENT_ID=votre_client_id_prod.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=votre_client_secret_prod
```

**Méthodes de configuration selon votre hébergement :**
- **Variables d'environnement du serveur** (recommandé)
- **Fichier `.env.local`** sur le serveur (non committé, non versionné)

### 5. Vérifier l'installation

1. Démarrez le serveur Symfony :
   ```bash
   symfony server:start
   ```

2. Allez sur http://localhost:8000/login

3. Vous devriez voir un bouton "Continuer avec Google"

4. Cliquez dessus pour tester l'authentification

## Fonctionnement
Déploiement en production (recherche-jobs.fr)

### Checklist de déploiement :

- [ ] Créer les identifiants OAuth de production dans Google Cloud Console (voir section 3)
- [ ] Configurer les variables d'environnement sur le serveur de production :
  ```env
  GOOGLE_CLIENT_ID=votre_client_id_prod.apps.googleusercontent.com
  GOOGLE_CLIENT_SECRET=votre_client_secret_prod
  ```
- [ ] Vérifier que votre site est accessible en HTTPS (obligatoire pour OAuth)
- [ ] Tester la connexion Google sur https://recherche-jobs.fr/login

### Important :

- **Ne réutilisez PAS les identifiants de développement en production** pour des raisons de sécurité
- **HTTPS est obligatoire** : Google OAuth2 ne fonctionne pas en HTTP en production
- Les identifiants de dev et prod doivent être séparés et distincts
### Liaison de comptes

Si un utilisateur a déjà un compte avec le même email, son compte sera automatiquement lié à son compte Google lors de la première connexion Google.

## Production

Pour déployer en production, vous devrez :

1. Créer de nouveaux identifiants OAuth avec les vraies URLs de production
2. Ajouter les variables d'environnement sur le serveur :
   - Via les variables d'environnement du serveur
   - Ou via le fichier `.env.local` (non committé)

3. Mettre à jour les URI de redirection dans Google Cloud Console :
   - `https://votre-domaine.com/connect/google/check`

## Dépannage

### "Error 400: redirect_uri_mismatch"

- Vérifiez que l'URI de redirection dans Google Cloud Console correspond exactement à celle utilisée
- Format attendu : `http://localhost:8000/connect/google/check`

### "Access blocked: This app's request is invalid"

- Vérifiez que l'écran de consentement OAuth est correctement configuré
- Assurez-vous que les scopes `email` et `profile` sont autorisés

### L'utilisateur est créé mais le mot de passe est null

- C'est normal ! Les utilisateurs connectés via Google n'ont pas besoin de mot de passe
- Ils ne peuvent se connecter que via Google
- Pour permettre la connexion par email/mot de passe, l'utilisateur devra définir un mot de passe séparément (fonctionnalité à implémenter)
