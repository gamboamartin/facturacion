<?php

function valida_token_interno(): void
{
    $token_esperado = 'token_n8n_ivitec';
    $token_recibido = $_SERVER['HTTP_X_API_TOKEN'] ?? '';

    if (!hash_equals($token_esperado, $token_recibido)) {
        http_response_code(403);
        echo json_encode([
            'STS' => 'error',
            'MSG' => 'Acceso no autorizado'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}