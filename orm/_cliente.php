<?php

namespace gamboamartin\facturacion\models;

use Exception;

class _cliente
{
    private const BASE_URL = 'https://www.banxico.org.mx/cep';
    private const USER_AGENT = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3770.142 Safari/537.36';
    private array $base_data;
    private $session;

    public function __construct() {
        $this->session = curl_init();
        $this->base_data = [
            'tipoCriterio' => 'T',
            'captcha' => 'c',
            'tipoConsulta' => 1,
        ];
    }

    public function post(string $endpoint, array $data, array $options = []): string {
        $data = array_merge($this->base_data, $data);
        return $this->request('POST', $endpoint, $data, $options);
    }

    private function request(string $method, string $endpoint, array $data = [], array $options = []): string {
        $url = _cliente::BASE_URL . $endpoint;

        curl_setopt($this->session, CURLOPT_URL, $url);
        curl_setopt($this->session, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->session, CURLOPT_USERAGENT, self::USER_AGENT);
        curl_setopt($this->session, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->session, CURLOPT_TIMEOUT, 30);

        if ($method === 'POST') {
            curl_setopt($this->session, CURLOPT_POST, true);
            curl_setopt($this->session, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        if (!empty($options)) {
            foreach ($options as $key => $value) {
                curl_setopt($this->session, constant($key), $value);
            }
        }

        $response = curl_exec($this->session);
        $http_code = curl_getinfo($this->session, CURLINFO_HTTP_CODE);

        if ($response === false || $http_code >= 400) {
            $error = curl_error($this->session);
            curl_close($this->session);
            throw new Exception("HTTP Error $http_code: $error");
        }

        return $response;
    }




}


