<?php

namespace JsonRPC;

class Server
{
    private $payload = array();
    private $procedures = array();


    public function __construct(array $payload = array())
    {
        $this->payload = $payload;
    }


    public function getPayload()
    {
        $result = json_decode(file_get_contents('php://input'), true);
        if ($result) return $result;

        return array();
    }


    public function register($name, \Closure $callback)
    {
        $this->procedures[$name] = $callback;
    }


    public function getResponse(array $data)
    {
        $response = array(
            'jsonrpc' => '2.0'
        );

        if (isset($this->payload['id'])) {

            $response['id'] = $this->payload['id'];
        }

        $response = array_merge($response, $data);

        return json_encode($response);
    }


    public function mapParameters(array $request_params, array $method_params, array &$params)
    {
        // Positional parameters
        if (array_keys($request_params) === range(0, count($request_params) - 1)) {

            if (count($request_params) !== count($method_params)) return false;
            $params = $request_params;

            return true;
        }

        // Named parameters
        foreach ($method_params as $p) {

            $name = $p->getName();

            if (isset($request_params[$name])) {

                $params[$name] = $request_params[$name];
            }
            else {

                return false;
            }
        }

        return true;
    }


    public function execute()
    {
        // Check JSON format
        if (empty($this->payload)) {

            $this->payload = json_decode(file_get_contents('php://input'), true);

            if (! $this->payload) {

                return $this->getResponse(array(
                    'error' => array(
                        'code' => -32700,
                        'message' => 'Parse error.'
                    )
                ));
            }
        }

        // Check JSON-RPC format
        if (! isset($this->payload['jsonrpc']) ||
            ! isset($this->payload['method']) ||
            ! is_string($this->payload['method']) ||
            $this->payload['jsonrpc'] != '2.0' ||
            (isset($this->payload['params']) && ! is_array($this->payload['params']))) {

            return $this->getResponse(array(
                'error' => array(
                    'code' => -32600,
                    'message' => 'Invalid Request.'
                )
            ));
        }

        // Procedure not found
        if (! isset($this->procedures[$this->payload['method']])) {

            return $this->getResponse(array(
                'error' => array(
                    'code' => -32601,
                    'message' => 'Procedure not found.'
                )
            ));
        }

        $callback = $this->procedures[$this->payload['method']];
        $params = array();

        $reflection = new \ReflectionFunction($callback);

        if (isset($this->payload['params'])) {

            $parameters = $reflection->getParameters();

            if (! $this->mapParameters($this->payload['params'], $parameters, $params)) {

                return $this->getResponse(array(
                    'error' => array(
                        'code' => -32602,
                        'message' => 'Invalid params.'
                    )
                ));
            }
        }

        $result = $reflection->invokeArgs($params);

        return $this->getResponse(array('result' => $result));
    }
}
