<?php
declare(strict_types=1);

namespace gamboamartin\facturacion\models;

use DOMDocument;
use DOMElement;
use DOMXPath;
use InvalidArgumentException;
use PDO;
use RuntimeException;

/** Lectura para impresión: el XML conserva la autoridad de los datos fiscales históricos. */
final class _datos_pdf_complemento
{
    public function cargar(PDO $link, int $id, string $raiz): array
    {
        if ($id <= 0) { throw new InvalidArgumentException('El complemento de pago no es válido.'); }
        $cabeceras = $this->filas($link, "SELECT c.*,e.rfc AS emisor_rfc,e.razon_social AS emisor_nombre,
            e.telefono_1 AS emisor_telefono,rf.codigo AS emisor_regimen,cl.rfc AS receptor_rfc,
            cl.razon_social AS receptor_nombre,rfc.codigo AS receptor_regimen,u.codigo AS uso_cfdi,
            cs.calle,cs.numero_exterior,cs.numero_interior,cs.colonia,cs.municipio,cs.estado,cs.cp AS receptor_cp,
            cp.descripcion AS cp_expedicion
            FROM fc_complemento_pago c INNER JOIN fc_csd cert ON cert.id=c.fc_csd_id
            INNER JOIN org_sucursal os ON os.id=cert.org_sucursal_id INNER JOIN org_empresa e ON e.id=os.org_empresa_id
            INNER JOIN com_sucursal cs ON cs.id=c.com_sucursal_id INNER JOIN com_cliente cl ON cl.id=cs.com_cliente_id
            LEFT JOIN cat_sat_regimen_fiscal rf ON rf.id=c.cat_sat_regimen_fiscal_id
            LEFT JOIN cat_sat_regimen_fiscal rfc ON rfc.id=cl.cat_sat_regimen_fiscal_id
            LEFT JOIN cat_sat_uso_cfdi u ON u.id=c.cat_sat_uso_cfdi_id
            LEFT JOIN dp_calle_pertenece dcp ON dcp.id=c.dp_calle_pertenece_id
            LEFT JOIN dp_colonia_postal dcol ON dcol.id=dcp.dp_colonia_postal_id
            LEFT JOIN dp_cp cp ON cp.id=dcol.dp_cp_id WHERE c.id=?", [$id]);
        if (count($cabeceras) !== 1) { throw new RuntimeException('No se encontró un complemento de pago único.'); }
        $cabecera = $cabeceras[0];
        $sellados = $this->filas($link, 'SELECT uuid,cadena_complemento_sat FROM fc_cfdi_sellado_cp WHERE fc_complemento_pago_id=?', [$id]);
        if (count($sellados) > 1) { throw new RuntimeException('El complemento tiene más de un registro de certificación.'); }
        $documentos = $this->filas($link, "SELECT t.descripcion AS tipo,d.ruta_absoluta AS ruta
            FROM fc_complemento_pago_documento cd INNER JOIN doc_documento d ON d.id=cd.doc_documento_id
            INNER JOIN doc_tipo_documento t ON t.id=d.doc_tipo_documento_id
            WHERE cd.fc_complemento_pago_id=? AND t.descripcion IN ('xml_sin_timbrar','qr_cfdi') ORDER BY cd.id", [$id]);
        $rutas = [];
        foreach ($documentos as $documento) {
            if (isset($rutas[$documento['tipo']])) { throw new RuntimeException('El complemento tiene archivos fiscales duplicados.'); }
            $rutas[$documento['tipo']] = $this->rutaLocal((string)$documento['ruta'], $raiz);
        }
        $uuid = (string)($sellados[0]['uuid'] ?? '');
        if (isset($rutas['xml_sin_timbrar'])) {
            $xml = @file_get_contents($rutas['xml_sin_timbrar']);
            if ($xml === false) { throw new RuntimeException('No se pudo leer el XML del complemento.'); }
            $datos = self::desde_xml($xml, (string)($sellados[0]['cadena_complemento_sat'] ?? ''), (string)($rutas['qr_cfdi'] ?? ''));
            if (strcasecmp($datos['emisor']['rfc'], (string)$cabecera['emisor_rfc']) !== 0
                || strcasecmp($datos['receptor']['rfc'], (string)$cabecera['receptor_rfc']) !== 0
                || ($uuid !== '' && strcasecmp($uuid, $datos['fiscales']['uuid']) !== 0)) {
                throw new RuntimeException('El XML no coincide con el emisor, receptor o certificación del complemento.');
            }
            if (self::folio($cabecera['serie'], $cabecera['folio']) !== $datos['folio']) {
                throw new RuntimeException('La serie y folio del XML no coinciden con el complemento.');
            }
            return $datos;
        }
        if ($uuid !== '' || strtoupper(trim((string)($cabecera['etapa'] ?? ''))) === 'TIMBRADO'
            || preg_match('/^[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}$/iD', (string)($cabecera['folio_fiscal'] ?? ''))) {
            throw new RuntimeException('Falta el XML certificado para imprimir este complemento timbrado.');
        }
        return $this->desdeBd($link, $id, $cabecera);
    }

    /** El parser no accede a red, archivos, configuración ni base de datos. */
    public static function desde_xml(string $xml, string $cadenaOriginal = '', string $qr = ''): array
    {
        if ($xml === '' || strlen($xml) > 16 * 1024 * 1024 || preg_match('/<!\s*(?:DOCTYPE|ENTITY)\b/i', $xml)) {
            throw new InvalidArgumentException('El XML del complemento no es válido o contiene declaraciones no permitidas.');
        }
        $documento = new DOMDocument();
        $anterior = libxml_use_internal_errors(true);
        try { $valido = $documento->loadXML($xml, LIBXML_NONET); }
        finally { libxml_clear_errors(); libxml_use_internal_errors($anterior); }
        if (!$valido || $documento->doctype !== null) { throw new InvalidArgumentException('No se pudo interpretar el XML del complemento.'); }
        $xp = new DOMXPath($documento);
        $xp->registerNodeNamespaces = false;
        $xp->registerNamespace('c', 'http://www.sat.gob.mx/cfd/4');
        $xp->registerNamespace('p', 'http://www.sat.gob.mx/Pagos20');
        $xp->registerNamespace('t', 'http://www.sat.gob.mx/TimbreFiscalDigital');
        $root = $documento->documentElement;
        if (!$root || $root->namespaceURI !== 'http://www.sat.gob.mx/cfd/4' || $root->localName !== 'Comprobante'
            || $root->getAttribute('TipoDeComprobante') !== 'P' || $root->getAttribute('Version') !== '4.0'
            || $xp->query('/c:Comprobante/c:Complemento/p:Pagos[@Version="2.0"]')->length !== 1) {
            throw new InvalidArgumentException('Se requiere un CFDI 4.0 de pago con complemento Pagos 2.0.');
        }
        $emisor = self::unico($xp, '/c:Comprobante/c:Emisor');
        $receptor = self::unico($xp, '/c:Comprobante/c:Receptor');
        $datos = self::vacio();
        $datos['folio'] = self::folio($root->getAttribute('Serie'), $root->getAttribute('Folio'));
        $datos['fecha_emision'] = $root->getAttribute('Fecha');
        $datos['nombre_documento'] = $root->getAttribute('Serie').$root->getAttribute('Folio');
        $datos['total_comprobante'] = $root->getAttribute('Total');
        $datos['emisor'] = ['nombre'=>$emisor->getAttribute('Nombre'),'rfc'=>$emisor->getAttribute('Rfc'),
            'regimen'=>$emisor->getAttribute('RegimenFiscal'),'cp'=>$root->getAttribute('LugarExpedicion'),'direccion'=>'','telefono'=>''];
        $datos['receptor'] = ['nombre'=>$receptor->getAttribute('Nombre'),'rfc'=>$receptor->getAttribute('Rfc'),
            'regimen'=>$receptor->getAttribute('RegimenFiscalReceptor'),'uso_cfdi'=>$receptor->getAttribute('UsoCFDI'),
            'cp'=>$receptor->getAttribute('DomicilioFiscalReceptor'),'direccion'=>''];
        foreach ($xp->query('/c:Comprobante/c:Complemento/p:Pagos/p:Pago') as $pago) {
            $fila = ['fecha'=>$pago->getAttribute('FechaPago'),'forma_pago'=>$pago->getAttribute('FormaDePagoP'),
                'moneda'=>$pago->getAttribute('MonedaP'),'tipo_cambio'=>$pago->getAttribute('TipoCambioP'),
                'monto'=>$pago->getAttribute('Monto'),'doctos'=>[], 'impuestos'=>[]];
            foreach ($xp->query('p:DoctoRelacionado', $pago) as $docto) {
                $d = [];
                foreach (['uuid'=>'IdDocumento','serie'=>'Serie','folio'=>'Folio','moneda'=>'MonedaDR',
                    'equivalencia'=>'EquivalenciaDR','objeto_impuesto'=>'ObjetoImpDR','parcialidad'=>'NumParcialidad',
                    'saldo_anterior'=>'ImpSaldoAnt','importe_pagado'=>'ImpPagado','saldo_insoluto'=>'ImpSaldoInsoluto'] as $k=>$atributo) {
                    $d[$k] = $docto->getAttribute($atributo);
                }
                $d['impuestos'] = self::impuestosXml($xp, $docto, 'DR');
                $fila['doctos'][] = $d;
            }
            $fila['impuestos'] = self::impuestosXml($xp, $pago, 'P');
            $datos['pagos'][] = $fila;
        }
        if ($datos['pagos'] === []) { throw new InvalidArgumentException('El XML no contiene pagos para imprimir.'); }
        $totales = self::unico($xp, '/c:Comprobante/c:Complemento/p:Pagos/p:Totales');
        foreach ($totales->attributes as $a) { $datos['totales'][$a->name] = $a->value; }
        foreach ($xp->query('/c:Comprobante/c:CfdiRelacionados') as $relacion) {
            foreach ($xp->query('c:CfdiRelacionado', $relacion) as $r) {
                $datos['relaciones'][] = ['tipo'=>$relacion->getAttribute('TipoRelacion'),'uuid'=>$r->getAttribute('UUID')];
            }
        }
        $timbres = $xp->query('/c:Comprobante/c:Complemento/t:TimbreFiscalDigital');
        if ($timbres->length > 1) { throw new InvalidArgumentException('El XML contiene más de un timbre fiscal.'); }
        $datos['fiscales']['certificado_emisor'] = $root->getAttribute('NoCertificado');
        $datos['sellos']['cfdi'] = $root->getAttribute('Sello');
        if ($timbres->length === 1) {
            $t = $timbres->item(0);
            foreach (['UUID','FechaTimbrado','RfcProvCertif','NoCertificadoSAT','SelloSAT','SelloCFD'] as $campo) {
                if ($t->getAttribute($campo) === '') { throw new InvalidArgumentException('El timbre fiscal del XML está incompleto.'); }
            }
            $datos['estado'] = 'timbrado';
            foreach (['uuid'=>'UUID','fecha_certificacion'=>'FechaTimbrado','certificado_sat'=>'NoCertificadoSAT','rfc_proveedor'=>'RfcProvCertif'] as $k=>$a) {
                $datos['fiscales'][$k] = $t->getAttribute($a);
            }
            $datos['sellos']['sat'] = $t->getAttribute('SelloSAT');
            $datos['sellos']['cadena_original'] = $cadenaOriginal !== '' ? $cadenaOriginal : self::cadenaTimbre($t);
            $datos['qr'] = $qr;
            $datos['qr_url'] = self::urlQr($root, $t, $datos);
        }
        return $datos;
    }

    private static function impuestosXml(DOMXPath $xp, DOMElement $padre, string $sufijo): array
    {
        $resultado = [];
        foreach (['Traslado'=>'Traslados','Retencion'=>'Retenciones'] as $nodo=>$grupo) {
            foreach ($xp->query('p:Impuestos'.$sufijo.'/p:'.$grupo.$sufijo.'/p:'.$nodo.$sufijo, $padre) as $imp) {
                $fila = ['tipo'=>$nodo === 'Traslado' ? 'Traslado' : 'Retención'];
                foreach (['base'=>'Base','impuesto'=>'Impuesto','factor'=>'TipoFactor','tasa'=>'TasaOCuota','importe'=>'Importe'] as $k=>$a) {
                    $fila[$k] = $imp->getAttribute($a.$sufijo);
                }
                $resultado[] = $fila;
            }
        }
        return $resultado;
    }

    private static function unico(DOMXPath $xp, string $ruta): DOMElement
    {
        $nodos = $xp->query($ruta);
        if ($nodos->length !== 1) { throw new InvalidArgumentException('El XML no contiene una estructura de pago única y completa.'); }
        return $nodos->item(0);
    }

    private static function cadenaTimbre(DOMElement $t): string
    {
        $campos = ['Version','UUID','FechaTimbrado','RfcProvCertif'];
        if ($t->hasAttribute('Leyenda')) { $campos[] = 'Leyenda'; }
        array_push($campos, 'SelloCFD', 'NoCertificadoSAT');
        return '||'.implode('|', array_map(static fn(string $k): string=>preg_replace('/\\s+/u', ' ', trim($t->getAttribute($k))), $campos)).'||';
    }

    private static function urlQr(DOMElement $comprobante, DOMElement $timbre, array $datos): string
    {
        $total = $comprobante->getAttribute('Total');
        if (!preg_match('/^\d+(?:\.\d{1,6})?$/D', $total)) {
            throw new InvalidArgumentException('El total del XML no permite construir el QR fiscal.');
        }
        [$entero, $decimales] = array_pad(explode('.', $total, 2), 2, '');
        $totalFijo = (ltrim($entero, '0') ?: '0').'.'.str_pad($decimales, 6, '0');
        return 'https://verificacfdi.facturaelectronica.sat.gob.mx/default.aspx?'.http_build_query([
            'id'=>$datos['fiscales']['uuid'],'re'=>$datos['emisor']['rfc'],'rr'=>$datos['receptor']['rfc'],
            'tt'=>$totalFijo,'fe'=>substr($timbre->getAttribute('SelloCFD'), -8)], '', '&', PHP_QUERY_RFC3986);
    }

    private static function folio(mixed $serie, mixed $folio): string
    {
        $s = trim((string)$serie); $f = trim((string)$folio);
        return $s !== '' && !str_starts_with($f, $s.'-') ? $s.'-'.$f : $f;
    }

    private static function vacio(): array
    {
        return ['folio'=>'','fecha_emision'=>'','estado'=>'pendiente','emisor'=>[],'receptor'=>[], 'pagos'=>[],
            'totales'=>[], 'fiscales'=>['uuid'=>'','fecha_certificacion'=>'','certificado_emisor'=>'','certificado_sat'=>'','rfc_proveedor'=>''],
            'sellos'=>['cfdi'=>'','sat'=>'','cadena_original'=>''], 'relaciones'=>[], 'qr'=>'', 'qr_url'=>''];
    }

    private function filas(PDO $link, string $sql, array $parametros): array
    {
        $q = $link->prepare($sql); $q->execute($parametros); return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    private function rutaLocal(string $ruta, string $raiz): string
    {
        $base = realpath($raiz); $real = realpath($ruta);
        if ($base === false || $real === false || !str_starts_with($real, rtrim($base, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR)
            || !is_file($real) || !is_readable($real) || filesize($real) > 16 * 1024 * 1024) {
            throw new RuntimeException('El archivo fiscal no está disponible dentro de esta instalación.');
        }
        return $real;
    }

    private function desdeBd(PDO $link, int $id, array $c): array
    {
        $datos = self::vacio();
        $datos['folio'] = self::folio($c['serie'], $c['folio']);
        $datos['nombre_documento'] = (string)$c['serie'].(string)$c['folio'];
        $datos['total_comprobante'] = (string)$c['total'];
        $datos['fecha_emision'] = (string)$c['fecha'];
        $datos['emisor'] = ['nombre'=>(string)$c['emisor_nombre'],'rfc'=>(string)$c['emisor_rfc'],
            'regimen'=>(string)$c['emisor_regimen'],'cp'=>(string)$c['cp_expedicion'],'direccion'=>'','telefono'=>(string)$c['emisor_telefono']];
        $direccion = implode(' ', array_filter(array_map(static fn(string $k):string=>trim((string)($c[$k]??'')),
            ['calle','numero_exterior','numero_interior','colonia','municipio','estado'])));
        $datos['receptor'] = ['nombre'=>(string)$c['receptor_nombre'],'rfc'=>(string)$c['receptor_rfc'],
            'regimen'=>(string)$c['receptor_regimen'],'uso_cfdi'=>(string)$c['uso_cfdi'],'cp'=>(string)$c['receptor_cp'],'direccion'=>$direccion];
        $pagos = $this->filas($link, "SELECT pp.id,pp.fecha_pago,pp.monto,fp.codigo AS forma,m.codigo AS moneda,tc.monto AS tipo_cambio
            FROM fc_pago_pago pp INNER JOIN fc_pago p ON p.id=pp.fc_pago_id
            INNER JOIN cat_sat_forma_pago fp ON fp.id=pp.cat_sat_forma_pago_id
            INNER JOIN cat_sat_moneda m ON m.id=pp.cat_sat_moneda_id INNER JOIN com_tipo_cambio tc ON tc.id=pp.com_tipo_cambio_id
            WHERE p.fc_complemento_pago_id=? ORDER BY p.id,pp.id", [$id]);
        foreach ($pagos as $pago) {
            $fila = ['fecha'=>(string)$pago['fecha_pago'],'forma_pago'=>(string)$pago['forma'],'moneda'=>(string)$pago['moneda'],
                'tipo_cambio'=>(string)$pago['tipo_cambio'],'monto'=>(string)$pago['monto'],'doctos'=>[],
                'impuestos'=>$this->impuestosBd($link, (int)$pago['id'], 'p')];
            $doctos = $this->filas($link, "SELECT dr.*,f.serie,f.folio,f.folio_fiscal,m.codigo AS moneda,oi.codigo AS objeto
                FROM fc_docto_relacionado dr INNER JOIN fc_factura f ON f.id=dr.fc_factura_id
                INNER JOIN cat_sat_moneda m ON m.id=f.cat_sat_moneda_id INNER JOIN cat_sat_obj_imp oi ON oi.id=dr.cat_sat_obj_imp_id
                WHERE dr.fc_pago_pago_id=? ORDER BY dr.id", [(int)$pago['id']]);
            foreach ($doctos as $d) {
                $fila['doctos'][] = ['uuid'=>(string)$d['folio_fiscal'],'serie'=>(string)$d['serie'],'folio'=>(string)$d['folio'],
                    'moneda'=>(string)$d['moneda'],'equivalencia'=>(string)$d['equivalencia_dr'],'objeto_impuesto'=>(string)$d['objeto'],
                    'parcialidad'=>(string)$d['num_parcialidad'],'saldo_anterior'=>(string)$d['imp_saldo_ant'],
                    'importe_pagado'=>(string)$d['imp_pagado'],'saldo_insoluto'=>(string)$d['imp_saldo_insoluto'],
                    'impuestos'=>$this->impuestosBd($link, (int)$d['id'], 'dr')];
            }
            $datos['pagos'][] = $fila;
        }
        $datos['relaciones'] = $this->filas($link, "SELECT tr.codigo AS tipo,c.folio_fiscal AS uuid
            FROM fc_relacion_cp r INNER JOIN cat_sat_tipo_relacion tr ON tr.id=r.cat_sat_tipo_relacion_id
            INNER JOIN fc_complemento_pago_relacionada cr ON cr.fc_relacion_cp_id=r.id
            INNER JOIN fc_complemento_pago c ON c.id=cr.fc_complemento_pago_id
            WHERE r.fc_complemento_pago_id=? ORDER BY r.id,cr.id", [$id]);
        $externas = $this->filas($link, "SELECT tr.codigo AS tipo,u.uuid
            FROM fc_relacion_cp r INNER JOIN cat_sat_tipo_relacion tr ON tr.id=r.cat_sat_tipo_relacion_id
            INNER JOIN fc_uuid_cp uc ON uc.fc_relacion_cp_id=r.id INNER JOIN fc_uuid u ON u.id=uc.fc_uuid_id
            WHERE r.fc_complemento_pago_id=? ORDER BY r.id,uc.id", [$id]);
        array_push($datos['relaciones'], ...$externas);
        return $datos;
    }

    /** Tabla y columnas solo provienen de estas dos listas constantes, nunca de una petición. */
    private function impuestosBd(PDO $link, int $id, string $nivel): array
    {
        $resultado = [];
        $filtro = $nivel === 'p' ? 'fc_pago_pago_id' : 'fc_docto_relacionado_id';
        foreach (['traslado'=>'Traslado','retencion'=>'Retención'] as $nombre=>$tipo) {
            $padre = 'fc_'.$nombre.'_'.$nivel;
            $parte = $padre.'_part';
            $impuesto = 'fc_impuesto_'.$nivel;
            $base = 'base_'.$nivel; $importe = 'importe_'.$nivel;
            $filas = $this->filas($link, "SELECT detalle.$base AS base,ti.codigo AS impuesto,tf.codigo AS factor,
                cf.factor AS tasa,detalle.$importe AS importe
                FROM $parte detalle INNER JOIN $padre padre ON padre.id=detalle.{$padre}_id
                INNER JOIN $impuesto i ON i.id=padre.{$impuesto}_id
                INNER JOIN cat_sat_tipo_impuesto ti ON ti.id=detalle.cat_sat_tipo_impuesto_id
                INNER JOIN cat_sat_tipo_factor tf ON tf.id=detalle.cat_sat_tipo_factor_id
                INNER JOIN cat_sat_factor cf ON cf.id=detalle.cat_sat_factor_id
                WHERE i.$filtro=? ORDER BY detalle.id", [$id]);
            foreach ($filas as $fila) { $resultado[] = ['tipo'=>$tipo]+array_map(static fn($v):string=>(string)$v, $fila); }
        }
        return $resultado;
    }
}
