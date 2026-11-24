<?php
declare(strict_types=1);
namespace config {
    class generales {
        public static string $base = '';
        public static mixed $formato = 'aguaban';
        public static string $proyecto = '';
        public static bool $titulo = false;
        public string $path_base;
        public mixed $formato_pdf_complemento_pago;
        public string $proyecto_custom_pdf;
        public bool $cambios_titulo_pdf;
        public function __construct() {$this->path_base=self::$base;$this->formato_pdf_complemento_pago=self::$formato;$this->proyecto_custom_pdf=self::$proyecto;$this->cambios_titulo_pdf=self::$titulo;}
    }
}
namespace gamboamartin\errores {
    class errores {
        public static bool $error = false;
        public function error(string $mensaje,mixed $data=[]): array {self::$error=true;return ['mensaje'=>$mensaje,'data'=>$data];}
    }
}
namespace gamboamartin\facturacion\models {
    class DiscoEntregaPdf {
        public static bool $fallarPublicacion=false;
        public static int $fallosRetiroTemporal=0;
    }
    function link(string $origen,string $destino):bool {
        return !DiscoEntregaPdf::$fallarPublicacion && \link($origen,$destino);
    }
    function unlink(string $ruta):bool {
        if (str_starts_with(basename($ruta),'.complemento-') && DiscoEntregaPdf::$fallosRetiroTemporal>0) {
            DiscoEntregaPdf::$fallosRetiroTemporal--;return false;
        }
        return \unlink($ruta);
    }
    class _doc {
        public static array $registros=[];
        public static bool $fallarRegistro=false;
        public function __construct(public \PDO $link){}
        public function existe(array $filtro):bool{return false;}
        public function alta_registro(array $registro):array|\stdClass{
            if(self::$fallarRegistro){return (new \gamboamartin\errores\errores())->error('Fallo simulado de registro');}
            self::$registros[]=$registro;return (object)['registro_obj'=>(object)($registro+['doc_documento_ruta_absoluta'=>\gamboamartin\documento\models\doc_documento::$ultimaRuta])];
        }
    }
    class _transacciones_fc {
        public int $consultas=0;
        public string $key_filtro_id='fc_complemento_pago.id';
        public function __construct(public \PDO $link,public string $tabla='fc_complemento_pago'){}
        public function get_factura(...$args):array{$this->consultas++;\gamboamartin\errores\errores::$error=true;return [];}
        public function registro(int $registro_id,bool $retorno_obj):\stdClass{return (object)[$this->tabla.'_folio'=>'000042'];}
    }
    class _partida {} class _cuenta_predial {} class _relacion {} class _relacionada {} class _data_impuestos {} class _sellado {} class _uuid_ext {}
    class _datos_pdf_complemento {
        public static array $porId=[];
        public function cargar(\PDO $link,int $id,string $raiz):array{return array_replace(['nombre_documento'=>'CP000042','total_comprobante'=>'0','receptor'=>['nombre'=>'RECEPTOR FICTICIO']],self::$porId[$id]??[]);}
    }
}
namespace gamboamartin\facturacion\models\_PDF\aguaban {
    class _complemento_pago {
        public static string $contenido="%PDF-1.4\nCONTENIDO FICTICIO PARA CONTRATO DE ENTREGA\n%%EOF";
        public static int $llamadas=0;
        public function render(array $datos):string{self::$llamadas++;return $datos['contenido_prueba']??self::$contenido;}
    }
    class _factura_and_nota_credito {
        public static int $llamadas=0;
        public function pdf(...$args):string{self::$llamadas++;return \config\generales::$base.'legado.pdf';}
    }
}
namespace gamboamartin\documento\models {
    class doc_documento {
        public static array $archivos=[];
        public static string $ultimaRuta='';
        public static bool $fallarCarga=false;
        public static bool $mismaRuta=false;
        public function __construct(\PDO $link){}
        public function alta_documento(array $registro,array $file):array|\stdClass {
            if(self::$fallarCarga){return (new \gamboamartin\errores\errores())->error('Fallo simulado de carga');}
            self::$archivos[]=['registro'=>$registro,'file'=>$file];
            self::$ultimaRuta=self::$mismaRuta?$file['tmp_name']:\config\generales::$base.'archivos/copia-'.count(self::$archivos).'.pdf';
            if(!self::$mismaRuta){if(!is_dir(dirname(self::$ultimaRuta)))mkdir(dirname(self::$ultimaRuta),0755,true);file_put_contents(self::$ultimaRuta,is_file($file['tmp_name'])?file_get_contents($file['tmp_name']):'%PDF-LEGADO');}
            return (object)['registro_id'=>88];
        }
    }
}
