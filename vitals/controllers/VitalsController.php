<?php
namespace modules\vitals\controllers;

use Craft;
use craft\helpers\App;
use craft\web\Controller;
use yii\web\Response;
use modules\vitals\Module;

class VitalsController extends Controller
{
    public const TOKEN_HEADER = 'X-Vitals-Token';

    protected array|bool|int $allowAnonymous = ['updates'];

    public function actionUpdates(): Response
    {
        $request = Craft::$app->getRequest();

        // Le token ne doit jamais transiter dans l'URL (logs serveur, proxy, APM)
        if ($request->getQueryParam('token') !== null) {
            return $this->noStore($this->asJson([
                'error' => 'Le token doit être envoyé dans l\'en-tête ' . self::TOKEN_HEADER . ', pas dans l\'URL',
                'code' => 'TOKEN_IN_QUERY'
            ])->setStatusCode(400));
        }

        $providedToken = $request->getHeaders()->get(self::TOKEN_HEADER);
        $expectedToken = App::env('VITALS_TOKEN');

        if (
            !is_string($providedToken) || $providedToken === '' ||
            !is_string($expectedToken) || $expectedToken === '' ||
            !hash_equals($expectedToken, $providedToken)
        ) {
            return $this->noStore($this->asJson([
                'error' => 'Accès refusé : token invalide ou manquant',
                'code' => 'INVALID_TOKEN'
            ])->setStatusCode(403));
        }

        try {
            $updates = Craft::$app->updates->getUpdates(true);

            if (!$updates) {
                throw new \Exception('Impossible de récupérer les mises à jour');
            }

            $response = [
                'cms_version' => Craft::$app->getVersion(),
                'php_version' => PHP_VERSION,
                'updates' => [
                    'pending_updates' => [],
                    'security_updates' => []
                ]
            ];

            // Vérification du CMS
            if ($updates->cms->hasReleases) {
                $latestVersion = end($updates->cms->releases);

                $coreUpdate = [
                    'current' => Craft::$app->getVersion(),
                    'latest' => $latestVersion->version
                ];

                $response['updates']['pending_updates']['craft'] = $coreUpdate;

                if ($updates->cms->hasCritical) {
                    $response['updates']['security_updates']['craft'] = $coreUpdate;
                }
            }

            // Vérification des plugins
            foreach ($updates->plugins as $handle => $pluginUpdate) {
                if ($pluginUpdate->hasReleases) {
                    $plugin = Craft::$app->getPlugins()->getPlugin($handle);
                    if (!$plugin) continue;

                    $latestVersion = end($pluginUpdate->releases);

                    $pluginData = [
                        'current' => $plugin->getVersion(),
                        'latest' => $latestVersion->version
                    ];

                    $response['updates']['pending_updates'][$handle] = $pluginData;

                    if ($pluginUpdate->hasCritical) {
                        $response['updates']['security_updates'][$handle] = $pluginData;
                    }
                }
            }

            return $this->noStore($this->asJson($response));
        } catch (\Throwable $e) {
            Craft::error('Erreur lors de la récupération des mises à jour : ' . $e->getMessage(), __METHOD__);

            return $this->noStore($this->asJson([
                'error' => 'Une erreur est survenue lors de la récupération des mises à jour',
                'code' => 'INTERNAL_ERROR',
                'message' => YII_DEBUG ? $e->getMessage() : null
            ])->setStatusCode(500));
        }
    }

    // Réponses jamais mises en cache par un CDN ou un proxy
    private function noStore(Response $response): Response
    {
        $response->getHeaders()->set('Cache-Control', 'no-store');
        return $response;
    }
}
