<?php
declare(strict_types=1);
require '/qa/bootstrap.php';
use gamboamartin\errores\errores;
$db = new config\database();
$link = new PDO('mysql:unix_socket=/run/mysqld/mysqld.sock;dbname=qa_csd_integral', $db->db_user, $db->db_password,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$link->exec("SET sql_mode = ''");
$instaladores = [
    gamboamartin\administrador\instalacion\instalacion::class => [
        '_add_adm_grupo', '_add_adm_menu', '_add_adm_namespace', '_add_adm_seccion',
        '_add_adm_accion', '_add_adm_accion_grupo', '_add_adm_usuario'
    ],
    gamboamartin\proceso\instalacion\instalacion::class => [
        '_add_pr_tipo_proceso', '_add_pr_proceso', '_add_pr_etapa', '_add_pr_etapa_proceso'
    ],
    gamboamartin\documento\instalacion\instalacion::class => [
        '_add_doc_tipo_documento', '_add_doc_extension', '_add_doc_acl_tipo_documento',
        '_add_doc_extension_permitido', '_add_doc_documento', 'doc_version',
        '_add_doc_documento_etapa', '_add_doc_conf_tipo_documento_seccion'
    ]
];
$instaladores[gamboamartin\direccion_postal\instalacion\instalacion::class] = [
    '_add_dp_pais', '_add_dp_estado', '_add_dp_municipio', '_add_dp_cp',
    '_add_dp_colonia', '_add_dp_colonia_postal', '_add_dp_calle', '_add_dp_calle_pertenece'
];
$instaladores[gamboamartin\cat_sat\instalacion\instalacion::class] = [
    '_add_cat_sat_regimen_fiscal', '_add_cat_sat_tipo_persona', '_add_cat_sat_conf_reg_tp'
];
$instaladores[gamboamartin\organigrama\instalacion\instalacion::class] = [
    '_add_org_tipo_empresa', '_add_org_tipo_sucursal', '_add_org_empresa', '_add_org_sucursal'
];
$completados = [];
foreach ($instaladores as $clase => $metodos) {
    $objeto = $clase === gamboamartin\cat_sat\instalacion\instalacion::class ? (new ReflectionClass($clase))->newInstanceWithoutConstructor() : new $clase();
    foreach ($metodos as $metodo) {
        $resultado = (new ReflectionMethod($objeto, $metodo))->invoke($objeto, $link);
        if (errores::$error) {
            echo json_encode(['estado' => 'BLOQUEADO', 'metodo' => $metodo, 'codigo' => 'ddl_oficial_fallo'], JSON_UNESCAPED_SLASHES);
            exit(1);
        }
        $completados[] = $metodo;
    }
}
$esquemas = json_decode(file_get_contents('/qa/esquema-csd-sanitizado.json'), true, flags: JSON_THROW_ON_ERROR);
foreach ($esquemas['tablas'] as $tabla => $fuente) {
    if (!in_array($tabla, ['fc_csd', 'fc_csd_etapa', 'fc_cer_csd', 'fc_key_csd', 'fc_cer_pem', 'fc_key_pem'], true)
        || hash('sha256', $fuente['ddl']) !== $fuente['sha256_sanitizado']) {
        throw new RuntimeException('Esquema no acreditado');
    }
    $link->exec($fuente['ddl']);
}
$requeridas = [];
foreach ($link->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $tabla) {
    foreach ($link->query('SHOW COLUMNS FROM `' . $tabla . '`')->fetchAll(PDO::FETCH_ASSOC) as $columna) {
        if ($columna['Null'] === 'NO' && $columna['Default'] === null && !str_contains($columna['Extra'], 'auto_increment')) {
            $requeridas[$tabla][] = $columna['Field'];
        }
    }
}
echo json_encode(['estado' => 'DDL_SINTETICO_PREPARADO', 'metodos' => $completados,
    'columnas_obligatorias_sin_default' => $requeridas, 'tablas_csd' => array_keys($esquemas['tablas']), 'fk_activas' => (int)$link->query('SELECT @@foreign_key_checks')->fetchColumn()]), PHP_EOL;
