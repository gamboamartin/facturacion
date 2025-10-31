<?php

/**
 * Cliente reusable para enviar archivos a un webhook de n8n (Dropbox) mediante HTTP POST multipart/form-data.
 */
class WebhookDropboxClient
{
    /** @var string URL completa del webhook de n8n */
    private string $webhookUrl;

    /** @var array Headers por defecto a incluir en la solicitud */
    private array $defaultHeaders = [];

    /** @var int Timeout de conexión (segundos) */
    private int $connectTimeoutSeconds = 10;

    /** @var int Timeout de respuesta total (segundos) */
    private int $responseTimeoutSeconds = 60;

    /**
     * @param string $webhookUrl URL del webhook n8n que recibirá los archivos
     * @param array $defaultHeaders Headers por defecto (por ejemplo: Authorization)
     */
    public function __construct(string $webhookUrl, array $defaultHeaders = [])
    {
        $this->webhookUrl = $webhookUrl;
        $this->defaultHeaders = $defaultHeaders;
    }

    /** Configura los timeouts de conexión y respuesta. */
    public function setTimeouts(int $connectTimeoutSeconds, int $responseTimeoutSeconds): void
    {
        $this->connectTimeoutSeconds = $connectTimeoutSeconds;
        $this->responseTimeoutSeconds = $responseTimeoutSeconds;
    }

    /** Reemplaza los headers por defecto. */
    public function setHeaders(array $headers): void
    {
        $this->defaultHeaders = $headers;
    }

    /**
     * Envía un archivo existente en disco al webhook.
     * @param string $filePath Ruta absoluta al archivo legible
     * @param array $extraFields Campos adicionales a enviar junto al archivo
     * @return array Resultado con status, httpCode, data (si JSON) y raw
     */
    public function uploadFilePath(string $filePath, array $extraFields = []): array
    {
        if (!is_readable($filePath)) {
            throw new InvalidArgumentException("El archivo no es legible: {$filePath}");
        }

        $filename = basename($filePath);
        $file = new CURLFile($filePath, mime_content_type($filePath) ?: 'application/octet-stream', $filename);
        $payload = array_merge($extraFields, [
            'file' => $file, // nombre de campo por defecto "file"
        ]);

        return $this->sendMultipart($payload);
    }

    /**
     * Envía un archivo cuyo contenido está en memoria.
     * @param string $filename Nombre de archivo a reportar
     * @param string $contents Contenido binario del archivo
     * @param array $extraFields Campos adicionales a enviar
     */
    public function uploadFromString(string $filename, string $contents, array $extraFields = []): array
    {
        $tmp = tmpfile();
        if ($tmp === false) {
            throw new RuntimeException('No se pudo crear archivo temporal.');
        }

        $meta = stream_get_meta_data($tmp);
        $tmpPath = $meta['uri'] ?? null;
        if (!$tmpPath) {
            fclose($tmp);
            throw new RuntimeException('No se pudo determinar la ruta del archivo temporal.');
        }

        $bytes = file_put_contents($tmpPath, $contents);
        if ($bytes === false) {
            fclose($tmp);
            throw new RuntimeException('No se pudo escribir el contenido en el archivo temporal.');
        }

        try {
            $file = new CURLFile($tmpPath, 'application/octet-stream', $filename);
            $payload = array_merge($extraFields, [
                'file' => $file,
            ]);
            return $this->sendMultipart($payload);
        } finally {
            fclose($tmp);
        }
    }

    /**
     * Envía múltiples archivos en una sola solicitud.
     * Acepta rutas (string) o especificaciones en memoria [filename, contents].
     * @param array $files Ej: ['a'=> '/ruta/a.pdf', ['filename'=>'b.txt','contents'=>'...']]
     */
    public function uploadMany(array $files, array $extraFields = []): array
    {
        $payload = $extraFields;

        foreach ($files as $key => $fileSpec) {
            if (is_string($fileSpec)) {
                if (!is_readable($fileSpec)) {
                    throw new InvalidArgumentException("El archivo no es legible: {$fileSpec}");
                }
                $filename = basename($fileSpec);
                $payload[$this->buildFileFieldName($key)] = new CURLFile(
                    $fileSpec,
                    mime_content_type($fileSpec) ?: 'application/octet-stream',
                    $filename
                );
            } elseif (is_array($fileSpec)) {
                $filename = $fileSpec['filename'] ?? 'upload.bin';
                $contents = $fileSpec['contents'] ?? null;
                if ($contents === null) {
                    throw new InvalidArgumentException('Faltan contents para archivo en memoria.');
                }

                $tmp = tmpfile();
                if ($tmp === false) {
                    throw new RuntimeException('No se pudo crear archivo temporal.');
                }
                $meta = stream_get_meta_data($tmp);
                $tmpPath = $meta['uri'] ?? null;
                if (!$tmpPath) {
                    fclose($tmp);
                    throw new RuntimeException('No se pudo determinar la ruta del archivo temporal.');
                }
                $bytes = file_put_contents($tmpPath, $contents);
                if ($bytes === false) {
                    fclose($tmp);
                    throw new RuntimeException('No se pudo escribir el contenido en el archivo temporal.');
                }

                $payload[$this->buildFileFieldName($key)] = new CURLFile($tmpPath, 'application/octet-stream', $filename);

                // Cerrar el archivo temporal al finalizar el script
                register_shutdown_function(static function () use ($tmp) {
                    fclose($tmp);
                });
            } else {
                throw new InvalidArgumentException('Cada archivo debe ser ruta (string) o arreglo {filename, contents}.');
            }
        }

        return $this->sendMultipart($payload);
    }

    /** Genera el nombre del campo de archivo para multipart (por defecto files[index]). */
    private function buildFileFieldName($key): string
    {
        if (is_int($key)) {
            return 'files[' . $key . ']';
        }
        return (string)$key;
    }

    /** Envía la solicitud multipart/form-data y maneja la respuesta. */
    private function sendMultipart(array $payload): array
    {
        $ch = curl_init();
        if ($ch === false) {
            throw new RuntimeException('No se pudo inicializar cURL');
        }

        $headers = $this->normalizeHeaders($this->defaultHeaders);

        curl_setopt_array($ch, [
            CURLOPT_URL => $this->webhookUrl,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeoutSeconds,
            CURLOPT_TIMEOUT => $this->responseTimeoutSeconds,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        $responseBody = curl_exec($ch);
        $curlErrNo = curl_errno($ch);
        $curlErr = curl_error($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlErrNo !== 0) {
            throw new RuntimeException('Error cURL: ' . $curlErr . ' (código ' . $curlErrNo . ')');
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new RuntimeException('HTTP ' . $httpCode . ' desde webhook: ' . $responseBody);
        }

        $decoded = json_decode((string)$responseBody, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return [
                'status' => 'ok',
                'httpCode' => $httpCode,
                'data' => $decoded,
                'raw' => $responseBody,
            ];
        }

        return [
            'status' => 'ok',
            'httpCode' => $httpCode,
            'data' => null,
            'raw' => $responseBody,
        ];
    }

    /** Normaliza headers en formato aceptado por cURL. */
    private function normalizeHeaders(array $headers): array
    {
        $out = [];
        foreach ($headers as $key => $value) {
            if (is_int($key)) {
                $out[] = (string)$value;
            } else {
                $out[] = $key . ': ' . $value;
            }
        }
        return $out;
    }
}


