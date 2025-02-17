# Module Vitals pour Craft CMS 5.x

Version actuelle : 1.0.0

## À propos

Ce module a été développé pour permettre la surveillance des mises à jour de Craft CMS et de ses plugins via un endpoint API sécurisé. Il est particulièrement utile pour les systèmes de monitoring externes qui doivent vérifier régulièrement si des mises à jour sont disponibles.

## Versionnage

Le module suit la convention de versionnage sémantique (SemVer) :
- MAJOR.MINOR.PATCH
  - MAJOR : Changements incompatibles avec les versions précédentes
  - MINOR : Ajout de fonctionnalités rétrocompatibles
  - PATCH : Corrections de bugs rétrocompatibles

## Structure de la réponse

La réponse de l'API contient les informations suivantes :
- `cms_version` : Version actuelle de Craft CMS installée
- `php_version` : Version de PHP du serveur
- `updates` : Objet contenant les informations de mises à jour
  - `pending_updates` : Liste des mises à jour disponibles
  - `security_updates` : Liste des mises à jour de sécurité

## Caractéristiques principales

- 🔒 Endpoint sécurisé par token
- 🔄 Détection automatique des mises à jour via `getHasReleases`
- 🚨 Identification des mises à jour de sécurité
- 📝 Logging détaillé des opérations
- 🛡️ Gestion robuste des erreurs
- 🔍 Détection améliorée des mises à jour des plugins
- ⚡ Rate limiting configurable
- 🔐 Validation de token renforcée

## Prérequis

- Craft CMS 5.x
- PHP 8.2 ou supérieur
- Accès aux variables d'environnement
- Permissions suffisantes pour vérifier les mises à jour

## Installation

1. Installez le module via Composer :
```bash
composer require votre-vendor/craft-vitals
```

2. Ajoutez la configuration dans `config/app.php` :
```php
use craft\helpers\App;
use modules\vitals\Module;

return [
    'modules' => [
        'vitals' => Module::class
    ],
    'bootstrap' => ['vitals']
];
```

3. Configurez les variables d'environnement dans `.env` :
```env
# Token de sécurité pour l'endpoint de vérification
VITALS_TOKEN="votre_token_secret"

# Limite de requêtes par heure (défaut: 60)
VITALS_RATE_LIMIT=60

# Longueur minimale du token (défaut: 32)
VITALS_MIN_TOKEN_LENGTH=32
```

4. Videz les caches :
```bash
php craft clear-caches/all
```

## Configuration détaillée

### Token de sécurité

Le token doit être :
- Unique et complexe
- Au moins 32 caractères (configurable via `VITALS_MIN_TOKEN_LENGTH`)
- Stocké de manière sécurisée

Exemple de génération de token :
```php
echo bin2hex(random_bytes(32));
```

### Rate Limiting

Le rate limiting est configurable via la variable d'environnement `VITALS_RATE_LIMIT` :
- Valeur par défaut : 60 requêtes par heure
- Basé sur l'adresse IP du client
- Utilise le cache de Craft pour le stockage

### Logs

Les logs sont écrits dans `storage/logs/web.log` avec différents niveaux :
- `info` : Opérations normales, détails des vérifications de mises à jour
- `warning` : Tentatives d'accès invalides, plugins sans méthode getHasReleases
- `error` : Erreurs techniques

## Utilisation

### Vérification des mises à jour

```bash
curl "https://votre-site.com/vitals/updates?token=votre_token_secret"
```

### Réponses possibles

1. Aucune mise à jour :
```json
{
    "cms_version": "5.0.0",
    "php_version": "8.2.0",
    "updates": {
        "pending_updates": {},
        "security_updates": {}
    }
}
```

2. Mises à jour disponibles :
```json
{
    "cms_version": "5.0.0",
    "php_version": "8.2.0",
    "updates": {
        "pending_updates": {
            "craft": {
                "current": "5.0.0",
                "latest": "5.0.1"
            },
            "plugin-example": {
                "current": "1.0.0",
                "latest": "1.1.0"
            }
        },
        "security_updates": {}
    }
}
```

3. Erreur d'authentification :
```json
{
    "error": "Accès refusé : token invalide ou manquant",
    "code": "INVALID_TOKEN"
}
```

4. Erreur interne :
```json
{
    "error": "Une erreur est survenue lors de la récupération des mises à jour",
    "code": "INTERNAL_ERROR"
}
```

## Dépannage

### Problèmes courants

1. Erreur 403 (INVALID_TOKEN)
   - Vérifiez le token dans `.env`
   - Assurez-vous que le token respecte la longueur minimale
   - Vérifiez que le token est correctement transmis dans l'URL

2. Erreur 500 (INTERNAL_ERROR)
   - Vérifiez les logs dans `storage/logs/web.log`
   - Activez le mode debug dans `.env`

3. Mises à jour non détectées
   - Vérifiez que les plugins supportent la méthode `getHasReleases`
   - Consultez les logs pour voir les méthodes disponibles
   - Assurez-vous que le plugin est correctement installé

### Activation du mode debug

Dans `.env` :
```env
CRAFT_DEBUG=true
CRAFT_DEV_MODE=true
CRAFT_LOG_LEVEL=debug
```

## Sécurité

### Bonnes pratiques

1. Token
   - Changez régulièrement le token
   - Utilisez un token unique par environnement
   - Ne partagez jamais le token en clair

2. Accès
   - Limitez l'accès à l'endpoint aux IPs nécessaires
   - Surveillez les tentatives d'accès invalides
   - Utilisez HTTPS en production

3. Monitoring
   - Configurez des alertes pour les mises à jour de sécurité
   - Vérifiez régulièrement les logs
   - Mettez à jour rapidement en cas de correctif de sécurité

## Support

### Ressources

- [Documentation Craft CMS](https://craftcms.com/docs/5.x/)
- [API Craft CMS](https://docs.craftcms.com/api/v5/)
- [Gestion des mises à jour](https://craftcms.com/docs/5.x/update.html)

### Contact

Pour toute question ou problème :
1. Consultez les logs détaillés
2. Vérifiez la documentation
3. Ouvrez une issue sur GitHub

## Contribution

Les contributions sont les bienvenues ! Pour contribuer :
1. Forkez le projet
2. Créez une branche pour votre fonctionnalité
3. Soumettez une pull request

## Licence

Ce module est distribué sous licence MIT. Voir le fichier LICENSE pour plus de détails. 