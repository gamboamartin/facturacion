<?php
declare(strict_types=1);

namespace gamboamartin\facturacion\models\_PDF\aguaban;

use Mpdf\Mpdf;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Mpdf\Output\Destination;
use RuntimeException;

/** Representación impresa: consume datos resueltos, sin consultar ni modificar documentos. */
final class _complemento_pago
{
    private const IZQUIERDA = 15.0;
    private const ANCHO = 180.0;
    private const LIMITE = 285.0;
    private Mpdf $pdf;
    private float $y = 34.0;
    private array $datos = [];

    public function __construct(private readonly ?string $plantilla = null, private readonly ?string $tempDir = null) {}

    public function render(array $datos): string
    {
        $template = realpath($this->plantilla ?? dirname(__DIR__, 3).'/plantillas/complemento_aguaban.pdf');
        if ($template === false || !is_file($template) || !is_readable($template)) {
            throw new RuntimeException('No está disponible la plantilla del complemento de pago Aguaban.');
        }
        $base = $this->tempDir ?? sys_get_temp_dir();
        $cache = rtrim($base, '/').'/aguaban-pdf-'.bin2hex(random_bytes(12));
        if (!is_dir($base) || !is_writable($base) || !mkdir($cache, 0700)) {
            throw new RuntimeException('No fue posible preparar el espacio temporal del PDF.');
        }
        try {
            $this->datos = $datos;
            $this->pdf = new Mpdf(['mode'=>'utf-8', 'format'=>'A4', 'tempDir'=>$cache,
                'default_font'=>'dejavusans', 'default_font_size'=>7, 'margin_left'=>15,
                'margin_right'=>15, 'margin_top'=>34, 'margin_bottom'=>12]);
            $this->pdf->SetAutoPageBreak(false);
            $this->pdf->SetTitle('Complemento de pago '.$this->texto($datos['folio'] ?? ''));
            $this->pdf->setSourceFile($template);
            $pagina = $this->pdf->importPage(1);
            $size = $this->pdf->getTemplateSize($pagina);
            if (abs($size['width']-210)>1 || abs($size['height']-297)>1) {
                throw new RuntimeException('La plantilla del complemento debe tener tamaño A4 vertical.');
            }
            $this->pdf->SetPageTemplate($pagina);
            $this->pagina();
            if (($datos['estado'] ?? '') === 'pendiente') {
                $this->textoFluido('COMPROBANTE PENDIENTE DE TIMBRAR', 8, true);
                $this->y += 2;
            }
            $folio = $this->texto($datos['folio'] ?? '');
            $this->fuente($folio, 7.5);
            if ($this->pdf->GetStringWidth($folio)>96) {
                $this->textoFluido('Folio: '.$folio, 7.5, true);
            }
            $this->partes();
            foreach (($datos['pagos'] ?? []) as $indice=>$pago) {
                $this->pago($pago, $indice+1, count($datos['pagos']));
            }
            $this->relaciones();
            $this->fiscales();
            return $this->pdf->Output('', Destination::STRING_RETURN);
        } finally {
            $this->limpiar($cache);
        }
    }

    private function pagina(): void
    {
        $this->pdf->AddPage();
        // El PDF aportado conserva su marca, cabecera y pie. El cuerpo se vuelve
        // a componer para que más pagos/documentos nunca invadan bloques fijos.
        $this->pdf->SetFillColor(255,255,255);
        $this->pdf->Rect(14.3,32.3,181.4,255.1,'F');
        $folio = $this->texto($this->datos['folio'] ?? '');
        $this->fuente($folio,7.5,true);
        $display = $this->pdf->GetStringWidth($folio)>96 ? 'Folio en el detalle' : $folio;
        $this->celda(98,19,97,5,$display,7.5,true,'R');
        $this->celda(131,29,64,3,$this->texto($this->datos['fecha_emision'] ?? ''),6.7,false,'R');
        $this->celda(171,290.7,24,3,'Página '.$this->pdf->PageNo(),6,false,'R');
        $this->y = 34;
        if ($this->pdf->PageNo()>1) {
            $this->textoFluido('COMPLEMENTO DE PAGO - CONTINUACIÓN',7,true);
            $this->y += 2;
        }
    }

    private function asegurar(float $alto): bool
    {
        if ($this->y+$alto<=self::LIMITE) { return false; }
        $this->pagina();
        return true;
    }

    private function titulo(string $texto,float $ancho=180): void
    {
        $this->asegurar(10);
        $this->pdf->SetDrawColor(180,204,229);
        $this->pdf->SetLineWidth(0.15);
        $this->pdf->Line(15,$this->y-2,15+$ancho,$this->y-2);
        $this->celda(15,$this->y,$ancho,4,$texto,8.3,true,'L',[0,121,169]);
        $this->y += 5;
    }

    private function partes(): void
    {
        $emisor = $this->datos['emisor'] ?? [];
        $receptor = $this->datos['receptor'] ?? [];
        $inicio = $this->y;
        $fin = $inicio;
        foreach ([[15,105,'EMISOR',$emisor],[128,67,'RECEPTOR',$receptor]] as [$x,$w,$titulo,$parte]) {
            $this->celda($x,$inicio,$w,4,$titulo,8.3,true,'L',[0,121,169]);
            $y = $inicio+6;
            $campos = [[$this->texto($parte['nombre'] ?? ''),true],
                [$this->etiqueta('RFC',$parte['rfc'] ?? ''),false],
                [$this->etiqueta('Régimen fiscal',$parte['regimen'] ?? ''),false],
                [$this->etiqueta($titulo==='EMISOR'?'Lugar de expedición: CP':'Domicilio fiscal: CP',$parte['cp'] ?? ''),false],
                [$this->etiqueta('Uso CFDI',$parte['uso_cfdi'] ?? ''),false],
                [$this->texto($parte['direccion'] ?? ''),false],
                [$this->etiqueta('Teléfono',$parte['telefono'] ?? ''),false]];
            foreach ($campos as [$texto,$bold]) {
                if ($texto==='') { continue; }
                foreach ($this->lineas($texto,$w,7,$bold) as $linea) {
                    if ($y+3.5>self::LIMITE) { throw new RuntimeException('Los datos del emisor o receptor exceden el área disponible.'); }
                    $this->celda($x,$y,$w,3.5,$linea,7,$bold);
                    $y += 3.5;
                }
            }
            $fin = max($fin,$y);
        }
        $this->y = max(66,$fin+6);
    }

    private function pago(array $pago, int $numero, int $total): void
    {
        $this->asegurar(47);
        $this->titulo('DETALLE DEL PAGO'.($total>1?' '.$numero.' DE '.$total:''));
        $inicio = $this->y;
        $this->pdf->SetFillColor(238,246,251);
        $this->pdf->RoundedRect(140,$inicio-6,55,22,2,'F');
        $this->celda(143,$inicio-4,49,4,'MONTO DEL PAGO',7.5,true,'R',[0,121,169]);
        $monto = $this->numero($pago['monto'] ?? '').' '.$this->texto($pago['moneda'] ?? '');
        $this->bloque(trim($monto),143,$inicio+2,49,9,true,'R');
        $fin = $inicio;
        foreach ([[15,68,'Fecha de pago',$pago['fecha'] ?? ''],[86,49,'Moneda',$pago['moneda'] ?? ''],
            [15,68,'Forma de pago',$pago['forma_pago'] ?? ''],[86,49,'Tipo de cambio',$pago['tipo_cambio'] ?? '']] as $i=>[$x,$w,$label,$value]) {
            $y = $inicio+($i>=2?11:0);
            $this->celda($x,$y,$w,3,$label,6.5,false,'L',[86,117,154]);
            $fin = max($fin,$this->bloque($this->texto($value),$x,$y+3.4,$w,7.5));
        }
        $this->y = max($inicio+30,$fin+5);
        $doctos = $pago['doctos'] ?? [];
        if ($doctos!==[]) {
            $this->titulo(count($doctos)===1?'DOCUMENTO PAGADO':'DOCUMENTOS PAGADOS');
            foreach ($doctos as $docto) {
                $this->asegurar(26);
                $this->textoFluido('UUID del documento: '.$this->texto($docto['uuid'] ?? ''),6.5);
                $this->y += 1;
                $serie = trim($this->texto($docto['serie'] ?? ''));
                $folio = trim($this->texto($docto['folio'] ?? ''));
                $this->tabla(['Serie / Folio','Moneda','Equivalencia','Objeto de impuesto','Parcialidad','Saldo anterior','Importe pagado','Saldo insoluto'],
                    [21,16,25,23,25,23,23,24],[[trim($serie.($serie!==''&&$folio!==''?' / ':'').$folio),
                    $docto['moneda'] ?? '',$docto['equivalencia'] ?? '',$docto['objeto_impuesto'] ?? '',$docto['parcialidad'] ?? '',
                    $this->numero($docto['saldo_anterior'] ?? ''),$this->numero($docto['importe_pagado'] ?? ''),$this->numero($docto['saldo_insoluto'] ?? '')]]);
                $this->y += 3;
            }
        }
        $this->asegurar(29);
        $this->titulo('INFORMACIÓN DE IMPUESTOS');
        $filas=[];
        foreach ($doctos as $i=>$docto) {
            foreach (($docto['impuestos'] ?? []) as $impuesto) {
                $filas[]=$this->filaImpuesto($impuesto,'Documento '.($i+1));
            }
        }
        foreach (($pago['impuestos'] ?? []) as $impuesto) {$filas[]=$this->filaImpuesto($impuesto,'Pago '.$numero);}
        $this->tabla(['Tipo de impuesto','Base','Impuesto','Tipo de factor','Tasa o cuota','Importe'],[35,25,30,30,30,30],$filas);
        $this->y += 5;
    }

    private function filaImpuesto(array $i,string $ambito): array
    {
        return [$this->texto($i['tipo'] ?? '').' - '.$ambito,$this->numero($i['base'] ?? ''),$i['impuesto'] ?? '',
            $i['factor'] ?? '',$i['tasa'] ?? '',$this->numero($i['importe'] ?? '')];
    }

    /** Las filas que exceden una página se continúan con cabecera repetida; no se recortan. */
    private function tabla(array $cabeceras,array $anchos,array $filas): void
    {
        $header=function()use($cabeceras,$anchos,$filas):void {
            $this->asegurar($filas===[]?12:18);$x=15;
            $this->pdf->SetFillColor(238,246,251);$this->pdf->RoundedRect(15,$this->y,180,9,2,'F');
            foreach ($cabeceras as $i=>$texto) {
                $this->bloque($texto,$x+1,$this->y+1,$anchos[$i]-2,6.2,true,'C');$x+=$anchos[$i];
            }
            $this->y+=10;
        };
        $header();
        foreach ($filas as $fila) {
            $lines=[];$fonts=[];
            foreach ($fila as $i=>$texto) {$texto=$this->texto($texto);$lines[$i]=$this->lineas($texto,$anchos[$i]-2,6.5);$fonts[$i]=$this->familia($texto);}
            $max=max(array_map('count',$lines));$offset=0;
            while ($offset<$max) {
                $available=self::LIMITE-$this->y<7?0:(int)floor((self::LIMITE-$this->y-2)/3.2);
                if ($available<1) {$this->pagina();$header();continue;}
                $take=min($max-$offset,$available);$x=15;
                foreach ($fila as $i=>$_) {
                    foreach (array_slice($lines[$i],$offset,$take) as $j=>$linea) {
                        $this->celda($x+1,$this->y+1+$j*3.2,$anchos[$i]-2,3.2,$linea,6.5,false,'C',null,$fonts[$i]);
                    }
                    $x+=$anchos[$i];
                }
                $this->y+=max(7,$take*3.2+2);$offset+=$take;
                if ($offset<$max) {$this->pagina();$header();}
            }
            $this->pdf->SetDrawColor(210,225,239);$this->pdf->Line(15,$this->y,195,$this->y);
        }
    }

    private function relaciones(): void
    {
        $relaciones=$this->datos['relaciones'] ?? [];
        $this->asegurar(10);$this->pdf->SetFillColor(238,246,251);$this->pdf->RoundedRect(15,$this->y,180,9,2,'F');
        $this->celda(17,$this->y+2,75,5,'TIPO DE RELACIÓN',8,true,'L',[0,121,169]);
        $this->celda(112,$this->y+2,80,5,'CFDI relacionados',7,false,'R',[86,117,154]);$this->y+=11;
        foreach ($relaciones as $relacion) {$this->textoFluido($this->texto($relacion['tipo'] ?? '').' - '.$this->texto($relacion['uuid'] ?? ''),7);}
        $totales=$this->datos['totales'] ?? [];
        if ($totales!==[]) {
            $this->y+=3;
            $this->titulo('TOTALES DEL COMPLEMENTO');
            $labels=['MontoTotalPagos'=>'Monto total de pagos','TotalRetencionesIVA'=>'Retención IVA',
                'TotalRetencionesISR'=>'Retención ISR','TotalRetencionesIEPS'=>'Retención IEPS',
                'TotalTrasladosBaseIVA16'=>'Base IVA 16%','TotalTrasladosImpuestoIVA16'=>'IVA 16%',
                'TotalTrasladosBaseIVA8'=>'Base IVA 8%','TotalTrasladosImpuestoIVA8'=>'IVA 8%',
                'TotalTrasladosBaseIVA0'=>'Base IVA 0%','TotalTrasladosImpuestoIVA0'=>'IVA 0%',
                'TotalTrasladosBaseIVAExento'=>'Base exenta de IVA'];
            foreach (array_chunk($totales,3,true) as $grupo) {
                $fin=$this->y;$i=0;$this->asegurar(12);
                foreach($grupo as $campo=>$valor){$fin=max($fin,$this->bloque(($labels[$campo] ?? $campo).': '.$this->numero($valor),15+$i*60,$this->y,58,6.5));$i++;}
                $this->y=$fin+1;
            }
        }
        $this->y+=3;
    }

    private function fiscales(): void
    {
        $this->titulo('DATOS FISCALES Y CERTIFICACIÓN SAT');
        $f=$this->datos['fiscales'] ?? [];
        foreach (['uuid'=>'UUID / Folio fiscal','fecha_certificacion'=>'Fecha y hora de certificación','certificado_emisor'=>'No. certificado emisor',
            'certificado_sat'=>'No. certificado SAT','rfc_proveedor'=>'RFC del proveedor de certificación'] as $key=>$label) {
            $this->textoFluido($label.': '.$this->texto($f[$key] ?? ''),6.6);
        }
        $this->y+=3;
        $sellos=$this->datos['sellos'] ?? [];
        $this->asegurar(46);
        $this->titulo('SELLO DIGITAL DEL CFDI');
        $inicio=$this->y;
        $paginaQr=$this->pdf->PageNo();
        $qr=$this->qr();
        if($qr!=='') {
            $this->celda(157,$inicio-6,38,4,'QR FISCAL SAT',7,true,'C',[0,121,169]);
            $this->pdf->Image($qr,158,$inicio,35,35);
        }
        $this->textoFluido($this->texto($sellos['cfdi'] ?? ''),6.1,false,138,'SELLO DIGITAL DEL CFDI');
        $this->y+=2;
        $this->titulo('SELLO DIGITAL DEL SAT',138);
        $this->textoFluido($this->texto($sellos['sat'] ?? ''),6.1,false,138,'SELLO DIGITAL DEL SAT');
        if($this->pdf->PageNo()===$paginaQr && $qr!=='') {$this->y=max($this->y,$inicio+37);}
        $this->y+=3;
        $this->titulo('CADENA ORIGINAL DEL COMPLEMENTO DE CERTIFICACIÓN DIGITAL DEL SAT');
        $this->textoFluido($this->texto($sellos['cadena_original'] ?? ''),6.1,false,180,'CADENA ORIGINAL DEL COMPLEMENTO DE CERTIFICACIÓN DIGITAL DEL SAT');
    }

    private function qr(): string
    {
        if (($this->datos['estado'] ?? '')!=='timbrado') {return '';}
        $url=$this->texto($this->datos['qr_url'] ?? '');
        if($url!=='') {
            $parts=parse_url($url);
            if(($parts['scheme'] ?? '')!=='https' || ($parts['host'] ?? '')!=='verificacfdi.facturaelectronica.sat.gob.mx'
                || ($parts['path'] ?? '')!=='/default.aspx' || isset($parts['user']) || isset($parts['pass']) || isset($parts['port']) || isset($parts['fragment'])) {
                throw new RuntimeException('La dirección del QR fiscal no corresponde al verificador del SAT.');
            }
            return (new QRCode(new QROptions(['outputType'=>QRCode::OUTPUT_IMAGE_PNG,'imageBase64'=>true,'scale'=>4])))->render($url);
        }
        $path=$this->texto($this->datos['qr'] ?? '');$real=$path!==''?realpath($path):false;
        if($real===false || !is_file($real) || !is_readable($real)) {return '';}
        $size=@getimagesize($real);
        return $size!==false&&in_array($size[2],[IMAGETYPE_PNG,IMAGETYPE_JPEG],true)?$real:'';
    }

    private function bloque(string $texto,float $x,float $y,float $ancho,float $size,bool $bold=false,string $align='L'): float
    {
        $family=$this->familia($texto);
        foreach ($this->lineas($texto,$ancho,$size,$bold) as $linea) {$this->celda($x,$y,$ancho,3.5,$linea,$size,$bold,$align,null,$family);$y+=3.5;}
        return $y;
    }

    private function textoFluido(string $texto,float $size=7,bool $bold=false,float $ancho=180,string $continuacion=''): void
    {
        $family=$this->familia($texto);$alto=$size<6.3?2.8:3.5;
        foreach ($this->lineas($texto,$ancho,$size,$bold) as $linea) {
            if ($this->asegurar($alto) && $continuacion!=='') {$this->titulo($continuacion.' (CONTINUACIÓN)');}
            $this->celda(15,$this->y,$ancho,$alto,$linea,$size,$bold,'L',null,$family);$this->y+=$alto;
        }
    }

    private function lineas(string $texto,float $ancho,float $size,bool $bold=false): array
    {
        $this->fuente($texto,$size,$bold);$lineas=[];
        foreach (explode("\n",str_replace(["\r\n","\r"],"\n",$texto)) as $parrafo) {
            $linea='';
            foreach (preg_split('/(\s+)/u',$parrafo,-1,PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY) ?: [] as $palabra) {
                if ($linea!=='' && $this->pdf->GetStringWidth($linea.$palabra)>$ancho-0.5) {$lineas[]=rtrim($linea);$linea='';}
                if ($this->pdf->GetStringWidth($palabra)>$ancho-0.5) {
                    foreach (preg_split('//u',$palabra,-1,PREG_SPLIT_NO_EMPTY) ?: [] as $char) {
                        if ($linea!==''&&$this->pdf->GetStringWidth($linea.$char)>$ancho-0.5) {$lineas[]=rtrim($linea);$linea='';}
                        $linea.=$char;
                    }
                } else {$linea.=$linea===''?ltrim($palabra):$palabra;}
            }
            $lineas[]=rtrim($linea);
        }
        return $lineas ?: [''];
    }

    private function celda(float $x,float $y,float $w,float $h,string $texto,float $size,bool $bold=false,string $align='L',?array $color=null,?string $family=null): void
    {
        $this->pdf->SetFont($family ?? $this->familia($texto),$bold&&($family ?? $this->familia($texto))!=='sun-exta'?'B':'',$size);
        $this->pdf->SetTextColor(...($color ?? [18,61,112]));$this->pdf->SetXY($x,$y);$this->pdf->Cell($w,$h,$texto,0,0,$align);
    }

    private function fuente(string $texto,float $size,bool $bold=false): void
    {
        $family=$this->familia($texto);$this->pdf->SetFont($family,$bold&&$family!=='sun-exta'?'B':'',$size);
    }

    private function familia(string $texto): string {return preg_match('/[\x{2e80}-\x{9fff}]/u',$texto)?'sun-exta':'dejavusans';}
    private function texto(mixed $valor): string {return is_scalar($valor)?(string)$valor:'';}
    private function etiqueta(string $nombre,mixed $valor): string {$v=$this->texto($valor);return $v===''?'':$nombre.': '.$v;}
    private function numero(mixed $valor): string
    {
        $v=$this->texto($valor);
        if (!preg_match('/^(-?)(\d+)(\.\d+)?$/D',$v,$m)) {return $v;}
        return $m[1].preg_replace('/\B(?=(\d{3})+(?!\d))/D',',',$m[2]).($m[3] ?? '');
    }

    private function limpiar(string $directorio): void
    {
        if (!is_dir($directorio)) {return;}
        foreach (new \FilesystemIterator($directorio,\FilesystemIterator::SKIP_DOTS) as $entry) {
            if ($entry->isDir()&&!$entry->isLink()) {$this->limpiar($entry->getPathname());} else {@unlink($entry->getPathname());}
        }
        @rmdir($directorio);
    }
}
