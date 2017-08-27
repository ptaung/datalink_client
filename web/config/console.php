<?php

date_default_timezone_set('Asia/Bangkok');
ini_set("max_execution_time", -1);
ini_set('memory_limit', '4048M');
$params = require(__DIR__ . '/params.php');
$db = require(__DIR__ . '/db.php');
$db_datacenter = require(__DIR__ . '/db_datacenter.php');
$config = [
    'id' => 'basic-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log', 'client'],
    'controllerNamespace' => 'app\commands',
    'modules' => [
        'client' => ['class' => 'app\modules\client\Module',],
    #'ws' => ['class' => 'app\modules\ws\Module',],
    ],
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'log' => [
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        'db_datacenter' => $db_datacenter,
    ],
    'params' => $params,
        /*
          'controllerMap' => [
          'fixture' => [ // Fixture generation command line.
          'class' => 'yii\faker\FixtureController',
          ],
          ],
         */
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
    ];
}

return $config;
