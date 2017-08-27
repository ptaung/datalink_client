<?php

namespace app\modules\client\controllers;

use yii\web\Controller;
use app\modules\client\models\ConfigForm;

class ConfigController extends Controller {

    public function actionIndex() {
        $params = require(\Yii::getAlias('@app') . '\config\params.php');

        $model = new ConfigForm();
        if (isset($_POST['ConfigForm'])) {
            $model->setAttributes($_POST['ConfigForm']);
        } else {
            $model->setAttributes($params);
        }
        if (isset($_POST['ConfigForm']) && $model->validate()) {
            $config = $_POST['ConfigForm'];
            file_put_contents($file, $str);
            $model->setAttributes($config);
        }
        #echo '<pre>';
        #print_r($params);
        #echo '</pre>';
        $this->render('index', array('model' => $model));
    }

}
