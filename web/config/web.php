<?php

/*
 * พัฒนาโดย ศิลา กลั่นแกล้ว สสจ.สุพรรณบุรี
 *
 */
date_default_timezone_set('Asia/Bangkok');
ini_set("max_execution_time", -1);
ini_set("memory_limit", '4048M');
#ini_set("max_input_vars", 10000);
$params = require(__DIR__ . '/params.php');

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    #'bootstrap' => ['log', 'oauth2'],#server
    'bootstrap' => ['log'], #client
    'language' => 'th_TH',
    'modules' => [
        'gridview' => [
            'class' => '\kartik\grid\Module'
        ],
        #server-------------------------------------------------
        /*
          'user' => [
          'class' => 'dektrium\user\Module',
          'admins' => ['admin', 'sila'],
          'enableUnconfirmedLogin' => true,
          ],

          'ws' => ['class' => 'app\modules\ws\Module',],
         *
         */
        'client' => ['class' => 'app\modules\client\Module',],
    #server-------------------------------------------------
    /*
      'oauth2' => [
      'class' => 'filsh\yii2\oauth2server\Module',
      'tokenParamName' => 'accessToken',
      'tokenAccessLifetime' => 3600 * 24,
      'storageMap' => [
      'user_credentials' => 'app\models\OauthUser',
      #'public_key' => 'restapi\storage\PublicKeyStorage',
      #'access_token' => 'restapi\storage\JwtAccessToken',
      ],
      'grantTypes' => [
      'authorization_code' => [ 'class' => 'OAuth2\GrantType\AuthorizationCode'],
      'client_credentials' => [ 'class' => 'OAuth2\GrantType\ClientCredentials'],
      'user_credentials' => ['class' => 'OAuth2\GrantType\UserCredentials',],
      'refresh_token' => [
      'class' => 'OAuth2\GrantType\RefreshToken',
      'always_issue_new_refresh_token' => true
      ]
      ]
      ]
     *
     */
    ],
    'components' => [
        /*
          'response' => [
          'format' => yii\web\Response::FORMAT_JSON,
          'charset' => 'UTF-8',
          ],

          'phpNetHttp' => [
          'class' => 'yii\httpclient\Client',
          'baseUrl' => 'http://127.0.0.1',
          ],
         * *
         */
        'view' => [
            'theme' => [
                'pathMap' => [
                    '@dektrium/user/views' => '@app/views/user'
                ],
            ],
        ],
        'authClientCollection' => [
            'class' => yii\authclient\Collection::className(),
            'clients' => [
                'moph' => [
                    'class' => 'app\auth\MophOAuth',
                    'clientId' => 'wmcservice',
                    'clientSecret' => '50f05d328b7ad23605b0838f9d72cb3f',
                ],
            ],
        ],
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'rN905dGVig49PbcPQMUaFyUPLmjfuJsF',
            'enableCookieValidation' => false,
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\User', #client
            #'identityClass' => 'app\models\OauthUser', #server
            'autoRenewCookie' => true,
            'authTimeout' => 60,
            #'enableSession' => false,
            'enableAutoLogin' => false,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            // send all mails to a file by default. You have to set
            // 'useFileTransport' to false and configure a transport
            // for the mailer to send real emails.
            'useFileTransport' => true,
        ],
        'log' => [

            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                    'logFile' => '@runtime/logs/http-request.log',
                    'categories' => ['yii\httpclient\*'],
                ],
            ],
        ],
        'db' => require(__DIR__ . '/db.php'),
        #'db_datacenter' => require(__DIR__ . '/db_datacenter.php'),#server
        /*
          'urlManager' => [
          'enablePrettyUrl' => true,
          'showScriptName' => false,
          'rules' => [
          ],
          ],
         */
        'urlManager' => [
            'enablePrettyUrl' => true, //only if you want to use petty URLs
            'showScriptName' => true,
            'rules' => [
            #server-------------------------------------------------
            /*
              ['class' => 'yii\rest\UrlRule', 'controller' => 'ws/api'],
              ['class' => 'yii\rest\UrlRule', 'controller' => 'ws/wdc'],
              'POST oauth2/<action:\w+>' => 'oauth2/rest/<action>',
             *
             */
            ]
        ]
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    #$config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
    ];
}

return $config;
