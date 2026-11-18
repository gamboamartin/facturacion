<?php
declare(strict_types=1);

/** Datos enteramente ficticios; únicamente dentro del motor efímero acreditado. */
function qa_crear_maestros(PDO $link): array
{
    if ($link->query('SELECT DATABASE()')->fetchColumn() !== 'qa_csd_integral'
        || (int)$link->query('SELECT @@skip_networking')->fetchColumn() !== 1
        || !is_file('/qa-instance-id') || (is_dir('/home') && count(scandir('/home')) > 2)
        || readlink('/proc/self/ns/net') === getenv('QA_HOST_NET_NS')) {
        throw new RuntimeException('Instancia de prueba no acreditada');
    }
    $inventario = [];
    $insertar = static function(string $tabla, array $datos = []) use ($link, &$inventario): int {
        if (!preg_match('/^[a-z_]+$/', $tabla)) { throw new RuntimeException('Tabla inválida'); }
        $campos = $link->query("SHOW COLUMNS FROM `$tabla`")->fetchAll(PDO::FETCH_ASSOC);
        $nombres = array_column($campos, 'Field');
        $numero = count($inventario) + 1;
        $codigo = 'QA-CSD-' . $numero;
        $base = ['codigo' => $codigo, 'codigo_bis' => $codigo, 'alias' => $codigo,
            'descripcion' => 'QA FICTICIO ' . $numero, 'descripcion_select' => 'QA FICTICIO ' . $numero,
            'status' => 'activo', 'usuario_alta_id' => 1, 'usuario_update_id' => 1,
            'fecha_alta' => '2026-11-18 12:00:00', 'fecha_update' => '2026-11-18 12:00:00'];
        foreach ($datos as $campo => $_) {
            if (!in_array($campo, $nombres, true)) { throw new RuntimeException("Columna no acreditada: $tabla.$campo"); }
        }
        $registro = array_replace(array_intersect_key($base, array_flip($nombres)), $datos);
        foreach ($campos as $campo) {
            if ($campo['Null'] === 'NO' && $campo['Default'] === null
                && !str_contains($campo['Extra'], 'auto_increment')
                && !array_key_exists($campo['Field'], $registro)) {
                throw new RuntimeException('Fixture incompleto: ' . $tabla . '.' . $campo['Field']);
            }
        }
        $keys = array_keys($registro);
        $sql = "INSERT INTO `$tabla` (`" . implode('`,`', $keys) . '`) VALUES ('
            . implode(',', array_fill(0, count($keys), '?')) . ')';
        $link->prepare($sql)->execute(array_values($registro));
        $id = isset($registro['id']) ? (int)$registro['id'] : (int)$link->lastInsertId();
        $inventario[] = ['tabla' => $tabla, 'id' => $id];
        return $id;
    };
    // No reutilizar maestros ni conceder privilegios de negocio a sesiones reales.
    foreach (['adm_grupo', 'adm_usuario', 'org_empresa', 'org_sucursal', 'pr_proceso'] as $tabla) {
        if ((int)$link->query("SELECT COUNT(*) FROM `$tabla`")->fetchColumn() !== 0) {
            throw new RuntimeException('Se esperaba tabla efímera vacía: ' . $tabla);
        }
    }
    $grupo = $insertar('adm_grupo', ['id' => 1, 'root' => 'inactivo', 'solo_mi_info' => 'inactivo']);
    $usuario = $insertar('adm_usuario', ['id' => 1, 'adm_grupo_id' => $grupo,
        'user' => 'qa_csd_ficticio', 'password' => 'CUENTA_SIN_LOGIN_FICTICIA',
        'email' => 'qa@example.invalid', 'nombre' => 'QA FICTICIO',
        'session' => 'QA_SIN_LOGIN', 'telefono' => '0000000000', 'ap' => 'FICTICIO', 'am' => 'PRUEBA']);
    $menu = $insertar('adm_menu');
    $namespace = $insertar('adm_namespace', ['descripcion' => 'gamboamartin\\facturacion\\models']);
    $seccion = $insertar('adm_seccion', ['descripcion' => 'fc_csd', 'adm_menu_id' => $menu,
        'adm_namespace_id' => $namespace]);
    $accion = $insertar('adm_accion', ['descripcion' => 'qa_csd_sin_permiso', 'adm_seccion_id' => $seccion,
        'status' => 'inactivo', 'visible' => 'inactivo', 'inicio' => 'inactivo', 'id_css' => 'qa-csd-inactiva']);

    $pais = $insertar('dp_pais', ['codigo' => 'MEX']);
    $estado = $insertar('dp_estado', ['dp_pais_id' => $pais]);
    $municipio = $insertar('dp_municipio', ['dp_estado_id' => $estado]);
    $cp = $insertar('dp_cp', ['codigo' => '00000', 'descripcion' => '00000', 'dp_municipio_id' => $municipio, 'georeferencia' => '0,0']);
    $colonia = $insertar('dp_colonia', ['georeferencia' => '0,0']);
    $postal = $insertar('dp_colonia_postal', ['dp_cp_id' => $cp, 'dp_colonia_id' => $colonia]);
    $calle = $insertar('dp_calle');
    $domicilio = $insertar('dp_calle_pertenece', ['dp_calle_id' => $calle, 'dp_colonia_postal_id' => $postal, 'georeferencia' => '0,0']);
    $regimen = $insertar('cat_sat_regimen_fiscal', ['codigo' => '601']);
    $persona = $insertar('cat_sat_tipo_persona', ['codigo' => 'MORAL']);
    $insertar('cat_sat_conf_reg_tp', ['cat_sat_regimen_fiscal_id' => $regimen, 'cat_sat_tipo_persona_id' => $persona]);
    $tipoEmpresa = $insertar('org_tipo_empresa');
    $tipoSucursal = $insertar('org_tipo_sucursal');
    $empresa = $insertar('org_empresa', ['rfc' => 'XAXX010101000', 'razon_social' => 'QA CSD FICTICIA SIN VALIDEZ',
        'cat_sat_regimen_fiscal_id' => $regimen, 'cat_sat_tipo_persona_id' => $persona,
        'dp_calle_pertenece_id' => $domicilio, 'org_tipo_empresa_id' => $tipoEmpresa,
        'fecha_inicio_operaciones' => '2026-01-01', 'fecha_ultimo_cambio_sat' => '2026-01-01', 'exterior' => '1',
        'interior' => 'QA', 'logo' => 'QA_SIN_LOGO', 'nombre_comercial' => 'QA FICTICIA',
        'email_sat' => 'qa@example.invalid', 'telefono_1' => '0000000000', 'telefono_2' => '0000000000',
        'telefono_3' => '0000000000', 'pagina_web' => 'https://example.invalid']);
    $sucursal = $insertar('org_sucursal', ['org_empresa_id' => $empresa, 'org_tipo_sucursal_id' => $tipoSucursal,
        'dp_calle_pertenece_id' => $domicilio, 'fecha_inicio_operaciones' => '2026-01-01', 'exterior' => '1', 'serie' => 'QA',
        'interior' => 'QA', 'telefono_1' => '0000000000', 'telefono_2' => '0000000000', 'telefono_3' => '0000000000']);
    $tipoProceso = $insertar('pr_tipo_proceso');
    $proceso = $insertar('pr_proceso', ['descripcion' => 'CSD', 'pr_tipo_proceso_id' => $tipoProceso]);
    $etapas = [];
    foreach (['ALTA', 'CER INTEGRADO', 'KEY INTEGRADO', 'DOCS INTEGRADOS', 'CER PEM INTEGRADO', 'KEY PEM INTEGRADO', 'LISTO USO'] as $nombre) {
        $etapa = $insertar('pr_etapa', ['descripcion' => $nombre]);
        $etapas[$nombre] = $insertar('pr_etapa_proceso', ['pr_proceso_id' => $proceso,
            'pr_etapa_id' => $etapa, 'adm_accion_id' => $accion]);
    }
    $tipos = [];
    foreach (['cer', 'key', 'pem'] as $extension) {
        $tipo = $insertar('doc_tipo_documento', ['descripcion' => 'QA CSD ' . strtoupper($extension)]);
        $ext = $insertar('doc_extension', ['descripcion' => $extension, 'codigo' => $extension]);
        $insertar('doc_extension_permitido', ['doc_tipo_documento_id' => $tipo, 'doc_extension_id' => $ext]);
        $insertar('doc_acl_tipo_documento', ['doc_tipo_documento_id' => $tipo, 'adm_grupo_id' => $grupo]);
        $insertar('doc_conf_tipo_documento_seccion', ['doc_tipo_documento_id' => $tipo, 'adm_seccion_id' => $seccion]);
        $tipos[$extension] = $tipo;
    }
    if ((int)$link->query('SELECT COUNT(*) FROM adm_accion_grupo')->fetchColumn() !== 0) {
        throw new RuntimeException('Se encontraron permisos de negocio no previstos');
    }
    return ['org_sucursal_id' => $sucursal, 'org_empresa_id' => $empresa,
        'grupo_id' => $grupo, 'usuario_id' => $usuario, 'pr_proceso_id' => $proceso,
        'etapas' => $etapas, 'tipos_documentales' => $tipos, 'objetos' => $inventario];
}
