<?php
declare(strict_types=1);

/** Maestros ficticios; no ejecutar fuera del motor confinado del laboratorio. */
function fixture_rutas(PDO $pdo): void
{
    if ($pdo->query('SELECT DATABASE()')->fetchColumn() !== 'qa_csd_integral'
        || (int)$pdo->query('SELECT @@skip_networking')->fetchColumn() !== 1
        || !is_file('/qa-instance-id') || readlink('/proc/self/ns/net') === getenv('QA_HOST_NET_NS')) {
        throw new RuntimeException('La prueba requiere el motor efímero aislado.');
    }
    $schema = json_decode(file_get_contents(__DIR__.'/esquema_acl.json'), true, flags: JSON_THROW_ON_ERROR)['tables'];
    $pending = $schema; $created = [];
    while ($pending) {
        $progress = false;
        foreach ($pending as $table => $definition) {
            foreach ($definition['foreign_keys'] as $fk) {
                if ($fk['REFERENCED_TABLE_NAME'] !== $table && !in_array($fk['REFERENCED_TABLE_NAME'], $created, true)) continue 2;
            }
            if (hash('sha256', $definition['ddl']) !== $definition['sha256']) throw new RuntimeException('DDL de fixture alterado.');
            $pdo->exec($definition['ddl']); $created[] = $table; unset($pending[$table]); $progress = true;
        }
        if (!$progress) throw new RuntimeException('Dependencias de fixture incompletas.');
    }
    $pdo->exec("CREATE TABLE adm_accion_grupo (
        id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY, adm_accion_id BIGINT NOT NULL,
        adm_grupo_id BIGINT NOT NULL, status VARCHAR(255) NOT NULL DEFAULT 'inactivo',
        usuario_alta_id BIGINT NOT NULL, usuario_update_id BIGINT NOT NULL,
        fecha_alta TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        fecha_update TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        codigo VARCHAR(255) NOT NULL, codigo_bis VARCHAR(255) NOT NULL,
        descripcion VARCHAR(255) NOT NULL, descripcion_select VARCHAR(255) NOT NULL,
        predeterminado VARCHAR(255) NOT NULL DEFAULT 'inactivo',
        UNIQUE KEY uq_accion_grupo (adm_accion_id,adm_grupo_id),
        FOREIGN KEY (adm_accion_id) REFERENCES adm_accion(id),
        FOREIGN KEY (adm_grupo_id) REFERENCES adm_grupo(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $insert = static function (string $table, array $overrides) use ($pdo, $schema): void {
        $row = [];
        foreach ($schema[$table]['columns'] as $column) {
            $name = $column['Field']; $type = $column['Type'];
            if ($name === 'id' || str_ends_with($name, '_id')) $value = 23;
            elseif (str_starts_with($type, 'timestamp') || str_starts_with($type, 'datetime')) $value = '2026-11-25 12:00:00';
            elseif ($name === 'status') $value = 'activo';
            elseif ($column['Default'] !== null) $value = $column['Default'];
            elseif ($column['Null'] === 'YES') $value = null;
            else $value = 'QA-FICTICIO';
            $row[$name] = $value;
        }
        $row = array_replace($row, $overrides);
        $statement = $pdo->prepare('INSERT INTO `'.$table.'` (`'.implode('`,`', array_keys($row)).'`) VALUES ('.implode(',', array_fill(0, count($row), '?')).')');
        $statement->execute(array_values($row));
    };
    $insert('adm_grupo', ['id'=>7, 'codigo'=>'PA', 'descripcion'=>'Administrador Sistema', 'root'=>'inactivo']);
    $insert('adm_grupo', ['id'=>11, 'codigo'=>'Sistema', 'descripcion'=>'Sistema', 'root'=>'inactivo']);
    foreach ([23=>[7,'qa_admin','activo'],24=>[7,'qa_admin_dos','activo'],41=>[11,'qa_operador','activo'],42=>[11,'qa_operador_inactivo','inactivo'],45=>[11,'qa_servicio','activo']] as $id=>$values) {
        $insert('adm_usuario', ['id'=>$id,'adm_grupo_id'=>$values[0],'user'=>$values[1],
            'password'=>'NO-LOGIN-FICTICIO','email'=>'qa'.$id.'@example.invalid',
            'nombre'=>'PERSONA FICTICIA '.$id,'ap'=>'QA','am'=>null,'status'=>$values[2]]);
    }
    $insert('adm_menu', ['id'=>3,'codigo'=>'facturacion','descripcion'=>'Facturacion']);
    $insert('adm_namespace', ['id'=>5,'codigo'=>'facturacion','descripcion'=>'gamboa.martin/facturacion','name'=>'gamboamartin\\facturacion']);
    $insert('adm_seccion', ['id'=>17,'codigo'=>'fc_factura','descripcion'=>'fc_factura','adm_namespace_id'=>5,'adm_menu_id'=>3]);
    $insert('adm_sistema', ['id'=>13,'codigo'=>'facturacion','descripcion'=>'facturacion']);
    $insert('adm_seccion_pertenece', ['id'=>19,'codigo'=>'factura.sistema','descripcion'=>'Factura sistema','adm_seccion_id'=>17,'adm_sistema_id'=>13]);
    $insert('adm_session', ['id'=>29,'name'=>'QA-FICTICIA','adm_usuario_id'=>23,'numero_empresa'=>1,'fecha'=>'2026-11-25 12:00:00','fecha_ultima_ejecucion'=>'2026-11-25 12:00:00','ip_publica'=>'192.0.2.1','permanente'=>'inactivo']);
    $insert('adm_accion', ['id'=>67,'codigo'=>'lista.17','descripcion'=>'lista','adm_seccion_id'=>17,
        'seguridad'=>'activo','es_view'=>'activo','es_lista'=>'activo','visible'=>'activo']);
    $pdo->exec("INSERT INTO adm_accion_grupo (id,adm_accion_id,adm_grupo_id,status,usuario_alta_id,usuario_update_id,codigo,codigo_bis,descripcion,descripcion_select)
        VALUES (101,67,11,'activo',23,23,'base.sistema','base.sistema','QA base','QA base')");
    $_SESSION = ['usuario_id'=>23,'grupo_id'=>7]; $_GET = []; $_POST = [];
}
