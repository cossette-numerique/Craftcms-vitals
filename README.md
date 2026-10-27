# Craft Vitals

Module de surveillance des mises à jour pour Craft CMS.

## Installation

1. Copiez le dossier `vitals` dans le dossier `modules` de votre projet Craft CMS.

2. Enregistrez le module dans `config/app.php` :

```php
'modules' => [
    'vitals' => \modules\vitals\Module::class,
],
'bootstrap' => ['vitals'],
```

3. Ajoutez un token unique par environnement dans le fichier `.env` (ne le commitez pas) :

```
VITALS_TOKEN=votre-token-ici
```

Pour générer un token : `openssl rand -hex 32`

## Utilisation

Le token s'envoie dans l'en-tête `X-Vitals-Token`, jamais dans l'URL :

```bash
curl -H "X-Vitals-Token: votre-token-ici" {URL_DU_BACKEND}/vitals/updates
```

| Cas | Réponse |
|---|---|
| Bon token dans l'en-tête | `200` : `cms_version`, `php_version`, `updates` |
| En-tête absent, token invalide ou `VITALS_TOKEN` vide | `403` `INVALID_TOKEN` |
| Paramètre `?token=` présent dans l'URL | `400` `TOKEN_IN_QUERY` |

Toutes les réponses portent `Cache-Control: no-store`.
