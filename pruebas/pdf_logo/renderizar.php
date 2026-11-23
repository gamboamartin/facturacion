<?php
declare(strict_types=1);
namespace config {
    final class generales {
        public string $path_base;
        public string $ruta_factura_pdf;
        public function __construct() {
            $this->path_base=getenv('PDF_SOURCE').'/';
            $this->ruta_factura_pdf=getenv('PDF_TEMPLATE');
        }
    }
}
namespace {
    set_error_handler(static function(int $severity,string $message,string $file,int $line): bool {
        if (!(error_reporting() & $severity)) return false;
        throw new \ErrorException($message,0,$severity,$file,$line);
    });
    final class LogoStatement extends \PDOStatement {
        public function __construct(private array $rows) {}
        public function execute(?array $params=null): bool { return true; }
        public function fetch(int $mode=\PDO::FETCH_DEFAULT,int $orientation=\PDO::FETCH_ORI_NEXT,int $offset=0): mixed {
            return array_shift($this->rows) ?? false;
        }
        public function fetchAll(int $mode=\PDO::FETCH_DEFAULT,mixed ...$args): array { return $this->rows; }
    }
    final class LogoPDO extends \PDO {
        public array $queries=[];
        public function __construct(private string $asset) {}
        public function prepare(string $query,array $options=[]): \PDOStatement|false {
            $this->queries[]=$query;
            return str_contains($query,'FROM org_empresa')
                ? new LogoStatement([['id'=>16]])
                : new LogoStatement($this->asset===''?[]:[['ruta_relativa'=>'archivos/doc_documento/'.$this->asset]]);
        }
        public function query(string $query,?int $fetchMode=null,mixed ...$args): \PDOStatement|false {
            throw new \RuntimeException('SQL fuera del proveedor prohibido');
        }
        public function exec(string $statement): int|false { throw new \RuntimeException('Escrituras SQL prohibidas'); }
    }
    $source=getenv('PDF_SOURCE'); $output=getenv('PDF_OUTPUT'); $mode=$argv[1]; $variant=$argv[2];
    $loader=require getenv('PDF_VENDOR').'/autoload.php';
    $loader->addPsr4('gamboamartin\\facturacion\\models\\',$source.'/orm',true);
    $loader->addPsr4('gamboamartin\\facturacion\\controllers\\',$source.'/controllers',true);
    foreach (['archivos/doc_documento','archivos/tmp'] as $dir) if(!is_dir($source.'/'.$dir))mkdir($source.'/'.$dir,0700,true);
    foreach (['png'=>[160,80],'jpg'=>[135,115],'alto'=>[40,240]] as $ext=>$size) {
        $im=imagecreatetruecolor(...$size);$white=imagecolorallocate($im,255,255,255);imagefill($im,0,0,$white);
        $green=imagecolorallocate($im,0,125,120);imagefilledrectangle($im,3,3,$size[0]-4,$size[1]-4,$green);
        imagestring($im,2,7,9,'QA',imagecolorallocate($im,255,255,255));
        $path=$source.'/archivos/doc_documento/9.logo.'.$ext;
        if($ext==='jpg')imagejpeg($im,$path);else imagepng($im,$path);
        imagedestroy($im);
    }
    $asset=$variant==='sinlogo'?'':'9.logo.'.$variant;
    $pdo=new LogoPDO($asset);
    $logoDato=(new \gamboamartin\facturacion\models\_logo_pdf($source))->para_empresa($pdo,16);
    $logo=$logoDato['ruta']??'';
    if($mode==='factura'||$mode==='nota_credito') {
        require_once $source.'/orm/_pdf.php';
        $model=new \gamboamartin\facturacion\models\_pdf();
        $method=new \ReflectionMethod($model,'base_pdf');
        $table=$mode==='factura'?'fc_factura':'fc_nota_credito';
        $factura=[];
        foreach(['org_empresa_razon_social'=>'EMISOR FICTICIO','org_empresa_nombre_comercial'=>'EMPRESA QA',
            'org_empresa_rfc'=>'AAA010101AAA','org_empresa_cp_txt'=>'11560','com_cliente_razon_social'=>'CLIENTE FICTICIO',
            'com_cliente_rfc'=>'XAXX010101000','com_sucursal_calle'=>'CALLE QA','com_sucursal_numero_exterior'=>'1',
            'com_sucursal_municipio'=>'MUNICIPIO QA','com_sucursal_estado'=>'ESTADO QA','com_sucursal_cp'=>'11560',
            'cat_sat_regimen_fiscal_codigo'=>'601','cat_sat_regimen_fiscal_descripcion'=>'GENERAL DE LEY',
            'cat_sat_regimen_fiscal_cliente_codigo'=>'616','cat_sat_regimen_fiscal_cliente_descripcion'=>'SIN OBLIGACIONES',
            'cat_sat_uso_cfdi_codigo'=>'S01','cat_sat_uso_cfdi_descripcion'=>'SIN EFECTOS FISCALES',
            'cat_sat_forma_pago_codigo'=>'01','cat_sat_forma_pago_descripcion'=>'EFECTIVO',
            'cat_sat_metodo_pago_codigo'=>'PUE','cat_sat_metodo_pago_descripcion'=>'PAGO EN UNA EXHIBICION',
            'cat_sat_tipo_de_comprobante_codigo'=>$mode==='factura'?'I':'E','cat_sat_tipo_de_comprobante_descripcion'=>$mode==='factura'?'INGRESO':'EGRESO',
            'cat_sat_moneda_codigo'=>'MXN','cat_sat_moneda_descripcion'=>'PESO MEXICANO'] as $k=>$v)$factura[$k]=$v;
        foreach(['folio'=>'QA-001','fecha'=>'2026-11-23 12:00:00','observaciones'=>'DATOS SINTETICOS',
            'sub_total'=>1000,'descuento'=>0,'total_traslados'=>160,'total_retenciones'=>0,'total'=>1160] as $k=>$v)$factura[$table.'_'.$k]=$v;
        $data=(object)['fecha_timbrado'=>'2026-11-23 12:00:01','folio_fiscal'=>'00000000-0000-0000-0000-000000000000',
            'no_certificado'=>'00000000000000000000','no_certificado_sat'=>'00000000000000000000',
            'complento'=>'CADENA FICTICIA','sello_cfdi'=>'SELLO FICTICIO','sello_sat'=>'SELLO SAT FICTICIO'];
        $pdf=new \setasign\Fpdi\Fpdi();
        for($page=1;$page<=2;$page++) {
            $method->invoke($model,$table,$data,$factura,$pdf,[],$logo,'');
            $pdf->SetXY(8,100);$pdf->SetFont('Arial','',9);$pdf->Cell(150,5,'PARTIDA FICTICIA '.$page.' IMPORTE 1000.00');
        }
        $bytes=$pdf->Output('S');
    } elseif($mode==='complemento') {
        $datos=['folio'=>'QA-001','logo'=>$logoDato['uri']??'',
            'logo_ancho'=>0,'logo_alto'=>0,'qr'=>'','productos'=>[]];
        if($logo!=='') {
            $size=getimagesize($logo);$factor=min(140/$size[0],110/$size[1]);$datos['logo_ancho']=$size[0]*$factor;$datos['logo_alto']=$size[1]*$factor;
        }
        $groups=['emisor'=>['emisor','nombre','rfc','regimen','direccion','telefono'],
            'fechas'=>['fecha_emision','fecha_certificacion','cp_expedicion'],
            'receptor'=>['nombre','rfc','uso_cfdi','direccion','regimen'],
            'fiscales'=>['folio_sat','certificado_emisor','certificado_sat','leyenda','exportacion'],
            'informacion_pago'=>['fecha_pago','forma_pago','moneda','monto','tipo_cambio'],
            'pagos'=>['condiciones_pago','forma_pago','metodo_pago','moneda','tipo_comprobante'],
            'relacionados'=>['folio','folio_fiscal','importe_pagado','moneda','numero_parcialidad','saldo_anterior','saldo_insoluto','serie','tipo_cambio'],
            'sellos'=>['cadena_original','sello_cfdi','sello_sat'],
            'totales'=>['descuento','isr_retenido','iva','iva_retenido','letra','subtotal','total']];
        foreach($groups as $g=>$keys)foreach($keys as $k)$datos[$g][$k]=in_array($k,['monto','descuento','isr_retenido','iva','iva_retenido','subtotal','total','importe_pagado','saldo_anterior','saldo_insoluto','tipo_cambio'],true)?'100.00':'FICTICIO';
        ob_start();include $source.'/controllers/templates/reporte.php';$html=ob_get_clean();
        file_put_contents($output.'.html',$html);
        $options=new \Dompdf\Options();$options->set('isRemoteEnabled',false);$options->set('defaultFont','DejaVu Sans');
        $pdf=new \Dompdf\Dompdf($options);$pdf->loadHtml($html,'UTF-8');$pdf->setPaper('A4','portrait');$pdf->render();$bytes=$pdf->output();
    } else {
        require_once $source.'/orm/_timbra_nomina/_finalizacion.php';
        $count=$mode==='nomina_larga'?45:1;$percepciones='';
        for($i=1;$i<=$count;$i++)$percepciones.='<nomina12:Percepcion TipoPercepcion="001" Clave="'.$i.'" Concepto="PERCEPCION FICTICIA '.$i.'" ImporteGravado="116" ImporteExento="0"/>';
        $xml=new \SimpleXMLElement('<cfdi:Comprobante xmlns:cfdi="http://www.sat.gob.mx/cfd/4" xmlns:nomina12="http://www.sat.gob.mx/nomina12" xmlns:tfd="http://www.sat.gob.mx/TimbreFiscalDigital" Version="4.0" Serie="QA" Folio="001" Fecha="2026-11-23T12:00:00" SubTotal="116" Descuento="16" Total="100" LugarExpedicion="11560" Sello="SELLO-FICTICIO"><cfdi:Emisor Rfc="AAA010101AAA" Nombre="EMISOR FICTICIO" RegimenFiscal="601"/><cfdi:Receptor Rfc="XAXX010101000" Nombre="RECEPTOR FICTICIO"/><cfdi:Conceptos><cfdi:Concepto Cantidad="1" Descripcion="PRUEBA" ValorUnitario="116" Importe="116"/></cfdi:Conceptos><cfdi:Complemento><tfd:TimbreFiscalDigital UUID="00000000-0000-0000-0000-000000000000" FechaTimbrado="2026-11-23T12:00:00" NoCertificadoSAT="00000000000000000000" SelloCFD="SELLO FICTICIO" SelloSAT="SELLO SAT FICTICIO"/><nomina12:Nomina TipoNomina="O" FechaPago="2026-11-23" FechaInicialPago="2026-11-01" FechaFinalPago="2026-11-15" NumDiasPagados="15" TotalPercepciones="116" TotalDeducciones="16"><nomina12:Receptor NumEmpleado="001" Curp="FICTICIO"/><nomina12:Percepciones>'.$percepciones.'</nomina12:Percepciones><nomina12:Deducciones><nomina12:Deduccion TipoDeduccion="002" Clave="002" Concepto="RETENCION FICTICIA" Importe="16"/></nomina12:Deducciones></nomina12:Nomina></cfdi:Complemento></cfdi:Comprobante>');
        $model=new \gamboamartin\facturacion\models\_timbra_nomina\_finalizacion();$method=new \ReflectionMethod($model,'generarReciboNomina');
        $args=[$xml,$mode==='nomina_cancelada'];if($method->getNumberOfParameters()>=3)$args[]=$pdo;
        $bytes=$method->invokeArgs($model,$args);
    }
    if(!is_string($bytes)||!str_starts_with($bytes,'%PDF'))throw new \RuntimeException('No se generó PDF');
    file_put_contents($output,$bytes);
    echo json_encode(['estado'=>'PDF_SINTETICO_GENERADO','bytes'=>strlen($bytes),'modo'=>$mode,'variante'=>$variant]),PHP_EOL;
}
