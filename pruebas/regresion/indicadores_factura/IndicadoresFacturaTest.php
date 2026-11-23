<?php
declare(strict_types=1);

use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\fc_cfdi_sellado;
use gamboamartin\facturacion\models\fc_factura;
use PHPUnit\Framework\TestCase;

/** Facturas y sellos ficticios: verifica selección temporal e identidad, sin DB ni PAC. */
final class IndicadoresFacturaTest extends TestCase
{
    private fc_factura $modelo;
    private bool $errorEsperado = false;

    protected function setUp(): void
    {
        require_once __DIR__.'/../fc_factura/dobles_factura.php';
        require_once __DIR__.'/dobles_indicadores.php';
        require_once RAIZ_REPOSITORIO_PRUEBAS.'/orm/fc_factura.php';
        errores::$error = false;
        fc_cfdi_sellado::$registros = [];
        fc_cfdi_sellado::$llamadas = [];
        fc_cfdi_sellado::$fallar = false;
        $pdo = new class extends PDO {
            public function __construct() {}
            public function prepare(string $query, array $options = []): PDOStatement|false
            {
                throw new RuntimeException('SQL real prohibido en pruebas unitarias.');
            }
            public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): PDOStatement|false
            {
                throw new RuntimeException('SQL real prohibido en pruebas unitarias.');
            }
            public function exec(string $statement): int|false
            {
                throw new RuntimeException('Escrituras prohibidas en pruebas unitarias.');
            }
        };
        $this->modelo = new fc_factura($pdo);
    }

    protected function tearDown(): void
    {
        self::assertSame($this->errorEsperado, errores::$error);
    }

    private function factura(int $id, string $folio, string $serie = 'A', float $total = 116.0, int $cliente = 1): array
    {
        return [
            'fc_factura_id' => $id,
            'fc_factura_serie' => $serie,
            'fc_factura_folio' => $folio,
            'fc_factura_folio_fiscal' => '00000000-0000-0000-0000-000000000001',
            'fc_factura_total' => $total,
            'com_cliente_id' => $cliente,
        ];
    }

    private function sello(int $factura, string $fecha): array
    {
        return ['fc_factura_id' => $factura, 'fc_cfdi_sellado_complemento_tfd_fecha_timbrado' => $fecha];
    }

    public function testSinFacturasNoConsultaSellos(): void
    {
        self::assertSame(['facturas' => 0, 'importe' => 0.0, 'clientes' => 0, 'ultima' => ''], $this->modelo->obtener_indicadores_listado());
        self::assertSame([], fc_cfdi_sellado::$llamadas);
    }

    public function testTimbradoPosteriorConIdMenorEsLaUltima(): void
    {
        $this->modelo->registros = [
            $this->factura(30, 'A-000030', total: 100.25),
            $this->factura(10, 'A-000010', total: 200.50, cliente: 2),
            $this->factura(20, 'A-000020', total: 50.10),
        ];
        fc_cfdi_sellado::$registros = [
            $this->sello(20, '2026-11-23T10:00:00'),
            $this->sello(10, '2026-11-23T12:00:00'),
            $this->sello(30, '2026-11-23T11:00:00'),
        ];
        self::assertSame(['facturas' => 3, 'importe' => 350.85, 'clientes' => 2, 'ultima' => 'A-000010'], $this->modelo->obtener_indicadores_listado());
        self::assertSame(['fc_factura.etapa' => 'TIMBRADO'], $this->modelo->llamadas[0]['filtro']);
        self::assertSame([[
            'columnas' => ['fc_factura_id', 'fc_cfdi_sellado_complemento_tfd_fecha_timbrado'],
            'filtro' => ['fc_factura.etapa' => 'TIMBRADO'],
        ]], fc_cfdi_sellado::$llamadas);
    }

    public function testMismaFechaDesempataPorIdNormalizandoT(): void
    {
        $this->modelo->registros = [$this->factura(3, 'A-000003'), $this->factura(1, 'A-000001')];
        fc_cfdi_sellado::$registros = [$this->sello(3, '2026-11-23 12:00:00'), $this->sello(1, '2026-11-23T12:00:00')];
        self::assertSame('A-000003', $this->modelo->obtener_indicadores_listado()['ultima']);
    }

    public function testFolioLargoCompuestoNoDuplicaSerieNiUsaUuid(): void
    {
        $serie = '000000000000000001';
        $folio = $serie.'-000002';
        $this->modelo->registros = [$this->factura(2, $folio, $serie)];
        fc_cfdi_sellado::$registros = [$this->sello(2, '2026-11-23T12:00:00')];
        self::assertSame($folio, $this->modelo->obtener_indicadores_listado()['ultima']);
    }

    public function testSerieSeparadaConservaCerosDelFolio(): void
    {
        $this->modelo->registros = [$this->factura(2, '000002', 'FAC')];
        fc_cfdi_sellado::$registros = [$this->sello(2, '2026-11-23T12:00:00')];
        self::assertSame('FAC-000002', $this->modelo->obtener_indicadores_listado()['ultima']);
    }

    public function testSinSerieConservaFolioCompleto(): void
    {
        $this->modelo->registros = [$this->factura(2, 'MANUAL-000002', '')];
        fc_cfdi_sellado::$registros = [$this->sello(2, '2026-11-23T12:00:00')];
        self::assertSame('MANUAL-000002', $this->modelo->obtener_indicadores_listado()['ultima']);
    }

    public function testSellosDuplicadosNoDuplicanConteosNiImportes(): void
    {
        $this->modelo->registros = [$this->factura(3, 'A-000003'), $this->factura(2, 'A-000002')];
        fc_cfdi_sellado::$registros = [
            $this->sello(2, '2026-11-23T09:00:00'),
            $this->sello(3, '2026-11-23T10:00:00'),
            $this->sello(2, '2026-11-23T11:00:00'),
        ];
        self::assertSame(['facturas' => 2, 'importe' => 232.0, 'clientes' => 1, 'ultima' => 'A-000002'], $this->modelo->obtener_indicadores_listado());
    }

    public function testIgnoraSellosFueraDeLasFacturasDelResumen(): void
    {
        $this->modelo->registros = [$this->factura(2, 'A-000002')];
        fc_cfdi_sellado::$registros = [$this->sello(2, '2026-11-23T10:00:00'), $this->sello(99, '2026-11-23T12:00:00')];
        self::assertSame('A-000002', $this->modelo->obtener_indicadores_listado()['ultima']);
    }

    public function testSinFechaValidaNoInventaUltimaPorIdNiUuid(): void
    {
        $this->modelo->registros = [$this->factura(2, 'A-000002')];
        fc_cfdi_sellado::$registros = [
            $this->sello(2, ''),
            $this->sello(2, '0000-00-00 00:00:00'),
            $this->sello(2, '2026-02-30T12:00:00'),
            $this->sello(2, '2026-11-23T25:00:00'),
            $this->sello(2, "2026-11-23T12:00:00\0"),
        ];
        self::assertSame(['facturas' => 1, 'importe' => 116.0, 'clientes' => 1, 'ultima' => ''], $this->modelo->obtener_indicadores_listado());
    }

    public function testFolioVacioNoSeSustituyePorSerieNiUuid(): void
    {
        $this->modelo->registros = [$this->factura(2, '', 'FAC')];
        fc_cfdi_sellado::$registros = [$this->sello(2, '2026-11-23T12:00:00')];
        self::assertSame('', $this->modelo->obtener_indicadores_listado()['ultima']);
    }

    public function testErrorDeFacturasNoConsultaSellos(): void
    {
        $this->modelo->fallarConsulta = true;
        $this->errorEsperado = true;
        self::assertTrue($this->modelo->obtener_indicadores_listado()['error']);
        self::assertSame([], fc_cfdi_sellado::$llamadas);
    }

    public function testPropagaErrorDeConsultaDeSellos(): void
    {
        $this->modelo->registros = [$this->factura(2, 'A-000002')];
        fc_cfdi_sellado::$fallar = true;
        $this->errorEsperado = true;
        self::assertTrue($this->modelo->obtener_indicadores_listado()['error']);
        self::assertCount(1, fc_cfdi_sellado::$llamadas);
    }
}
