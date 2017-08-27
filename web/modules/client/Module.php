<?php

namespace app\modules\client;

/**
 * ws module definition class
 */
use Yii;
use yii\base\BootstrapInterface;
use yii\base\Module as BaseModule;

class Module extends BaseModule implements BootstrapInterface {

    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'app\modules\client\controllers';

    /**
     * @inheritdoc
     */
    public function init() {
        parent::init();
    }

    public function bootstrap($app) {
        if ($app instanceof \yii\console\Application) {
            $this->controllerNamespace = 'app\modules\client\commands';
        }
    }

}
