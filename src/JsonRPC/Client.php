<?php

namespace JsonRPC;

class Client
{
    private $url;
    private $timeout;
    private $debug;


    public function __construct($url, $timeout = 5, $debug = false)
    {
        $this->url = $url;
        $this->timeout = $timeout;
        $this->debug = $debug;
    }


    public function execute($procedure, array $params = array())
    {
        $id = mt_rand();

        $payload = array(
            'jsonrpc' => '2.0',
            'method' => $procedure,
            'id' => $id
        );

        if (! empty($params)) {

            $payload['params'] = $params;
        }

        $result = $this->doRequest($payload);

        if (isset($result['id']) && $result['id'] == $id && array_key_exists('result', $result)) {

            return $result['result'];
        }
        else if ($this->debug && isset($result['error'])) {

            print_r($result['error']);
        }

        return null;
    }


    public function doRequest($payload)
    {
        $headers = array(
            'Connection: close',
            'Content-Type: application/json'
        );

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_USERAGENT, 'JSON-RPC PHP Client');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        $response = json_decode(curl_exec($ch), true);

        curl_close($ch);

        return is_array($response) ? $response : array();
    }
}
