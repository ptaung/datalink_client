<?php

namespace app\modules\client\controllers;

use yii\web\Controller;
use yii\data\ArrayDataProvider;

/**
 * Default controller for the `ws` module
 */
class DefaultController extends Controller {

    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex() {
        return $this->render('index');
    }

    public function actionGetlogprocess() {
        if (file_exists("../log/logprocess.txt"))
            $data = @file("../log/logprocess.txt");
        if (file_exists("../../logs/logprocess.txt"))
            $data = @file("../../logs/logprocess.txt");
        #krsort($data);
        #foreach ($data as $value) {
        #$rows[]['label'] = $value;
        #}
        #echo '<pre>';
        #print_r($rows);
        #echo '</pre>';
        #exit;
        /*
          $dataProvider = new ArrayDataProvider([
          'allModels' => $rows,
          #'sort' => [
          #'attributes' => $attributes,
          #],
          'pagination' => [
          'pageSize' => 30,
          ],
          ]);
         *
         */
        echo $data[0];
    }

}
