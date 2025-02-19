<?php
namespace modules\vitals;

use Craft;
use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use yii\base\Event;
use yii\base\Module as BaseModule;

class Module extends BaseModule
{
    public const VERSION = '1.0.1';

    public function init(): void
    {
        parent::init();
        
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            $this->controllerNamespace = 'modules\\vitals\\console\\controllers';
        } else {
            $this->controllerNamespace = 'modules\\vitals\\controllers';
        }

        Craft::setAlias('@modules/vitals', __DIR__);
        
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function(RegisterUrlRulesEvent $event) {
                $event->rules['vitals/updates'] = 'vitals/vitals/updates';
            }
        );

        Craft::info(
            'Module Vitals initialisé (version ' . self::VERSION . ')',
            __METHOD__
        );
    }
} 