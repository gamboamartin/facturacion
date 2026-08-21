// valida_firma_meta.php
<?php
header('Content-Type: application/json');

$app_secret = $_GET['app_secret'] ?? '';
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

// Leer raw body
$rawBody = file_get_contents('php://input');

if (!$signature || !$rawBody || !$app_secret) {
    echo json_encode(['valido' => false, 'error' => 'Datos insuficientes']);
    exit;
}

$expected = 'sha256=' . hash_hmac('sha256', $rawBody, $app_secret);

echo json_encode([
    'valido' => ($signature === $expected)
]);