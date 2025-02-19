# Module Vitals pour Craft CMS 4.x/5.x

Module de vérification des mises à jour pour Craft CMS via un endpoint API sécurisé.

## Installation Rapide

1. **Copier les fichiers**
   Copiez le dossier `src` dans `modules/vitals/` de votre projet Craft CMS.

2. **Configurer config/app.php**
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

3. **Configurer .env**
   ```env
   VITALS_TOKEN="votre_token_secret"
   ```

4. **Vider les caches**
   ```bash
   php craft cache/flush-all
   ```

## Utilisation

Vérifier les mises à jour :
```bash
curl "https://votre-site.com/vitals/updates?token=votre_token_secret"
```

## Réponses

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

## Prérequis

- Craft CMS 4.x ou 5.x
- PHP 8.0 ou supérieur

## Support

En cas de problème :
1. Vérifiez les logs dans `storage/logs/web.log`
2. Activez le mode debug dans `.env` 