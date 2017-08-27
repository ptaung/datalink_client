<?php
$base = dirname(__FILE__). '/../../';
$ini = parse_ini_file($base."datalink-config.ini",true);
return $ini['database'];
/*
  return [
  'class' => 'yii\db\Connection',
  'dsn' => 'mysql:host=127.0.0.1;dbname=hosxp_pcu',
  'username' => 'datalinksystem',
  'password' => 'datalink@pcu',
  'charset' => 'utf8',
  ];
*/
