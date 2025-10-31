<?php

// Controlador de ejemplo para reenviar archivos al webhook de n8n (Dropbox)
// - POST multipart/form-data con campo "file" reenviará el archivo
// - GET ?path=/ruta/absoluta/subira/archivo reenviará un archivo local del servidor

require_once __DIR__ . '/WebhookDropboxClient.php';

$WEBHOOK_URL = 'https://richito.ivitec.mx/webhook-test/subir_archivo';

try {
    $client = new WebhookDropboxClient($WEBHOOK_URL);
    $client->setTimeouts(10, 120);

    // Caso 1: Recibir un archivo desde un formulario (POST)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
        $file = $_FILES['file'];
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Archivo subido inválido']);
            exit;
        }

        $filename = $file['name'] ?? 'upload.bin';
        $contents = file_get_contents($file['tmp_name']);
        if ($contents === false) {
            http_response_code(500);
            echo json_encode(['error' => 'No se pudo leer el archivo subido']);
            exit;
        }

        // Enviar al webhook con campos extra del POST (si los hay)
        $result = $client->uploadFromString($filename, $contents, $_POST);
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

    // Caso 2: Subir un archivo local del servidor via parámetro GET "path"
    if (isset($_GET['path'])) {
        $path = (string)$_GET['path'];
        $extra = $_GET;
        unset($extra['path']);
        $result = $client->uploadFilePath($path, $extra);
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

    // Ayuda de uso si no se cumplen los casos anteriores
    header('Content-Type: application/json');
    echo json_encode([
        'uso' => [
            'POST multipart/form-data con campo "file" para reenviar a webhook n8n',
            'GET ?path=/ruta/absoluta/al/archivo para subir un archivo local',
        ],
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => $e->getMessage(),
    ]);
}

