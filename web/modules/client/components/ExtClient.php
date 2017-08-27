<?php

namespace app\modules\client\components;

use yii\httpclient\Client;

class ExtClient extends Client {

    public function post($url, $data = null, $headers = [], $options = []) {
        $options = [CURLOPT_SSL_VERIFYHOST => 0, CURLOPT_SSL_VERIFYPEER => false];
        return $this->createRequestShortcut('post', $url, $data, $headers, $options);
    }

    private function createRequestShortcut($method, $url, $data, $headers, $options) {
        $request = $this->createRequest()
                ->setMethod($method)
                ->setUrl($url)
                ->addHeaders($headers)
                ->addOptions($options);
        if (is_array($data)) {
            $request->setData($data);
        } else {
            $request->setContent($data);
        }
        return $request;
    }

}
