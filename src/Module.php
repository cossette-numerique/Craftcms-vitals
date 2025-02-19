<?php
namespace modules\vitals;

use Craft;
use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use yii\base\Event;
use yii\base\Module as BaseModule;

/**
 * Module de vérification des mises à jour pour Craft CMS 5.x
 * 
 * @property-read null|object $vitals
 */
class Module extends BaseModule
{
    /**
     * Version du module
     * Format: MAJOR.MINOR.PATCH
     */
    public const VERSION = '1.0.1';

    public function init(): void
    {
        parent::init();
        
        // Set the controllerNamespace based on whether this is a console or web request
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            $this->controllerNamespace = 'modules\\vitals\\console\\controllers';
        } else {
            $this->controllerNamespace = 'modules\\vitals\\controllers';
        }

        // Register module with the application
        Craft::setAlias('@modules/vitals', __DIR__);
        
        // Register URL rules
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function(RegisterUrlRulesEvent $event) {
                $event->rules['vitals/updates'] = 'vitals/vitals/updates';
            }
        );

        // Log module initialization
        Craft::info(
            'Module Vitals initialisé (version ' . self::VERSION . ')',
            __METHOD__
        );
    }
} 