<?php
declare(strict_types=1);
namespace gamboamartin\errores {
    class errores {
        public static bool $error = false;
        public function error(string $mensaje, mixed $data): array { self::$error = true; return ['error'=>true,'mensaje'=>$mensaje,'data'=>$data]; }
    }
}
namespace config { class generales { public string $proyecto_custom_pdf = ''; public string $path_base = '/qa/'; } }
namespace gamboamartin\administrador\models {
    class adm_usuario {
        public function __construct(\PDO $link) {}
        public function tengo_permiso(string $adm_accion, string $adm_seccion): array|bool {
            $valor=$GLOBALS['permisos'][$adm_seccion.':'.$adm_accion]??false;
            if ($valor==='error') return (new \gamboamartin\errores\errores())->error('Error ficticio de permisos', []);
            return $valor;
        }
    }
}
namespace gamboamartin\documento\models {
    class doc_documento {
        public function __construct(\PDO $link) {}
        public function alta_documento(array $registro, array $file): \stdClass { $GLOBALS['ops'][]='guardar_documento'; return (object)['registro_id'=>17]; }
    }
}
namespace gamboamartin\facturacion\models {
    class _doc {
        public \PDO $link;
        public function __construct(\PDO $link) { $this->link=$link; }
        public function existe(array $filtro): bool { $GLOBALS['ops'][]='consultar_documento'; return true; }
        public function elimina_con_filtro_and(array $filtro): array { $GLOBALS['ops'][]='reemplazar_documento'; return []; }
        public function alta_registro(array $registro): \stdClass { $GLOBALS['ops'][]='guardar_relacion'; return (object)['registro_id'=>18,'registro_obj'=>(object)['doc_documento_ruta_absoluta'=>'/qa/guardado.pdf']]; }
    }
    class _transacciones_fc {
        public string $tabla='fc_factura'; public \PDO $link;
        public function __construct(\PDO $link) { $this->link=$link; }
        public function registro(int $registro_id, bool $retorno_obj): \stdClass { $GLOBALS['ops'][]='leer_factura'; return (object)[$this->tabla.'_folio'=>'FICTICIO']; }
    }
    class _partida {} class _cuenta_predial {} class _relacion {} class _relacionada {} class _data_impuestos {} class _sellado {} class _uuid_ext {}
    class _formato_pdf_complemento { public static function seleccionar(object $g): string { return 'normal'; } }
    class _pdf {
        public function pdf(bool $descarga, bool $guarda, \PDO $link, _doc $modelo_documento, _transacciones_fc $modelo_entidad,
            _partida $modelo_partida, _cuenta_predial $modelo_predial, _relacion $modelo_relacion, _relacionada $modelo_relacionada,
            _data_impuestos $modelo_retencion, _sellado $modelo_sellado, _data_impuestos $modelo_traslado, _uuid_ext $modelo_uuid_ext, int $registro_id): array|string {
            $GLOBALS['ops'][]=['render',$descarga,$guarda,$modelo_entidad->tabla,$registro_id];
            if ($GLOBALS['fallar_pdf']??false) return (new \gamboamartin\errores\errores())->error('PDF ficticio inválido', []);
            if ($descarga) echo '%PDF-fixture';
            return $descarga ? 'FICTICIO' : '/qa/guardado.pdf';
        }
    }
}
namespace gamboamartin\facturacion\models\_PDF\aguaban { class _factura_and_nota_credito extends \gamboamartin\facturacion\models\_pdf {} }
namespace gamboamartin\facturacion\controllers {
    class _base_system {
        public \PDO $link; public object $obj_link; public mixed $registro; public string $seccion='fc_factura'; public int $registro_id=7;
        public function retorno_error(string $mensaje, mixed $data, bool $header, bool $ws): array { return ['error'=>true,'mensaje'=>$mensaje,'data'=>$data]; }
    }
    require dirname(__DIR__,2).'/controllers/_base_system_fc.php';
    require dirname(__DIR__,2).'/controllers/_doctos.php';
    require dirname(__DIR__,2).'/controllers/controlador_fc_factura.php';
    require dirname(__DIR__,2).'/controllers/controlador_fc_nota_credito.php';
    require dirname(__DIR__,2).'/controllers/controlador_fc_complemento_pago.php';
    class ControladorPrueba extends _base_system_fc {
        public function __construct() {
            $this->link=(new \ReflectionClass(\PDO::class))->newInstanceWithoutConstructor();
            $this->obj_link=new class {
                public array $llamadas=[];
                public function link_con_id(string $accion, \PDO $link, int $registro_id, string $seccion): array|string {
                    $this->llamadas[]=$accion;
                    if (($GLOBALS['fallar_link']??'')===$accion) return (new \gamboamartin\errores\errores())->error('Link ficticio inválido', []);
                    return 'index.php?seccion='.$seccion.'&accion='.$accion.'&registro_id='.$registro_id;
                }
            };
            $this->modelo_documento=new \gamboamartin\facturacion\models\_doc($this->link);
            $this->modelo_entidad=new \gamboamartin\facturacion\models\_transacciones_fc($this->link);
            $this->modelo_partida=new \gamboamartin\facturacion\models\_partida();
            $this->modelo_predial=new \gamboamartin\facturacion\models\_cuenta_predial();
            $this->modelo_relacion=new \gamboamartin\facturacion\models\_relacion();
            $this->modelo_relacionada=new \gamboamartin\facturacion\models\_relacionada();
            $this->modelo_retencion=new \gamboamartin\facturacion\models\_data_impuestos();
            $this->modelo_sello=new \gamboamartin\facturacion\models\_sellado();
            $this->modelo_traslado=new \gamboamartin\facturacion\models\_data_impuestos();
            $this->modelo_uuid_ext=new \gamboamartin\facturacion\models\_uuid_ext();
        }
        public function enlaces(): array|\stdClass { return $this->datos_descarga_por_separado(false); }
        public function entidad(string $seccion): void { $this->seccion=$seccion; $this->modelo_entidad->tabla=$seccion; }
    }
}
namespace {
    use gamboamartin\facturacion\controllers\ControladorPrueba;
    use gamboamartin\errores\errores;
    function comprobar(bool $ok,string $mensaje):void { if(!$ok)throw new \RuntimeException($mensaje); }
    if (($argv[1]??'')==='--stream') {
        $GLOBALS['ops']=[]; $c=new ControladorPrueba();
        $c->genera_pdf(header:true); echo 'HTML_QUE_NO_DEBE_ENVIARSE'; exit(9);
    }
    $casos=[];
    foreach (['fc_factura','fc_nota_credito','fc_complemento_pago'] as $seccion) {
        $real='gamboamartin\\facturacion\\controllers\\controlador_'.$seccion;
        $nativo=(new \ReflectionClass($real))->newInstanceWithoutConstructor(); $fixture=new ControladorPrueba();
        $nativo->link=$fixture->link; $nativo->obj_link=$fixture->obj_link; $nativo->seccion=$seccion; $nativo->registro_id=7;
        $GLOBALS['permisos']=[$seccion.':descarga_xml'=>true,$seccion.':genera_pdf'=>true];
        $r=$nativo->descargar_por_separado(false);
        comprobar($r instanceof \stdClass && str_contains($r->descarga_xml,'seccion='.$seccion.'&') && str_contains($r->genera_pdf,'seccion='.$seccion.'&'),'Wrapper nativo no delega sección');
        $casos[]=$seccion.'/accion_nativa';
        foreach ([[true,true],[true,false],[false,true],[false,false]] as [$xml,$pdf]) {
            errores::$error=false; $c=new ControladorPrueba(); $c->entidad($seccion);
            $GLOBALS['permisos']=[$seccion.':descarga_xml'=>$xml,$seccion.':genera_pdf'=>$pdf];
            $r=$c->enlaces();
            comprobar($r instanceof \stdClass && ($r->descarga_xml!=='')===$xml && ($r->genera_pdf!=='')===$pdf,'Enlaces no corresponden al permiso');
            foreach (['descarga_xml','genera_pdf'] as $a) if($r->$a!=='') comprobar(str_contains($r->$a,'seccion='.$seccion.'&accion='.$a.'&registro_id=7'),'Enlace de otra sección/registro');
            comprobar(count($c->obj_link->llamadas)===(int)$xml+(int)$pdf,'Generó enlace denegado');
            $casos[]=$seccion.'/permisos_'.(int)$xml.(int)$pdf;
        }
        foreach ([false,true] as $guarda) {
            errores::$error=false;$GLOBALS['ops']=[];$c=new ControladorPrueba();$c->entidad($seccion);
            ob_start();$r=$c->genera_pdf(header:false,descarga:true,guarda:$guarda);$stream=ob_get_clean();
            comprobar($stream==='%PDF-fixture' && $r==='FICTICIO' && count($GLOBALS['ops'])===1,'La descarga accedió a persistencia después del PDF');
            $casos[]=$seccion.'/descarga_sin_persistencia_'.(int)$guarda;
        }
        errores::$error=false;$GLOBALS['ops']=[];$c=new ControladorPrueba();$c->entidad($seccion);
        $r=$c->genera_pdf(header:false,descarga:false,guarda:true);
        comprobar($r instanceof \stdClass && $r->registro_id===18 && count($GLOBALS['ops'])===6,'Se perdió el guardado de PDF para adjuntos');
        $casos[]=$seccion.'/guardado_conservado';
    }
    errores::$error=false;$GLOBALS['permisos']=['fc_factura:descarga_xml'=>'error'];$c=new ControladorPrueba();
    comprobar(is_array($c->enlaces()) && errores::$error && $c->obj_link->llamadas===[],'No propagó error de ACL');$casos[]='error_acl';
    errores::$error=false;$GLOBALS['permisos']=['fc_factura:descarga_xml'=>true];$GLOBALS['fallar_link']='descarga_xml';$c=new ControladorPrueba();
    comprobar(is_array($c->enlaces()) && errores::$error,'No propagó error de enlace');$casos[]='error_link';unset($GLOBALS['fallar_link']);
    errores::$error=false;$GLOBALS['ops']=[];$GLOBALS['fallar_pdf']=true;$c=new ControladorPrueba();
    comprobar(is_array($c->genera_pdf(header:false)) && errores::$error && count($GLOBALS['ops'])===1,'Persistió después de error PDF');$casos[]='error_pdf';
    echo json_encode(['estado'=>'PASS_DESCARGAS_CONTROLADORES','casos'=>$casos],JSON_THROW_ON_ERROR),PHP_EOL;
}
