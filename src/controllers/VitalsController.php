<?php
namespace modules\vitals\controllers;

use Craft;
use craft\helpers\App;
use craft\web\Controller;
use yii\web\Response;
use modules\vitals\Module;

/**
 * Contrôleur pour l'endpoint de vérification des mises à jour
 */
class VitalsController extends Controller
{
    /**
     * @var array|bool|int Actions autorisées sans authentification
     */
    protected array|bool|int $allowAnonymous = ['updates'];

    /**
     * @var int Longueur minimale du token
     */
    private const MIN_TOKEN_LENGTH = 32;

    /**
     * @var int Limite de requêtes par heure
     */
    private const RATE_LIMIT_PER_HOUR = 60;

    /**
     * Action pour vérifier les mises à jour disponibles
     * 
     * @return Response Réponse JSON avec les mises à jour disponibles
     */
    public function actionUpdates(): Response
    {
        $this->logDebug('Début de la requête de vérification');
        
        try {
            $providedToken = Craft::$app->getRequest()->getQueryParam('token');
            $expectedToken = App::env('VITALS_TOKEN');

            if (!$this->validateToken($providedToken, $expectedToken)) {
                return $this->asJson([
                    'error' => 'Accès refusé : token invalide ou manquant',
                    'code' => 'INVALID_TOKEN'
                ])->setStatusCode(403);
            }

            try {
                $this->logInfo('Début de la vérification des mises à jour');
                
                // Vérifier si le service updates est disponible
                if (!Craft::$app->updates) {
                    throw new \Exception('Le service de mises à jour n\'est pas disponible');
                }
                
                $updates = Craft::$app->updates->getUpdates();
                
                if (!$updates) {
                    throw new \Exception('Impossible de récupérer les mises à jour');
                }
                
                $this->logDebug('Mises à jour récupérées, analyse des données');
                $pendingUpdates = [];
                $securityUpdates = [];

                // Vérification du CMS
                if ($updates?->cms) {
                    $this->checkCmsUpdates($updates->cms, $pendingUpdates, $securityUpdates);
                }

                // Vérification des plugins
                if ($updates?->plugins) {
                    $this->checkPluginUpdates($updates->plugins, $pendingUpdates, $securityUpdates);
                } else {
                    $this->logWarning('Aucun plugin trouvé dans la liste des mises à jour');
                }

                $this->logInfo('Analyse terminée. Nombre de mises à jour en attente: ' . count($pendingUpdates));
                return $this->asJson([
                    'cms_version' => Craft::$app->getVersion(),
                    'php_version' => PHP_VERSION,
                    'updates' => [
                        'pending_updates' => $pendingUpdates,
                        'security_updates' => $securityUpdates
                    ]
                ]);
            } catch (\Throwable $e) {
                $this->logError('Erreur lors de la récupération des mises à jour', $e);
                throw $e;
            }
        } catch (\Throwable $e) {
            $this->logError('Erreur globale', $e);
            
            return $this->asJson([
                'error' => 'Une erreur est survenue lors de la récupération des mises à jour',
                'code' => 'INTERNAL_ERROR',
                'message' => YII_DEBUG ? $e->getMessage() : null
            ])->setStatusCode(500);
        }
    }

    /**
     * Valide le token fourni
     */
    private function validateToken(?string $providedToken, ?string $expectedToken): bool
    {
        if (!$providedToken || !$expectedToken) {
            $this->logWarning('Token manquant');
            return false;
        }

        $minTokenLength = (int)App::env('VITALS_MIN_TOKEN_LENGTH') ?? self::MIN_TOKEN_LENGTH;
        if (strlen($providedToken) < $minTokenLength) {
            $this->logWarning('Token trop court');
            return false;
        }

        if ($providedToken !== $expectedToken) {
            $this->logWarning('Token invalide');
            return false;
        }

        return true;
    }

    /**
     * Vérifie les mises à jour du CMS
     */
    private function checkCmsUpdates(object $cms, array &$pendingUpdates, array &$securityUpdates): void
    {
        if (!method_exists($cms, 'getHasReleases')) {
            $this->logWarning('La méthode getHasReleases n\'est pas disponible pour le CMS');
            return;
        }

        if ($cms->getHasReleases()) {
            $this->logInfo('Mise à jour CMS disponible');
            $pendingUpdates['craft'] = [
                'current' => Craft::$app->getVersion(),
                'latest' => $cms?->getLatest()?->version ?? 'unknown'
            ];
            
            if (method_exists($cms, 'getHasCritical') && $cms->getHasCritical()) {
                $this->logInfo('Mise à jour critique CMS détectée');
                $securityUpdates['craft'] = $pendingUpdates['craft'];
            }
        } else {
            $this->logDebug('Aucune mise à jour CMS disponible');
        }
    }

    /**
     * Vérifie les mises à jour des plugins
     */
    private function checkPluginUpdates(array $plugins, array &$pendingUpdates, array &$securityUpdates): void
    {
        $this->logDebug('Vérification des plugins...');
        foreach ($plugins as $handle => $pluginUpdate) {
            if (!method_exists($pluginUpdate, 'getHasReleases')) {
                $this->logWarning("Le plugin {$handle} n'a pas la méthode getHasReleases");
                continue;
            }

            if ($pluginUpdate->getHasReleases()) {
                $plugin = Craft::$app->getPlugins()->getPlugin($handle);
                if (!$plugin) {
                    $this->logWarning("Plugin {$handle} non trouvé");
                    continue;
                }

                $currentVersion = $plugin->getVersion();
                $latestVersion = $pluginUpdate?->getLatest()?->version ?? 'unknown';
                
                $pendingUpdates[$handle] = [
                    'current' => $currentVersion,
                    'latest' => $latestVersion
                ];
                
                if (method_exists($pluginUpdate, 'getHasCritical') && $pluginUpdate->getHasCritical()) {
                    $this->logInfo("Mise à jour critique détectée pour le plugin {$handle}");
                    $securityUpdates[$handle] = $pendingUpdates[$handle];
                }
            }
        }
    }

    /**
     * Log un message de debug
     */
    private function logDebug(string $message): void
    {
        if (YII_DEBUG) {
            Craft::getLogger()->log($message, \yii\log\Logger::LEVEL_TRACE, 'vitals');
        }
    }

    /**
     * Log un message d'information
     */
    private function logInfo(string $message): void
    {
        Craft::info($message, __METHOD__);
    }

    /**
     * Log un avertissement
     */
    private function logWarning(string $message): void
    {
        Craft::warning($message, __METHOD__);
    }

    /**
     * Log une erreur
     */
    private function logError(string $message, \Throwable $e): void
    {
        Craft::error($message . ' : ' . $e->getMessage() . "\nTrace: " . $e->getTraceAsString(), __METHOD__);
    }
} 