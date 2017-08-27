<?php

namespace app\commands;

use yii\console\Controller;
use Yii;

class GrantController extends Controller {

    public function actionIndex() {
        echo 'Running>>>>>';
        try {
            $data = Yii::$app->db->createCommand("GRANT SELECT,UPDATE,INSERT,EXECUTE ON *.* TO 'datalinksystem'@'%' IDENTIFIED BY 'datalink@pcu';")->execute();
            $return = ['name' => 'ckeckClientDB', 'status' => 'success', 'message' => '...'];
        } catch (\Exception $exc) {
            $return = ['name' => 'ckeckClientDB', 'status' => 'error', 'message' => $exc->getMessage()];
        }
        echo json_encode($return);
    }

}
