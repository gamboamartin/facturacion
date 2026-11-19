<?php
declare(strict_types=1);
/** Metadato funcional sin asignar permisos: exclusivamente DB efímera de FPM. */
function qa_completar_metadatos_fpm(PDO $link): array {
    if ($link->query('SELECT DATABASE()')->fetchColumn() !== 'qa_csd_integral'
        || (int)$link->query('SELECT @@skip_networking')->fetchColumn() !== 1
        || !is_file('/qa-instance-id') || (is_dir('/home') && count(scandir('/home')) > 2)
        || readlink('/proc/self/ns/net') === getenv('QA_HOST_NET_NS')) {
        throw new RuntimeException('Instancia no acreditada');
    }
    $secciones = $link->query("SELECT id FROM adm_seccion WHERE descripcion='fc_csd'")->fetchAll(PDO::FETCH_COLUMN);
    if (count($secciones) !== 1) { throw new RuntimeException('Seccion ficticia no unica'); }
    if ((int)$link->query('SELECT COUNT(*) FROM adm_accion_grupo')->fetchColumn() !== 0) {
        throw new RuntimeException('Permisos no previstos');
    }
    $registro = ['codigo'=>'QA-FPM-ALTA-CSD','descripcion'=>'alta_bd','descripcion_select'=>'QA FPM ALTA CSD',
        'alias'=>'QA-FPM-ALTA-CSD','codigo_bis'=>'QA-FPM-ALTA-CSD','usuario_alta_id'=>1,'usuario_update_id'=>1,
        'adm_seccion_id'=>(int)$secciones[0],'id_css'=>'qa-fpm-alta-csd',
        'status'=>'inactivo','visible'=>'inactivo','inicio'=>'inactivo'];
    $columnas = $link->query('SHOW COLUMNS FROM adm_accion')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columnas as $columna) {
        if ($columna['Null']==='NO' && $columna['Default']===null && !str_contains($columna['Extra'],'auto_increment')
            && !array_key_exists($columna['Field'],$registro)) {
            throw new RuntimeException('Fixture incompleto: adm_accion.'.$columna['Field']);
        }
    }
    $q=$link->prepare('INSERT INTO adm_accion (`'.implode('`,`',array_keys($registro)).'`) VALUES ('.implode(',',array_fill(0,count($registro),'?')).')');
    $q->execute(array_values($registro));
    $id=(int)$link->lastInsertId();
    if ((int)$link->query('SELECT COUNT(*) FROM adm_accion_grupo')->fetchColumn() !== 0) { throw new RuntimeException('Permiso inesperado'); }
    return ['accion_ficticia_id'=>$id,'accion_inactiva'=>true,'permisos_asignados'=>0];
}
