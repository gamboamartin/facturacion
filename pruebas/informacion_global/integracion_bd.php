<?php
declare(strict_types=1);

use base\orm\{inserts, upd, modelo_base};
use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\_informacion_global;
use gamboamartin\xml_cfdi_4\cfdis;

require_once '/qa/bootstrap.php';
$root = '/var/www/html/facturacion';
$registro = json_decode(file_get_contents(__DIR__.'/registro.json'), true, flags: JSON_THROW_ON_ERROR);
$db = new config\database();
if ($db->db_name !== 'qa_csd_integral') { throw new RuntimeException('Base sintética requerida'); }
$link = new PDO('mysql:unix_socket=/run/mysqld/mysqld.sock;dbname=qa_csd_integral', $db->db_user, $db->db_password,
    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$link->exec("SET sql_mode='STRICT_ALL_TABLES'");
$casos = [];
$aserciones = 0;
function exigir_global(bool $condicion, string $codigo): void {
    global $aserciones;
    $aserciones++;
    if (!$condicion) { throw new RuntimeException($codigo); }
}
function caso_global(string $nombre, callable $prueba): void {
    global $casos;
    errores::$error = false;
    $prueba();
    exigir_global(!errores::$error, 'bandera_error_inesperada');
    $casos[] = $nombre;
}
$fuentes = [];
foreach ([_informacion_global::class, inserts::class, upd::class, modelo_base::class, cfdis::class,
    gamboamartin\xml_cfdi_4\xml::class] as $clase) {
    $archivo=(new ReflectionClass($clase))->getFileName();
    $fuentes[$clase]=['archivo'=>$archivo,'sha256'=>hash_file('sha256',$archivo)];
}
$campos = ['global_periodicidad','global_meses','global_anio'];
$servicio = new _informacion_global(2026);
$insert = new inserts();
$value = new ReflectionMethod($insert,'value');
$valores = new ReflectionMethod($insert,'valores_sql_alta');
$update = new upd();
$params = new ReflectionMethod($update,'params_data_update');
$modelo=(new ReflectionClass(modelo_base::class))->newInstanceWithoutConstructor();
$modelo->tipo_campos=[];
$sqlInsert = static function (array $datos) use ($insert,$value,$valores): string {
    $sql='';
    foreach ($datos as $dato) {
        $procesado=$value->invoke($insert,$dato);
        exigir_global(!errores::$error && $procesado instanceof stdClass,'insert_valor');
        $sql=$valores->invoke($insert,$sql,$procesado->value,$procesado->value_es_null);
        exigir_global(is_string($sql) && !errores::$error,'insert_sql');
    }
    return $sql;
};
$sqlUpdate = static function (array $datos) use ($update,$params,$modelo): string {
    $partes=[];
    foreach ($datos as $campo=>$dato) {
        $procesado=$params->invoke($update,$campo,$modelo,$dato);
        exigir_global($procesado instanceof stdClass && !errores::$error,'update_valor');
        $partes[]=$procesado->campo.'='.$procesado->value;
    }
    return implode(',',$partes);
};
$leer = static fn(int $id):array => $link->query('SELECT global_periodicidad,global_meses,global_anio FROM fc_factura WHERE id='.$id)->fetch(PDO::FETCH_ASSOC);
$periodo=['global_periodicidad'=>'01','global_meses'=>'11','global_anio'=>'2026'];
$comprobante=['tipo_de_comprobante'=>'I','moneda'=>'MXN','total'=>'1.00','exportacion'=>'01','sub_total'=>'1.00',
    'lugar_expedicion'=>'11560','folio'=>'QA-GLOBAL','no_certificado'=>'00000000000000000000','forma_pago'=>'01',
    'metodo_pago'=>'PUE','fecha'=>'2026-11-23T12:00:00'];
$emisor=['rfc'=>'AAA010101AAA','nombre'=>'EMISOR FICTICIO SIN VALIDEZ','regimen_fiscal'=>'601'];
$receptor=['rfc'=>'XAXX010101000','nombre'=>'PUBLICO EN GENERAL','domicilio_fiscal_receptor'=>'11560',
    'regimen_fiscal_receptor'=>'616','uso_cfdi'=>'S01'];
$conceptos=[(object)['clave_prod_serv'=>'80101500','cantidad'=>'1','importe'=>'1.00','clave_unidad'=>'E48',
    'descripcion'=>'OPERACION FICTICIA SIN VALIDEZ','valor_unitario'=>'1.00','objeto_imp'=>'01',
    'impuestos'=>[]]];
$codigo=1;
$rollback=false;
ob_start();
try {
    caso_global('ddl_nullable_varchar_defaults',function () use ($link,$root,$campos) {
        $link->exec('CREATE TABLE fc_factura (id BIGINT NOT NULL PRIMARY KEY) ENGINE=InnoDB');
        $ddl=file_get_contents($root.'/tools/migrations/sql/fc_factura_informacion_global.sql');
        $link->exec($ddl);
        $columnas=$link->query('SHOW FULL COLUMNS FROM fc_factura')->fetchAll(PDO::FETCH_UNIQUE|PDO::FETCH_ASSOC);
        foreach ($campos as $campo) {
            exigir_global($columnas[$campo]['Type']===($campo==='global_anio'?'varchar(4)':'varchar(2)'),'tipo_ddl');
            exigir_global($columnas[$campo]['Null']==='YES' && in_array($columnas[$campo]['Default'],[null,'NULL'],true),'null_default_ddl');
        }
        $link->exec('INSERT INTO fc_factura(id) VALUES(99)');
        exigir_global($link->query('SELECT global_periodicidad,global_meses,global_anio FROM fc_factura WHERE id=99')->fetch(PDO::FETCH_NUM)===[null,null,null],'defaults_null_reales');
        $link->exec('DELETE FROM fc_factura WHERE id=99');
    });
    $link->beginTransaction();
    caso_global('insert_periodo_via_orm',function () use ($link,$periodo,$campos,$sqlInsert,$leer) {
        $link->exec('INSERT INTO fc_factura(id,'.implode(',',$campos).') VALUES(1,'.$sqlInsert($periodo).')');
        exigir_global($leer(1)===$periodo,'periodo_insertado');
    });
    caso_global('insert_vacio_sql_null',function () use ($link,$servicio,$campos,$sqlInsert,$leer) {
        $vacio=$servicio->normaliza_registro(array_fill_keys($campos,''));
        $link->exec('INSERT INTO fc_factura(id,'.implode(',',$campos).') VALUES(2,'.$sqlInsert($vacio).')');
        exigir_global($leer(2)===array_fill_keys($campos,null),'insert_null_real');
    });
    caso_global('update_periodo_via_orm',function () use ($link,$sqlUpdate,$leer,$periodo) {
        $otro=$periodo; $otro['global_anio']='2025';
        $link->exec('UPDATE fc_factura SET '.$sqlUpdate($otro).' WHERE id=1');
        exigir_global($leer(1)===$otro,'update_periodo_real');
    });
    caso_global('update_vacio_orm_cadena_vacia',function () use ($link,$servicio,$sqlUpdate,$leer,$campos) {
        $vacio=$servicio->normaliza_registro(array_fill_keys($campos,''));
        $sql=$sqlUpdate($vacio);
        exigir_global(!str_contains($sql,'NULL'),'update_orm_actual_no_emite_null');
        $link->exec('UPDATE fc_factura SET '.$sql.' WHERE id=1');
        exigir_global($leer(1)===array_fill_keys($campos,''),'update_persiste_cadena_vacia');
    });
    caso_global('lectura_vacia_normalizada_null',function () use ($servicio,$leer,$campos) {
        exigir_global($servicio->normaliza_registro($leer(1))===array_fill_keys($campos,null),'lectura_vacia_normalizada');
    });
    caso_global('ordinaria_sin_global',function () use ($servicio,$leer,$comprobante,$emisor,$receptor) {
        $ordinario=$receptor;$ordinario['rfc']='AAA010101AAA';$ordinario['nombre']='RECEPTOR FICTICIO';
        exigir_global($servicio->resolver($leer(1),$comprobante,$emisor,$ordinario)===[],'ordinaria_sin_nodo');
    });
    caso_global('publico_sin_periodo_rechazado',function () use ($servicio,$leer,$comprobante,$emisor,$receptor) {
        $servicio->resolver($leer(1),$comprobante,$emisor,$receptor);
        exigir_global(errores::$error,'publico_requiere_periodo');
        errores::$error=false;
    });
    $nodo=$servicio->resolver($periodo,$comprobante,$emisor,$receptor);
    exigir_global(!errores::$error,'resolver_periodo');
    caso_global('json_real_con_informacion_global',function () use ($servicio,$comprobante,$conceptos,$emisor,$receptor,$nodo) {
        $generado=(new cfdis())->ingreso_json($comprobante,$conceptos,$emisor,[],$receptor);
        exigir_global(!errores::$error && is_string($generado),'json_generador_real');
        $datos=json_decode($servicio->aplicar($generado,'json',$nodo),true,flags:JSON_THROW_ON_ERROR);
        exigir_global($datos['Comprobante']['InformacionGlobal']===$nodo,'json_nodo_real');
        exigir_global($datos['Comprobante']['Receptor']['Nombre']==='PUBLICO EN GENERAL','json_receptor_conservado');
    });
    caso_global('xml_real_con_informacion_global',function () use ($servicio,$comprobante,$conceptos,$emisor,$receptor,$nodo) {
        $generado=(new cfdis())->ingreso($comprobante,$conceptos,$emisor,[],$receptor);
        exigir_global(!errores::$error && is_string($generado),'xml_generador_real');
        $dom=new DOMDocument();
        exigir_global($dom->loadXML($servicio->aplicar($generado,'xml',$nodo),LIBXML_NONET),'xml_leible');
        exigir_global($dom->documentElement->firstElementChild->localName==='InformacionGlobal','xml_orden_esquema');
        foreach ($nodo as $campo=>$valor) exigir_global($dom->documentElement->firstElementChild->getAttribute($campo)===$valor,'xml_atributo_global');
    });
    exigir_global($casos===$registro['casos'],'identidades_casos');
    $codigo=0;
} catch(Throwable $error) {
    $fallo=['tipo'=>get_class($error),'codigo'=>$error instanceof RuntimeException?$error->getMessage():'fallo_integracion'];
} finally {
    if ($link->inTransaction()) $rollback=$link->rollBack();
}
$ruido=ob_get_clean();
$restantes=(int)$link->query('SELECT COUNT(*) FROM fc_factura')->fetchColumn();
if (!$rollback || $restantes!==0 || $ruido!=='') $codigo=1;
echo json_encode(['estado'=>$codigo===0?'PASS_INFORMACION_GLOBAL_BD':'BLOQUEADO',
    'casos_ejecutados'=>count($casos),'casos'=>$casos,'aserciones'=>$aserciones,'fuentes'=>$fuentes,
    'rollback_confirmado'=>$rollback,'filas_restantes'=>$restantes,'salida_interna_presente'=>$ruido!=='',
    'fallo'=>$fallo??null,'alcance'=>$registro['limites']],JSON_UNESCAPED_UNICODE),PHP_EOL;
exit($codigo);
