<?php

namespace app\modules\client\models;

use Yii;
use yii\base\Model;

class ConfigForm extends Model {

    public function rules() {
        return [
            [['webService_baseUrl', 'webService_clientId', 'webService_clientSecret', 'hospital_hospcode'], 'required'],
        ];
    }

    /**
     * @return array customized attribute labels
     */
    public function attributeLabels() {
        return [
            'webService_baseUrl' => 'http://',
            'webService_clientId' => 'wmservice',
            'webService_clientSecret' => 'qw2267er',
            'hospital_hospcode' => '08264',
        ];
    }

}
