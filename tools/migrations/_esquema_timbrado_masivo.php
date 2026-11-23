<?php
declare(strict_types=1);

/** Contraste de estructura previo a habilitar la acción; nunca corrige DDL al arrancar. */
final class EsquemaTimbradoMasivo
{
    public static function validar(PDO $pdo): void
    {
        $tablas = [
            'fc_timbrado_lote' => ['id'=>'char(32)','usuario_id'=>'bigint','clave_solicitud'=>'char(32)','huella'=>'char(64)','filtros'=>'text','fecha_alta'=>'timestamp'],
            'fc_timbrado_lote_item' => ['id'=>'bigint','lote_id'=>'char(32)','factura_id'=>'bigint','posicion'=>'int','empresa_id'=>'bigint','folio'=>'varchar(255)','estado'=>'varchar(24)','mensaje'=>'varchar(500)','uuid'=>'varchar(64)','codigo'=>'varchar(64)','fecha_update'=>'timestamp'],
            'fc_timbrado_intento' => ['factura_id'=>'bigint','intento'=>'char(32)','estado'=>'varchar(24)','uuid'=>'varchar(64)','codigo'=>'varchar(64)','mensaje'=>'varchar(500)','xml_respuesta'=>'mediumblob','fecha_update'=>'timestamp']
        ];
        $unicos = ['fc_timbrado_lote' => [['id'],['usuario_id','clave_solicitud']],
            'fc_timbrado_lote_item' => [['id'],['lote_id','factura_id'],['lote_id','posicion']],
            'fc_timbrado_intento' => [['factura_id']]];
        foreach ($tablas as $tabla => $esperadas) {
            $q=$pdo->prepare('SELECT ENGINE,TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');
            $q->execute([$tabla]);$motor=$q->fetch(PDO::FETCH_ASSOC);
            if (!$motor || strcasecmp($motor['ENGINE'],'InnoDB')!==0 || $motor['TABLE_TYPE']!=='BASE TABLE') {
                throw new RuntimeException('El control de lotes requiere tablas InnoDB.');
            }
            $columnas=$pdo->query('SHOW COLUMNS FROM '.$tabla)->fetchAll(PDO::FETCH_ASSOC);
            if (array_column($columnas,'Field')!==array_keys($esperadas)) {
                throw new RuntimeException('Las columnas del control de lotes no coinciden con la migración.');
            }
            foreach ($columnas as $columna) {
                $nombre=$columna['Field'];
                $tipo=preg_replace('/^(bigint|int)\([0-9]+\)/','$1',strtolower($columna['Type']));
                if ($tipo!==$esperadas[$nombre] || $columna['Null']!==($nombre==='xml_respuesta'?'YES':'NO')) {
                    throw new RuntimeException('Los tipos o la nullabilidad del control de lotes no coinciden.');
                }
                if (in_array($nombre,['fecha_alta','fecha_update'],true)
                    && strtoupper(str_replace('()','',(string)$columna['Default']))!=='CURRENT_TIMESTAMP') {
                    throw new RuntimeException('Los tiempos del control de lotes no tienen su valor predeterminado.');
                }
                if ($nombre==='fecha_update' && !str_contains(strtoupper($columna['Extra']),'ON UPDATE CURRENT_TIMESTAMP')) {
                    throw new RuntimeException('El control de lotes no actualiza la fecha de sus elementos.');
                }
                if ($tabla==='fc_timbrado_lote_item' && $nombre==='id' && !str_contains(strtolower($columna['Extra']),'auto_increment')) {
                    throw new RuntimeException('Los elementos del lote requieren un identificador autoincremental.');
                }
                if ($tabla==='fc_timbrado_lote_item' && $nombre==='estado' && $columna['Default']!=='pendiente') {
                    throw new RuntimeException('El estado inicial de los elementos no coincide con la migración.');
                }
                if (in_array($nombre,['uuid','codigo','mensaje'],true) && $columna['Default']!=='') {
                    throw new RuntimeException('Los resultados del control de lotes requieren valores iniciales vacíos.');
                }
            }
            $indices=[];
            foreach ($pdo->query('SHOW INDEX FROM '.$tabla)->fetchAll(PDO::FETCH_ASSOC) as $indice) {
                if ((int)$indice['Non_unique']===0 && $indice['Sub_part']===null) {
                    $indices[$indice['Key_name']][(int)$indice['Seq_in_index']]=$indice['Column_name'];
                }
            }
            foreach ($indices as &$campos) {ksort($campos);$campos=array_values($campos);}
            unset($campos);
            foreach ($unicos[$tabla] as $campos) {
                if (!in_array($campos,$indices,true)) {
                    throw new RuntimeException('Falta un índice único necesario para impedir envíos duplicados.');
                }
            }
            $primaria=$tabla==='fc_timbrado_intento'?['factura_id']:['id'];
            if (($indices['PRIMARY']??null)!==$primaria) {
                throw new RuntimeException('La clave primaria del control de lotes no coincide.');
            }
        }
        $q=$pdo->query("SELECT k.COLUMN_NAME,k.REFERENCED_TABLE_SCHEMA,k.REFERENCED_TABLE_NAME,k.REFERENCED_COLUMN_NAME,r.DELETE_RULE
            FROM information_schema.KEY_COLUMN_USAGE k
            INNER JOIN information_schema.REFERENTIAL_CONSTRAINTS r ON r.CONSTRAINT_SCHEMA=k.CONSTRAINT_SCHEMA AND r.CONSTRAINT_NAME=k.CONSTRAINT_NAME AND r.TABLE_NAME=k.TABLE_NAME
            WHERE k.TABLE_SCHEMA=DATABASE() AND k.TABLE_NAME='fc_timbrado_lote_item' AND k.COLUMN_NAME='lote_id'");
        $fk=$q->fetchAll(PDO::FETCH_ASSOC);
        $base=$pdo->query('SELECT DATABASE()')->fetchColumn();
        if (count($fk)!==1 || $fk[0]['REFERENCED_TABLE_SCHEMA']!==$base || $fk[0]['REFERENCED_TABLE_NAME']!=='fc_timbrado_lote'
            || $fk[0]['REFERENCED_COLUMN_NAME']!=='id' || $fk[0]['DELETE_RULE']!=='CASCADE') {
            throw new RuntimeException('Falta la relación de integridad entre el lote y sus elementos.');
        }
    }
}
