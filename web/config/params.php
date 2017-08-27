<?php
$base = dirname(__FILE__). '/../../';
$ini = parse_ini_file($base."datalink-config.ini",true);
return $ini['webservice'];/*
return [
    'webService_baseUrl' => 'https://122.154.74.220/datalink/web/index.php',
    'webService_clientId' => '',
    'webService_clientSecret' => '',
    'hospital_hospcode' => '',
];
*/
