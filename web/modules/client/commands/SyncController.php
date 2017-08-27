<?php

namespace app\modules\client\commands;

use app\modules\client\components\CSync;
use Yii;

class SyncController extends CSync {

    public function init() {
        $this->baseUrl = Yii::$app->params['webService_baseUrl'];
        $this->hcode = Yii::$app->params['hospital_hospcode'];
    }

}
