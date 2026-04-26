# Configuration RapidAPI pour JSearch et Indeed

## 📝 Obtenir votre clé RapidAPI

1. **Créer un compte sur RapidAPI:**
   - Allez sur https://rapidapi.com/
   - Créez un compte gratuit

2. **S'abonner aux APIs:**
   
   **JSearch API (Recommandé - Agrège LinkedIn, Indeed, Google Jobs):**
   - URL: https://rapidapi.com/letscrape-6bRBa3QguO5/api/jsearch
   - Cliquez sur "Subscribe to Test"
   - Choisissez le plan gratuit (limites: 2500 requêtes/mois!)
   - **C'est la meilleure option car elle agrège plusieurs sources**
   
   **Indeed Jobs Search API (Optionnel):**
   - URL: https://rapidapi.com/letscrape-6bRBa3QguO5/api/indeed12
   - Cliquez sur "Subscribe to Test"
   - Choisissez le plan gratuit (limites: 100 requêtes/mois)

3. **Récupérer votre clé API:**
   - Une fois abonné, allez sur la page de l'API
   - Dans l'onglet "Endpoints", vous verrez votre clé dans la section "Header Parameters"
   - Copiez la valeur de `X-RapidAPI-Key`

4. **Configurer dans le projet:**
   - Ouvrez le fichier `.env` à la racine du projet
   - Remplacez la ligne `RAPIDAPI_KEY=` par `RAPIDAPI_KEY=votre_cle_ici`

## 🚀 Utilisation

### Commandes disponibles:

**Fetch JSearch (LinkedIn + Indeed + Google Jobs):**
```bash
php bin/console app:jobs:fetch-jsearch
php bin/console app:jobs:fetch-jsearch --query="php developer" --location="Paris"
```

**Fetch Indeed:**
```bash
php bin/console app:jobs:fetch-indeed
php bin/console app:jobs:fetch-indeed --query="symfony developer" --location="France"
```

**Fetch Welcome to the Jungle:**
```bash
php bin/console app:jobs:fetch-wttj --query="developpeur php" --city="Paris"
```

**Fetch TOUTES les sources en une commande:**
```bash
php bin/console app:jobs:fetch-all --query="php developer" --location="Paris"
```

### Fetch automatique dans l'interface:

Sur la page `/jobs`, quand vous tapez une ville et cliquez sur "Filtrer", le système fetch automatiquement depuis WTTJ, JSearch ET Indeed.

## ⚠️ Limites des plans gratuits

- **JSearch:** 2500 requêtes/mois (très généreux!)
- **Indeed Jobs Search:** 100 requêtes/mois

Pour plus de requêtes, consultez les plans payants sur RapidAPI.

## 🔍 Sources de jobs disponibles

- **WTTJ (Welcome to the Jungle):** Gratuit, pas de limite
- **JSearch:** Agrège LinkedIn, Indeed, Google Jobs (2500 req/mois gratuit)
- **Indeed:** API directe (100 req/mois gratuit)
