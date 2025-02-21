<?php
namespace modules\vitals;

use Craft;
use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use yii\base\Event;
use yii\base\Module as BaseModule;

class Module extends BaseModule
{
    public static function config(): array
    {
        return [
            'components' => [],
        ];
    }

    public function init()
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
    }
} 