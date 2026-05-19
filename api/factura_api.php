<?php
chdir(__DIR__ . '/..');
require "init.php";
require 'vendor/autoload.php';

use base\conexion;

header('Content-Type: application/json; charset=utf-8');

$_SESSION['usuario_id'] = 2;
$_SESSION['grupo_id'] = 2;

$con = new conexion();
$link = conexion::$link;

$telefono = trim($_GET['telefono'] ?? '');

if ($telefono === '') {
    echo json_encode([
        'STS' => 'error',
        'MSG' => 'El teléfono es requerido'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$telefono = preg_replace('/\D+/', '', $telefono);

$sql = "
    SELECT
        adm_usuario.id AS adm_usuario_id,
        adm_usuario.adm_grupo_id,
        adm_grupo.codigo AS adm_grupo_codigo,
        adm_grupo.alias AS adm_grupo_alias,
        adm_grupo.descripcion AS adm_grupo_descripcion
    FROM adm_usuario
    INNER JOIN adm_grupo
        ON adm_grupo.id = adm_usuario.adm_grupo_id
    WHERE CONCAT(adm_usuario.codigo_pais, adm_usuario.telefono) = :telefono
      AND adm_usuario.estatus_telefono = 'validado'
      AND adm_usuario.status = 'activo'
      AND adm_grupo.status = 'activo'
    LIMIT 1
";

$stmt = $link->prepare($sql);
$stmt->execute([':telefono' => $telefono]);
$registro = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$registro) {
    echo json_encode([
        'STS' => 'no_encontrado',
        'MSG' => 'Usuario no encontrado o teléfono no validado',
        'adm_grupo_id' => 0
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'STS' => 'ok',
    'adm_usuario_id' => (int)$registro['adm_usuario_id'],
    'adm_grupo_id' => (int)$registro['adm_grupo_id'],
    'adm_grupo_codigo' => $registro['adm_grupo_codigo'],
    'adm_grupo_alias' => $registro['adm_grupo_alias'],
    'adm_grupo_descripcion' => $registro['adm_grupo_descripcion']
], JSON_UNESCAPED_UNICODE);
exit;