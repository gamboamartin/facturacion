<?php
namespace gamboamartin\facturacion\controllers;

use config\generales;
use gamboamartin\errores\errores;

class _http_client
{
    public errores $errores;
    private string $baseUrl;
    private array $defaultHeaders;

    private string $path_constancia = '5de4b043-e573-488f-822d-942f18b487aa';
    private string $path_validacion_empleado_contacto = 'valida_telefono_empleado';

    public function __construct()
    {
        $base_url = '';
        if (isset(generales::$url_base_n8n)) {
            $base_url = generales::$url_base_n8n;
        }
        $this->baseUrl = $base_url;
        $this->defaultHeaders = [];
        $this->errores = new errores();
    }

    public function request_constancias(int $fc_row_layout_id, string $rfc, int $whatsapp): array
    {
        $data = [
            'rfc' => $rfc,
            'fc_row_layout_id' => $fc_row_layout_id,
            'whatsapp' => $whatsapp
        ];
        try {
            $rs = $this->post(
                endpoint: $this->path_constancia,
                data:  $data
            );
        }catch (\Exception $e) {
            return $this->errores->error(mensaje: $e->getMessage(), data: $e);
        }

        return $rs;

    }

    public function request_validacion_empleado_contacto(
        string $nombre,
        string $url_validacion,
        string $codigo_pais,
        string $telefono,
    ): array
    {
        $data = [
            'nombre' => $nombre,
            'url_validacion' => $url_validacion,
            'codigo_pais' => $codigo_pais,
            'telefono' => $telefono
        ];
        try {
            $rs = $this->post(
                endpoint: $this->path_validacion_empleado_contacto,
                data:  $data
            );
        }catch (\Exception $e) {
            return $this->errores->error(mensaje: $e->getMessage(), data: $e);
        }

        return $rs;
    }

    private function post(string $endpoint, array $data = [], array $headers = []): array
    {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');

        $ch = curl_init($url);

        $payload = json_encode($data);

        $finalHeaders = array_merge([
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload)
        ], $this->defaultHeaders, $headers);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => $finalHeaders,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);

        $error = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        return [
            'status' => $status,
            'response' => $response,
            'error' => $error,
        ];
    }
}
