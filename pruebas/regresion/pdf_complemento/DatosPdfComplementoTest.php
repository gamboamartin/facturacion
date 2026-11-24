<?php
declare(strict_types=1);

use gamboamartin\facturacion\models\_datos_pdf_complemento;
use PHPUnit\Framework\TestCase;

final class DatosPdfComplementoTest extends TestCase
{
    protected function setUp(): void
    {
        require_once RAIZ_REPOSITORIO_PRUEBAS.'/orm/_datos_pdf_complemento.php';
        require_once __DIR__.'/fixture.php';
    }

    public function testConservaTodosLosPagosYDocumentosDelXml(): void
    {
        $d = _datos_pdf_complemento::desde_xml(xmlComplementoPrueba());
        self::assertCount(2, $d['pagos']);
        self::assertCount(2, $d['pagos'][0]['doctos']);
        self::assertCount(1, $d['pagos'][1]['doctos']);
        self::assertSame('A', $d['pagos'][0]['doctos'][0]['serie']);
        self::assertSame('100', $d['pagos'][0]['doctos'][0]['folio']);
        self::assertSame('2', $d['pagos'][0]['doctos'][0]['parcialidad']);
        self::assertNotSame($d['fiscales']['uuid'], $d['pagos'][0]['doctos'][0]['uuid']);
        self::assertSame('USD', $d['pagos'][1]['moneda']);
        self::assertSame('17.000000', $d['pagos'][1]['tipo_cambio']);
    }

    public function testFechasEmisionCobroYCertificacionSonDistintas(): void
    {
        $d = _datos_pdf_complemento::desde_xml(xmlComplementoPrueba());
        self::assertSame('2026-11-24T12:00:01', $d['fecha_emision']);
        self::assertSame('2026-11-24T13:05:09', $d['fiscales']['fecha_certificacion']);
        self::assertSame('2026-11-20T08:30:00', $d['pagos'][0]['fecha']);
    }

    public function testNoInventarImpuestosExentosNiRetenciones(): void
    {
        $d = _datos_pdf_complemento::desde_xml(xmlComplementoPrueba());
        self::assertSame('Retención', $d['pagos'][0]['doctos'][0]['impuestos'][1]['tipo']);
        self::assertSame('001', $d['pagos'][0]['impuestos'][1]['impuesto']);
        self::assertSame('', $d['pagos'][0]['impuestos'][1]['base']);
        $exento = $d['pagos'][1]['doctos'][0]['impuestos'][0];
        self::assertSame('Exento', $exento['factor']);
        self::assertSame('', $exento['tasa']);
        self::assertSame('', $exento['importe']);
        self::assertSame('2430.00', $d['totales']['MontoTotalPagos']);
    }

    public function testIdentidadesUnicodeYRelacionSonDelXml(): void
    {
        $d = _datos_pdf_complemento::desde_xml(xmlComplementoPrueba());
        self::assertSame('EMISOR FICTICIO ÁRBOL', $d['emisor']['nombre']);
        self::assertSame('RECEPTOR FICTICIO MUÑOZ', $d['receptor']['nombre']);
        self::assertSame('64000', $d['receptor']['cp']);
        self::assertSame('601', $d['receptor']['regimen']);
        self::assertSame('CP01', $d['receptor']['uso_cfdi']);
        self::assertSame('04', $d['relaciones'][0]['tipo']);
        self::assertSame('CP-000042', $d['folio']);
    }

    public function testPendienteNoAdoptaCertificacionNiQrRecibido(): void
    {
        $d = _datos_pdf_complemento::desde_xml(xmlComplementoPrueba(false), 'CADENA ANTIGUA', '/archivo/qr-anterior.png');
        self::assertSame('pendiente', $d['estado']);
        self::assertSame('', $d['fiscales']['uuid']);
        self::assertSame('', $d['sellos']['cadena_original']);
        self::assertSame('', $d['qr']);
        self::assertSame('', $d['qr_url']);
    }

    public function testCadenaTimbreCanonicaYLeyendaOpcional(): void
    {
        $xml = str_replace('Version="1.1" UUID=', 'Version="1.1" Leyenda="  SOLO    PRUEBA  " UUID=', xmlComplementoPrueba());
        $d = _datos_pdf_complemento::desde_xml($xml);
        self::assertSame('||1.1|11111111-1111-4111-8111-111111111111|2026-11-24T13:05:09|AAA010101AAA|SOLO PRUEBA|SELLOFICTICIO+abc/=|00001000000500000000||', $d['sellos']['cadena_original']);
        self::assertSame('CADENA PAC', _datos_pdf_complemento::desde_xml($xml, 'CADENA PAC')['sellos']['cadena_original']);
    }

    public function testQrUsaTotalDelCfdiYNoMontoDePago(): void
    {
        $d = _datos_pdf_complemento::desde_xml(xmlComplementoPrueba());
        parse_str((string)parse_url($d['qr_url'], PHP_URL_QUERY), $q);
        self::assertSame('0.000000', $q['tt']);
        self::assertSame('AAA010101AAA', $q['re']);
        self::assertSame('BBB010101BBB', $q['rr']);
        self::assertSame(substr('SELLOFICTICIO+abc/=', -8), $q['fe']);
        self::assertSame('verificacfdi.facturaelectronica.sat.gob.mx', parse_url($d['qr_url'], PHP_URL_HOST));
    }

    public function testRechazaXmlRotoEntidadesYComprobanteDistinto(): void
    {
        $base = xmlComplementoPrueba();
        foreach (['<no-cierra>', '<!DOCTYPE c:Comprobante [<!ENTITY x SYSTEM "file:///etc/passwd">]>'.$base,
            str_replace('TipoDeComprobante="P"', 'TipoDeComprobante="I"', $base),
            str_replace('http://www.sat.gob.mx/Pagos20', 'https://ejemplo.invalid/Pagos20', $base),
            str_replace('<c:Emisor ', '<c:EmisorX ', $base),
            str_replace(' SelloSAT="SELLOSATFICTICIO"', '', $base)] as $xml) {
            $error = null;
            try { _datos_pdf_complemento::desde_xml($xml); } catch (InvalidArgumentException $e) { $error = $e; }
            self::assertInstanceOf(InvalidArgumentException::class, $error);
        }
    }
}
