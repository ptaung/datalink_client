<?php

namespace app\modules\client\components;

use yii\console\Controller;
use app\modules\client\components\ExtClient;
#use yii\web\Cookie;
use Yii;

/* 2.0.1
 * 2.0.2
 * - update count table client
 * 2.0.3
 * - fix bug upload
 * 2.0.4
 * - diable start on web
 */

class CSync extends Controller {

    public $baseUrl = '';
    public $connect; #new Client
    public $token = '';
    public $hcode;
    public $userAgen = 'DATALINK-SERVICE 2.0.4 Client'; //เปลี่ยนทุกครั้งที่ Update
    public $tableSyncData = []; #เก็บข้อมูลที่ต้อง Sync
    public $tableSynctGroup = []; #เก็บข้อมูลตารางที่ต้องการ Sync แบบที่ละตาราง
    public $datatable;
    public $loop = 5000;
    public $splitLimit = 5000; # split data order to small for upload;
    public $tableSynctMode = ''; #1 = service ,2 = basic , empty = all

#-----------------------------------------------------------------------------------------

    public function actionStart() {
        $this->logProcess(); #logprocess => .txt
        $this->connect(); #เชื่อมต่อ api เพื่อรับ token
        $this->wmcCheck(); #ตรวจสอบโปรแกรม
        $this->flagsStatus(1); #Start
        $this->ckeckNode(); #ตรวจสอบฐานข้อมูลที่ Server
        $this->ckeckClientDB(); #ตรวจสอบฐานข้อมูลที่ Client
        echo "\n";
        #$this->tableSynctGroup = ['vn_stat'];
        #$this->tableSynctMode = 1;
        $this->getSyncList(); #รับข้อมูลตารางที่ต้อง Sync จาก Server
        $this->checkTable(); #ตรวจสอบข้อมูลที่ Client ต้อง Sync เพิ่ม
        $this->flagsStatus(2); #Checksum
        $tableMustToSync = $this->checkSumClient();
        $this->flagsStatus(3); #Upload
        if (count($tableMustToSync) > 0) {
            echo "Count number of table for sync is " . count($tableMustToSync);
            $this->operQueryData($tableMustToSync); #query data
        }
        $this->flagsStatus(4); #Finish
        echo "\nFINISH:" . date("Y-m-d H:i:s");
    }

    public function flagsStatus($status, $data = []) {
        #{1=start,2=checksum,3=upload,4=finish}
        $request = $this->connect->post('ws/wdc/flags', ['hcode' => $this->hcode, 'status' => $status, 'data' => $data]);
        $request->addHeaders(['Authorization' => 'Bearer ' . $this->token]);
        $response = $request->send();
    }

    public function wmcCheck() {
        $dir = Yii::getAlias('@app') . "\modules\client\\";
        $sha1 = $this->hashDirectory($dir);
        $request = $this->connect->post('ws/wdc/online', ['checkDir' => $sha1]);
        $request->addHeaders(['Authorization' => 'Bearer ' . $this->token]);
        $response = $request->send();
        if ($response) {

        }
        echo $sha1;
    }

    /*
      public function actionGettoken() {
      $client = new ExtClient();
      $response = $client->createRequest()
      ->setMethod('post')
      ->setUrl('http://localhost/auth/web/oauth2/token')
      ->setData(['grant_type' => 'password',
      'client_id' => 'testclient',
      'client_secret' => '',
      'username' => '',
      'password' => '',
      ])
      ->send();
      if ($response->isOk) {
      #echo $response . "\n";
      echo $response->content;
      $data = json_decode($response->content);
      echo $data->access_token;
      } else {
      echo $response;
      }
      }
     */

    public function connect() {
        $http = new ExtClient([
            'transport' => 'yii\httpclient\CurlTransport',
            'baseUrl' => $this->baseUrl,
            'requestConfig' => [
                'headers' => [
                    'User-Agent' => $this->userAgen
                ]
            ]
        ]);
        $request = $http->post('oauth2/token', [
            'grant_type' => 'client_credentials',
            'client_id' => Yii::$app->params['webService_clientId'],
            'client_secret' => Yii::$app->params['webService_clientSecret'],
        ]);

        $response = $request->send(); # POST Request ไป Server
        if ($response->isOk) {
            $data = $response->getData(); # รับค่าจาก Response to array
            $this->token = $data['access_token'];
            $this->connect = $http;
        } else {
            echo $response->getContent();
            exit;
        }
    }

    public function operCallDataClient($tableMustToSync = []) {#ดำเนินการเตรียมเลือกข้อมูลจาก Client
        $sqlQueryArr = $this->getSqlQueryString($tableMustToSync); #รับค่า SQLQUERY

        foreach ($sqlQueryArr as $table => $query) {
            $serverData = []; #$this->operCallDataServer($query); #รับค่า SQLQUERY
            $clientData = Yii::$app->db->createCommand(str_replace('dw_' . $this->hcode . '.', '', $query))->queryAll();
            $dataServer = [];
            foreach ($serverData as $key => $value) {#เปรียบเทียบค่าตรวจสอบจาก Server
                $dataServer[$value['dd']] = $value['ss'];
                $dataCServer[$value['dd']] = $value['cc'];
            }
            if (count($clientData) > 0) {
                foreach ($clientData as $key => $row) {
                    $dataCClient[$row['dd']] = $row['cc'];
                    if (count($dataServer) > 0) {
                        if (@$dataServer[$row['dd']] != $row['ss'])
                            $chk[] = array('dd' => $row['dd'], 'cc' => $row['cc']);
                    } else {
                        $chk[] = array('dd' => $row['dd'], 'cc' => $row['cc']);
                    }
                }

                #ค่าที่ต้องลบทิ้งจาก ​Server
                $diff = @array_diff_key($dataCServer, $dataCClient);
                foreach ((array) $diff as $k => $v) {
                    $chk[] = array('dd' => @$k, 'cc' => @$v);
                }

                #แบ่งกลุ่มเพื่อเพิ่มความเร็วในการส่ง Query
                if (count($chk) > 0)
                    $chk = $this->groupOrder($chk);
            }

            print_r($chk);


            echo "\n\n";
        }
    }

    public function getSqlQueryString($tableSyncData = []) { #ส่งคำสั่งไป run ที่ Server
        #echo count($tableSyncData);
        #exit;
        $request = $this->connect->post('ws/wdc/getsqlquerystring', ['hcode' => $this->hcode, 'tableSyncData' => $tableSyncData]);
        /*
          $request = $this->connect->createRequest()
          ->setMethod("post")
          ->setUrl('ws/wdc/getsqlquerystring')
          ->addOptions([
          CURLOPT_CONNECTTIMEOUT => 360, //5 connection timeout
          CURLOPT_TIMEOUT => 360, //10 data receiving timeout
          ])
          ->setData(['hcode' => $this->hcode, 'count' => count($tableSyncData), 'tableSyncData' => $tableSyncData, 'tableSyncData1' => []]);
         */

        $request->addHeaders(['Authorization' => 'Bearer ' . $this->token]);
        $response = $request->send();
        #print_r($response->getData());
        #exit;
        if ($response->isOk) {
            $data = $response->getData(); # รับค่าจาก Response to array
            return @$data['data']['rows'];
        } else {
            $return = $response->getContent();
            return $return;
        }
    }

    public function getDesc() { #ส่งคำสั่งไป run ที่ Server
        $request = $this->connect->post('ws/wdc/desc', ['hcode' => $this->hcode]);
        $request->addHeaders(['Authorization' => 'Bearer ' . $this->token]);
        $response = $request->send();
        if ($response->isOk) {
            $data = $response->getData(); # รับค่าจาก Response to array
            return @$data['data']['rows'];
        } else {
            $return = $response->getContent();
            return [];
        }
    }

    public function getAuthencode($http) {
        $request = $this->connect->post('ws/wdc/getsynclist', ['hcode' => $this->hcode]);
        $request->addHeaders(['Authorization' => 'Bearer ' . $this->token]);
        $response = $request->send();

        if ($response->isOk) {
            echo $response->content;
        } else {
            echo $response->getContent();
        }
        /*
          $request = $http->createRequest()
          ->setMethod('post')
          ->setData(['grant_type' => 'client_credentials',
          'client_id' => 'testclient',
          'client_secret' => 'testpass',
          ])
          ->setOptions([
          CURLOPT_CONNECTTIMEOUT => 1, //5 connection timeout
          CURLOPT_TIMEOUT => 1, //10 data receiving timeout
          CURLOPT_SSL_VERIFYHOST => 2,
          CURLOPT_SSL_VERIFYPEER => FALSE,
          ])
          ->send();
         */
    }

    public function getSyncList() {
        $request = $this->connect->post('ws/wdc/getsynclist', [
            'hcode' => $this->hcode,
            'table' => $this->tableSynctGroup,
            'synctype' => $this->tableSynctMode, #1 = service ,2 = basic , empty = all
        ]);
        $request->addHeaders(['Authorization' => 'Bearer ' . $this->token]);
        $response = $request->send();
        if ($response->isOk) {
            $data = $response->getData(); # รับค่าจาก Response to array
            $return = @$data['data']['numrow'];
            $this->tableSyncData = @$data['data']['rows'];
        } else {
            $return = $response->getContent();
        }

        echo json_encode($return);
#return $return;
    }

    public function checkTable() {
        $foundNullTable = 0;
        foreach ((array) $this->tableSyncData as $rows) {
            if ($rows['ny'] == 0) { // กรณีไม่พบตารางที่ server
                if (trim($rows['fs']) == '*') {
#-----------------------------------------------------------

                    try {
                        $table = Yii::$app->db->createCommand("SHOW CREATE TABLE {$rows['ts']};")->queryAll();
                        $gen_auto = str_replace("CREATE TABLE `" . $rows['ts'] . "`", "CREATE TABLE  IF NOT EXISTS dw_" . $this->hcode . "." . $rows['ts'], $table[0]['Create Table']);
                        $gen_auto = str_replace("ENGINE=InnoDB", "ENGINE=MyISAM", $gen_auto);
                        $gen_auto = str_replace("ROW_FORMAT=COMPACT", "", $gen_auto);
                        $gen_auto = str_replace("DEFAULT CHARSET=tis620", "DEFAULT CHARSET=utf8", $gen_auto . ';');
                        $queryString[] = $gen_auto;
                    } catch (\Exception $e) {
#$this->writeLog($this->log_error, $e->getMessage());
                        continue;
                    }
                } else {
#-----------------------------------------------------------
//กรณีสร้างเองตาม fields
                    try {
                        $result = Yii::$app->db->createCommand("describe {$rows['ts']};")->cache(3600)->queryAll();
                        $column = explode(',', $rows['fs']); //แยก field เป็น array
                        $gen_manual = "CREATE TABLE IF NOT EXISTS dw_{$this->hcode}.{$rows['ts']} (";
                        foreach ($result as $a => $b) {
                            if (in_array($b['Field'], $column)) {
                                $gen_manual .= $b['Field'] . ' ' . $b['Type'] . ' NULL,';
                            }
                        }

                        $result = Yii::$app->db->createCommand("SHOW indexes FROM {$rows['ts']} WHERE Non_unique = 1 AND Seq_in_index = 1")->queryAll();
                        foreach ($result as $c => $d) {
                            if (in_array($d['Column_name'], $column)) {
                                $gen_manual .= 'KEY ' . $d['Key_name'] . '(' . $d['Column_name'] . '),';
                            }
                        }

                        $gen_manual = rtrim($gen_manual, ', ');
                        $gen_manual = $gen_manual . ') ENGINE=MyISAM DEFAULT CHARSET=utf8;';
                        $queryString[] = $gen_manual;
                    } catch (\Exception $e) {
                        continue;
                    }
#-----------------------------------------------------------
                }
                $foundNullTable++;
            }
#-----------------------------------------------------------
#echo $rows['ts'] . "\n";
        }
        if ($foundNullTable > 0) {
            echo "\n\nCREATE NEW TABLE " . $foundNullTable . "\n\n";
            $res = $this->createTableSync($queryString); //ส่งคำสั่งไป Query
            $this->getSyncList(); #รับข้อมูลตารางที่ต้อง Sync จาก Server
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function createTableSync($queryStaring = []) { #ส่งคำสั่งไป run ที่ Server
        $request = $this->connect->post('ws/wdc/execute', ['hcode' => $this->hcode, 'queryStaring' => $queryStaring]);
        $request->addHeaders(['Authorization' => 'Bearer ' . $this->token]);
        $response = $request->send();
        echo $response->getContent();
    }

    public function ckeckClientDB() {
        $return = [];
        try {
            $data = Yii::$app->db->createCommand("SHOW VARIABLES WHERE variable_name = 'version';")->queryOne();
            $return = ['name' => 'ckeckClientDB', 'status' => 'success', 'message' => $data['Value']];
        } catch (\Exception $exc) {
            $return = ['name' => 'ckeckClientDB', 'status' => 'error', 'message' => $exc->getMessage()];
        }
        echo json_encode($return);
        return $return;
    }

    public function ckeckNode() {
        $request = $this->connect->post('ws/wdc/checknode', ['hcode' => $this->hcode]);
        $request->addHeaders(['Authorization' => 'Bearer ' . $this->token]);
        $response = $request->send();
        echo $response->getContent();
    }

    public function operCallDataServer($query) {#ดำเนินการเตรียมเลือกข้อมูลจาก Server
        return $this->request($this->connect, 'ws/wdc/calldataserver', ['hcode' => $this->hcode, 'query' => $query]);

        /*
          $response = $request->send();
          if ($response->isOk) {
          $data = $response->getData(); # รับค่าจาก Response to array
          return @$data['data']['rows'];
          } else {
          $return = $response->getContent();
          return [];
          }
         *
         */
    }

    /*
     * ส่งข้อมูล
     */

    public function request($client, $url, $data = [], $file = []) {
        $try = 5;
        for ($loop = 1; $loop <= $try; $loop++) {
            try {
                /*
                  $client = new Client([
                  'baseUrl' => $this->baseUrl,
                  ]);
                 *
                 */
                $response = $client->createRequest()
                                #->addHeaders(['user-agent' => $this->userAgen])
                                ->setUrl($url)
                                ->setMethod('post')
                                ->setData($data)
                                ->addHeaders(['Authorization' => 'Bearer ' . $this->token])
                                ->setOptions([
                                    CURLOPT_CONNECTTIMEOUT => 30, //5 connection timeout
                                    CURLOPT_TIMEOUT => 3600, //10 data receiving timeout
                                    CURLOPT_SSL_VERIFYHOST => 0,
                                    CURLOPT_SSL_VERIFYPEER => false
                                ])->send();

#->addFile('pdffile', 'C:\Users\ITMAN\Desktop\2016-09-26_15-48-06.pdf')

                /*
                  if ($this->is_json($response->content)) {
                  $content = $response->content;
                  } else {
                  $content = json_encode(['name' => 'error', 'message' => $response->content]);
                  }

                  if ($response->isOk) {
                  return $response->setData($data);
                  } else {
                  return $content;
                  }
                 *
                 */
                #$response = $request->send();
                if ($response->isOk) {
                    $data = $response->getData(); # รับค่าจาก Response to array
                    #print_r($data);
                    return @$data['data']['rows'];
                } else {
                    $return = $response->getContent();
                    #echo $return;
                    return $return;
                }
            } catch (\Exception $exc) {
                if ($loop == $try) {
                    exit;
                } else {
                    echo 'Error::try agrain ' . $loop . ' ' . $exc->getMessage() . " \n";
                    sleep(1);
                    continue;
                }
            }
        }
        exit;
    }

    public function actionIndex() {
        echo $this->request('api/index', ['hcode' => $this->hcode, 'email' => 'p_taung@hotmail.com', 'sendtime' => date('Y-m-d H:i:s')]);
        echo $this->getSignature();
    }

    public function actionPing() {
        echo $this->request('api/ping', ['hcode' => $this->hcode, 'key' => '123456']);
    }

    public function getSignature() {
        $signature = php_uname();
#@$_SERVER["HTTP_ACCEPT_LANGUAGE"] .
#@$_SERVER["SERVER_SIGNATURE"] . " " .
#@$_SERVER["HTTP_USER_AGENT"] .
#date('Y-m-d H:i:s');
        return $signature;
    }

    /*
     * ตรวจสอบ Response ต้องเป็น JSON
     */

    public function is_json($str) {
        return json_decode($str) != null;
    }

    public function groupOrder($loopData) {
        if (!is_array($loopData)) {
            exit;
        }
        $sumCount = 0;
        $loop = $this->loop;
        $key = '';
        $arr = [];
        foreach ($loopData as $i => $v) {
            $index[] = $v['dd'];
        }
        $last = end($index);

        foreach ((array) $loopData as $i => $data) {
#echo '<br>' . "\$sumCount = $sumCount ... \$loop = $loop  --- && \$last = $last  ,\$data['dd'] = {$data['dd']}";
            if ($data['dd'] == '0000-00-000') {
                $arr[$data['dd']] = $data['cc'];
            } else {

                $sumCount += $data['cc'];

                if ($sumCount > $loop) {
                    $sumCount = ($sumCount - $data['cc']);
                    $arr[rtrim($key, ',')] = $sumCount;
                    $sumCount = $data['cc'];
                    $key = '';
                }

                if ($sumCount <= $loop && $last == $data['dd']) {
                    $key .= "'" . $data['dd'] . "',";
                    $arr[rtrim($key, ',')] = $sumCount;
                }

                if ($sumCount <= $loop) {
                    $key .= "'" . $data['dd'] . "',";
                    continue;
                } else {
                    $key .= "'" . $data['dd'] . "',";
                    $arr[rtrim($key, ',')] = $sumCount;
                }
            }
        }

        return $arr;
    }

//การแบ่งกลุ่มข้อมูลเป็นส่วนๆ โดยใช้ limit
    public function splitLimit($numRows) {
        $split = $this->splitLimit;
        for ($page_start = 0; $page_start < $numRows; $page_start = ($page_start + $split)) {
            if ($numRows < $split) {
                $limitQuery = " ";
            } elseif ($numRows == $split) {
                $limitQuery = "  limit $page_start , $split";
            } elseif (($numRows % $split) == 0) {
                $limitQuery = "  limit $page_start , $split";
            } else {
                $limitQuery = "  limit $page_start , $split";
            }
            $ref[] = $limitQuery;
        }
        if ($numRows < 1 || $split < 1)
            $ref = array();
        return $ref;
    }

    /*
      public function clearDataServer($queryStaring = []) { #ส่งคำสั่งไป run ที่ Server
      $request = $this->connect->post('ws/wdc/execute', ['hcode' => $this->hcode, 'queryStaring' => $queryStaring]);
      $request->addHeaders(['Authorization' => 'Bearer ' . $this->token]);
      $response = $request->send();
      echo $response->getContent();
      }
     *
     */

    public function clearDataServer($ds = []) {
//ตรวจสอบข้อมูลทาง server
        switch ($ds['syncmode']) {
            case 1:
                $sql = " WHERE " . $ds['param'] . " IN ({$ds['order']}) " . (strpos($ds['order'], "0000-00-00") ? " OR ({$ds['param']} IS NULL OR {$ds['param']} = '' )" : "" );
                break;
            case 2:
                $sql = '';
                break;
            case 3:
                $sql = " WHERE LEFT(" . $ds['param'] . ",6) IN ({$ds['order']}) " . (strpos($ds['order'], "'000000'") ? " OR ({$ds['param']} IS NULL OR {$ds['param']} = '' )" : "" );
                break;
            case 4:
                $sqlWhere = $this->sqlGroup($ds['param'], $ds['order']);
                $sql = " WHERE {$sqlWhere} ";
                break;
        }

        return $sql;
    }

    public function genSQLLoopData($ds = []) {
        switch ($ds['syncmode']) {
            case 1:
                $sql = "SELECT SQL_BIG_RESULT {$ds['column']} FROM {$ds['table']} WHERE {$ds['param']}  IN ({$ds['order']}) " . (strpos($ds['order'], "0000-00-00") ? " OR ({$ds['param']} IS NULL OR {$ds['param']} = '' )" : "" ) . $ds['limit'] . ' /*' . date('Y-m-d H') . ' */;';
                break;
            case 2:
                $sql = "SELECT {$ds['column']} FROM {$ds['table']} {$ds['limit']}" . ' /*' . date('Y-m-d H') . ' */;';
                break;
            case 3:
                $sql = "SELECT SQL_BIG_RESULT {$ds['column']} FROM {$ds['table']} WHERE LEFT({$ds['param']},6)  IN ({$ds['order']}) " . (strpos($ds['order'], "'000000'") ? " OR ({$ds['param']} IS NULL OR {$ds['param']} = '' )" : "" ) . $ds['limit'] . ' /*' . date('Y-m-d H') . ' */;';
                break;
            case 4:
                $sql = $this->sqlGroup($ds['param'], $ds['order']);
                $sql = "SELECT SQL_BIG_RESULT {$ds['column']} FROM {$ds['table']} WHERE {$sql} {$ds['limit']} /*" . date('Y-m-d H') . ' */;';
                break;
        }

        return $sql;
    }

    public function sqlGroup($field, $values) {
        $sql = '';
        $values = str_replace("'", '', $values);
        $values = explode(',', $values);

        foreach ($values as $data) {
            @list($min, $max) = @explode('|', $data);
            $sql .= $field . ' between "' . $min . '" and "' . $max . '" or ';
        }
        $sql = rtrim($sql, 'or ');
        $sql = '(' . $sql . ')';
        return $sql;
    }

    /**
     * Generate an MD5 hash string from the contents of a directory.
     *
     * @param string $directory
     * @return boolean|string
     */
    public function hashDirectory($directory) {
        if (!is_dir($directory)) {
            return false;
        }

        $files = array();
        $dir = dir($directory);

        while (false !== ($file = $dir->read())) {
            if ($file != '.' and $file != '..') {
                if (is_dir($directory . '/' . $file)) {
                    $files[] = $this->hashDirectory($directory . '/' . $file);
                } else {
                    $files[] = sha1_file($directory . '/' . $file);
                }
            }
        }

        $dir->close();

        return sha1(implode('', $files));
    }

#-----------------------------------------------------------------------------------------

    public function operQueryData($tableMustToSync = []) {
        $mask = "%s|%8.8s|%-50.50s|%s\n";
        $sqlQueryArr = $this->getSqlQueryString($tableMustToSync); #รับค่า SQLQUERY
        $ds = [];
        $Arr = array_merge_recursive($tableMustToSync, $sqlQueryArr);
        $no = 1;
        $countTableSync = count($Arr);
        $zipsize = 0;
        $txtsize = 0;
        foreach ($Arr as $rows) {
            #echo "\nSENDING|{$no}/{$countTableSync}|{$rows['ts']}|";

            $dataQuerySend = $this->operCompareData($rows['query'], $rows['sm']); #ดำเนินเปรียบเทียบข้อมูลจาก Client vs Server
            $ds['table'] = $rows['ts'];
            $ds['param'] = $rows['p1'];
            $ds['syncmode'] = $rows['sm'];
            $ds['syncfield'] = $rows['fs'];
            $ds['column'] = $rows['column'];
            $splitNo = 1; #บอกลำดับของไฟล์

            foreach ($dataQuerySend as $scope => $value) {
#-----------------------------------------------------------------------------------------
                $ds['order'] = $scope; //order
                $ds['numrow'] = $value; //numrow
                $sqlClear = '';

                if (!empty($scope))
                    $sqlClear = $this->clearDataServer($ds, $scope);#Clear data @ server

                if (in_array($rows['sm'], [1, 3, 4]) && $sqlClear <> '') {
                    $clearBase64 = "{$sqlClear} "; #Clear data
                }
                if ($rows['sm'] == 2 && $sqlClear == '')
                    $clearBase64 = "{$sqlClear} ";#Clear data

                $splitLimit = $this->splitLimit($value);
                $countSplitLimit = count($splitLimit);
                $success = 0;
                $error = 0;
                foreach ($splitLimit as $index => $limit) {
                    $ds['limit'] = trim($limit);

                    #---------------------------------------------------------------------------
                    try {
                        #---genSQLLoopData------------------------------------------------------------------------
                        $sql = $this->genSQLLoopData($ds);
                        #---------------------------------------------------------------------------
                        $return = '';
                        $result = Yii::$app->db->createCommand($sql)->queryAll();

                        $return = base64_encode($clearBase64 . ($index == 0 ? "  " : " AND 1<>1")) . "\r\n"; #แทรกคำสั่งเพื่อการ Clear ข้อมูล
                        $return .= "({$rows['column']}) VALUES ";
                        if (count($result) > 0) {
                            foreach ((array) $result as $row) {
                                $row_data = "(";
                                foreach ($row as $data) {
                                    if (isset($data)) {
                                        $data = addslashes($data);
                                        # Updated to preg_replace to suit PHP5.3 +
                                        $data = preg_replace("/\n/", "\\n", $data);
                                        $row_data .= '"' . $data . '"';
                                    } else {
                                        $row_data .= "null";
                                    }
                                    $row_data .= ",";
                                }
                                $row_data = rtrim($row_data, ",");
                                $row_data .= "),";
                                $return.= $row_data;
                            }
                            $return = rtrim($return, ",");
                            $return.=";";
                            $filename = $this->hcode . '-' . $rows['ts'] . '-' . ($rows['csclient'] == 1 ? 0 : $rows['csclient']) . '-' . str_pad($splitNo, 5, '0', STR_PAD_LEFT) . '-' . $rows['cc'] . '-' . md5($sql); #ชื่อไฟล์
                            #hospcode-table-checksum-count_record_table-md5_query
                            #Save to file.txt
                            $file = $this->putToFile($return, $filename);
                            $zipsize += $file['zipsize'];
                            $txtsize += $file['txtsize'];

                            $this->upload(['hcode' => $this->hcode, 'filename' => $filename], $file);
                        }
                        $success++;
                    } catch (\Exception $e) {
                        #echo 'Error query SQL...';
                        $error++;
                    }
                    $logJson = [
                        'process_all' => $countTableSync,
                        'process_current' => $no,
                        'process_table' => $rows['ts'],
                        'upload_current_split' => $countSplitLimit,
                        'upload_current_process' => $splitNo,
                        'zipsize' => $zipsize,
                        'txtsize' => $txtsize,
                    ];
                    $this->logProcess(json_encode($logJson));
                    #---------------------------------------------------------------------------
                    $splitNo++;
                }

                #echo "SUCCESS:{$success}|ERROR:{$error}";
            }
            $logJson = [
                'process_all' => @$countTableSync,
                'process_current' => @$no,
                'process_table' => @$rows['ts'],
                'upload_current_split' => @$countSplitLimit,
                'upload_current_process' => @$splitNo,
                'zipsize' => @$zipsize,
                'txtsize' => @$txtsize,
            ];
            $this->logProcess(json_encode($logJson));
            printf($mask, 'SENDING', "$no/{$countTableSync}", $rows['ts'], 'OK');
            $no++;
        }
    }

    public function operCompareData($query, $syncmode) {#ดำเนินการเตรียมเลือกข้อมูลจาก Client
        $clientData = Yii::$app->db->createCommand(str_replace('dw_' . $this->hcode . '.', '', $query))->cache(7200)->queryAll();
        $dataServer = [];
        $chk = [];
        if (in_array($syncmode, [1, 3, 4])) {
            $serverData = $this->operCallDataServer($query); #รับค่า SQLQUERY
            foreach ($serverData as $key => $value) {#เปรียบเทียบค่าตรวจสอบจาก Server
                $dataServer[$value['dd']] = $value['ss'];
                $dataCServer[$value['dd']] = $value['cc'];
            }
        }
        if (count($clientData) > 0) {
            foreach ($clientData as $key => $row) {
                $dataCClient[$row['dd']] = $row['cc'];
                if (count($dataServer) > 0) {
                    if (@$dataServer[$row['dd']] != $row['ss'])
                        $chk[] = ['dd' => $row['dd'], 'cc' => $row['cc']];
                } else {
                    $chk[] = ['dd' => $row['dd'], 'cc' => $row['cc']];
                }
            }

            #ค่าที่ต้องลบทิ้งจาก ​Server
            $diff = @array_diff_key($dataCServer, $dataCClient);
            foreach ((array) $diff as $k => $v) {
                $chk[] = ['dd' => @$k, 'cc' => @$v];
            }

            #แบ่งกลุ่มเพื่อเพิ่มความเร็วในการส่ง Query
            if (count($chk) > 0)
                $chk = $this->groupOrder($chk);
        }

        return $chk;
    }

    #-----------------------------------------------------------------------------------------

    public function actionOnline() {
        $data[0] = "{}";
        if (file_exists(Yii::getAlias('@app') . "\log\logprocess.txt"))
            $data = @file(Yii::getAlias('@app') . "\log\logprocess.txt");
        if (file_exists("../../logs/logprocess.txt"))
            $data = @file("../../logs/logprocess.txt");
        /*
          try {
          $ckport = shell_exec('netstat -an | find "8080"');
          if (empty($ckport)) {
          $curentpath = dirname(Yii::getAlias('@app'));
          shell_exec($curentpath . '\start-webservice.bat');
          }
          } catch (\Exception $exc) {
          echo $exc->getMessage();
          }
         */
        $db = $this->ckeckClientDB();
        $this->connect(); #เชื่อมต่อ api เพื่อรับ token
        $request = $this->connect->post('ws/wdc/online', [
            'hcode' => $this->hcode,
            'secretkey' => $this->hcode,
            'version' => $this->userAgen,
            'client' => php_uname(),
            'clienttime' => date('y-m-d H:i:s'),
            'dbversion' => $db['message'],
            'log' => @json_decode($data[0], true)
        ]);
        $request->addHeaders(['Authorization' => 'Bearer ' . $this->token]);
        $response = $request->send();
        #echo $response->getContent();
        $this->commandRequest(json_decode($response->getContent(), true));
    }

    public function commandRequest($respone) {
        print_r($respone);

        if (@$respone['sync'] == 1) {
            #$this->actionStart();
            $curentpath = dirname(Yii::getAlias('@app'));
            $ckport = shell_exec($curentpath . '\sending.bat');
        }

        if (@$respone['dlc'] == 1) {
            $this->actionDlc();
        }
    }

    public function actionDlc() {#ดำเนินการตามคำสั่งจาก Server
        $this->connect(); #เชื่อมต่อ api เพื่อรับ token
        $request = $this->connect->post('ws/wdc/dlc', ['hcode' => $this->hcode]);
        $request->addHeaders(['Authorization' => 'Bearer ' . $this->token]);
        $response = $request->send();

        if ($response->isOk) {
            $data = $response->getData(); # รับค่าจาก Response to array
            print_r($data);
            if (count($data['data']) > 0) {
                foreach ($data['data'] as $key => $rows) {
                    try {
                        $result = Yii::$app->db->createCommand($rows['sqlquery'])->execute();
                        $post = $this->connect->post('ws/wdc/dlc', ['hcode' => $this->hcode,
                            'report' => ['wtc_id' => $rows['wtc_id'], 'command_status' => 'success', 'processtime' => date('Y-m-d H:i:s'), 'command_message' => '']]);
                    } catch (\Exception $e) {
                        $post = $this->connect->post('ws/wdc/dlc', ['hcode' => $this->hcode,
                            'report' => ['wtc_id' => $rows['wtc_id'], 'command_status' => 'error', 'command_message' => $e->getMessage(), 'processtime' => date('Y-m-d H:i:s')]]);
                    }
                    $post->addHeaders(['Authorization' => 'Bearer ' . $this->token]);
                    $res_post = $post->send();
                    print_r($res_post->getData());
                }
            }
            return @$data['data']['rows'];
        } else {
            $return = $response->getContent();
            return [];
        }
    }

    public function checkSumClient() {
        $mask = "%s|%8.8s|%-50.50s|%s\n";
        #---------------------------------------------------------
        $tableMustToSync = [];
        $describe = $this->getDesc(); #ดึงข้อมูล Column จาก Server

        $countTableSync = count($this->tableSyncData);

        $no = 1;
        $str = '';
        #---------------------------------------------------------
        foreach ($this->tableSyncData as $rows) {
            #$this->printf("CHECKSUM|{$no}/{$countTableSync}|{$rows['ts']}|");
            $column_fs = '';
            #---------------------------------------------------------
            $column = '';
            $cfieldname = [];
            if ($rows['fs'] == '*' || $rows['fs'] == '') {
                #-------------------------------------------------------------
                try {
                    $result = Yii::$app->db->createCommand("describe {$rows['ts']} ;")->cache(21600)->queryAll();
                    foreach ($result as $a => $b) {
                        if (!in_array($b['Field'], $describe[$rows['ts']]))
                            continue;

                        $column.= ' CAST(ifnull(' . $b['Field'] . ",'') AS CHAR),";
                        $column_fs .= $b['Field'] . ",";
                        $cfieldname[] = $b['Field'];
                    }
                } catch (\Exception $e) {
                    #echo $e->getMessage();
                    #echo "ERROR Query describe table ({$rows['ts']})\n";
#continue;
                }
            } else {
#-------------------------------------------------------------
                $fieldName = explode(',', $rows['fs']);
                foreach ($fieldName as $field) {
                    if (!in_array($field, $describe[$rows['ts']]))
                        continue;
                    $column.= ' CAST(ifnull(' . $field . ",'') AS CHAR),";
                    $column_fs .= $field . ",";
                    $cfieldname[] = $field;
                }
            }
#-------------------------------------------------------------
            $column_fs = rtrim($column_fs, ',');

            $column = rtrim($column, ',');

            if (count($cfieldname) > 1)
                $column = "concat_ws({$column})";


            try {
                $checkSumQuery = "SELECT SUM(CRC32(convert(" . $column . " USING utf8))) AS csum ,COUNT(*) AS cc FROM " . $rows['ts'] . ';';

                if (in_array($rows['sm'], [1, 3])) {#กรณีเป็นบริการ ไม่ต้อง checksum ให้ตรวจสอบรายละเอียดได้เลย
                    #$checkSumQuery = "SELECT SUM(CRC32(convert(" . $column . " USING utf8))) AS cc FROM " . $rows['ts'] . " WHERE YEAR({$rows['p1']}) BETWEEN YEAR(NOW())-5 AND YEAR(NOW()) /*" . date('Y-m-d H') . ' */;';
                    #$checkSum = Yii::$app->db->createCommand($checkSumQuery)->queryScalar();
                    $checkSumQuery = "SELECT 1 AS csum ,COUNT(*) AS cc FROM " . $rows['ts'] . ';';
                    $checkSum = Yii::$app->db->createCommand($checkSumQuery)->cache(3600)->queryOne();
                } else {
                    $checkSum = Yii::$app->db->createCommand($checkSumQuery)->cache(3600)->queryOne();
                }

                if ($checkSum['csum'] <> $rows['cm'] && $checkSum['csum'] > 0) { #ตรวจสอบ CM Server VS Client
                    $tableMustToSync[$rows['ts']] = $rows;
                    $tableMustToSync[$rows['ts']]['checksum'] = "SUM(CRC32(convert(" . $column . " USING utf8))) AS ss";
                    $tableMustToSync[$rows['ts']]['column'] = $column_fs;
                    $tableMustToSync[$rows['ts']]['csclient'] = $checkSum['csum'];
                    $tableMustToSync[$rows['ts']]['cc'] = $checkSum['cc'];
                }
                $str = "OK";
            } catch (\Exception $e) {
                $str = "ERROR:Checksum table " . $e->getMessage();
            }
            printf($mask, 'CHECKSUM', "$no/{$countTableSync}", $rows['ts'], $str);
            $no++;
        }
        return $tableMustToSync; # return ตามที่ checksum ไม่ตรงกัน
    }

    public function putToFile($data, $fileNameQuery) {

        $path = Yii::getAlias('@app') . "\log\\"; //กำหนดค่า path ของ Log
        $filepath = $path . $fileNameQuery . '.txt';
        $handle = fopen($filepath, 'w+');
        fwrite($handle, $data);
        fclose($handle);

        $txtsize = @filesize($filepath);
        $zipfile = $path . $fileNameQuery . '.zip';

        try {
            $zip = new \ZipArchive;
            if ($zip->open($zipfile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
                $zip->addFile($filepath, $fileNameQuery . '.txt');
                $zip->close();
                $zipfilesize = @filesize($zipfile);

                if (!@unlink($filepath))
                    @unlink($filepath);
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        return ['name' => $fileNameQuery, 'path' => $zipfile, 'txtsize' => $txtsize, 'zipsize' => $zipfilesize];
    }

    public function upload($data = [], $files = []) {
        $try = 5;
        for ($loop = 1; $loop <= $try; $loop++) {
            try {
                $response = $this->connect->createRequest()
                                ->setUrl('ws/wdc/upload')
                                ->setMethod('post')
                                ->setData($data)
                                ->addHeaders(['Authorization' => 'Bearer ' . $this->token])
                                ->addFile('zipFile', $files['path'])
                                ->setOptions([
                                    CURLOPT_CONNECTTIMEOUT => 30, //5 connection timeout
                                    CURLOPT_TIMEOUT => 3600, //10 data receiving timeout
                                    CURLOPT_SSL_VERIFYHOST => 0,
                                    CURLOPT_SSL_VERIFYPEER => false
                                ])->send();
                if ($response->isOk) {
                    @unlink($files['path']); #ลบไฟล์ zip เมื่อ upload เสร็จ
                    return $data = $response->getData(); # รับค่าจาก Response to array
                    ##print_r($data);
                } else {
                    return $return = $response->getContent();
                    #echo $return;
                }
            } catch (\Exception $exc) {
                if ($loop == $try) {
                    exit;
                } else {
                    echo 'Error::try agrain ' . $loop . ' ' . $exc->getMessage() . " \n";
                    sleep(30);
                    continue;
                }
            }
        }
    }

    public function logProcess($str = '') {
        $path = Yii::getAlias('@app') . "\log\\"; //กำหนดค่า path ของ Log
        $filepath = $path . 'logprocess.txt';
        $handle = fopen($filepath, 'w+');
        fwrite($handle, $str);
        fclose($handle);
    }

}
