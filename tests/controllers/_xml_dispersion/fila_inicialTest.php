<?php
namespace gamboamartin\facturacion\tests\controllers\_xml_dispersion;
use PHPUnit\Framework\TestCase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use gamboamartin\facturacion\controllers\_xls_dispersion;
use gamboamartin\errores\errores;
use ReflectionClass;

final class fila_inicialTest extends TestCase
{
    private _xls_dispersion $xls;

    protected function setUp(): void
    {
        errores::$error = false;
        $this->xls = new _xls_dispersion();
    }

    public function testFilaInicialConEncabezadoValido(): void
    {
        $spreadsheet = new Spreadsheet();
        $hoja = $spreadsheet->getActiveSheet();
        $hoja->setCellValue('A5', 'CLAVE EMPLEADO');

        $resultado = $this->getPrivateFilaInicial($hoja);

        $this->assertIsInt($resultado);
        $this->assertEquals(6, $resultado);
    }

    public function testFilaInicialSinEncabezado(): void
    {
        $spreadsheet = new Spreadsheet();
        $hoja = $spreadsheet->getActiveSheet();
        $hoja->setCellValue('A1', 'NO VALIDO');

        $resultado = $this->getPrivateFilaInicial($hoja);

        $this->assertIsArray($resultado);
        $this->assertTrue(isset($resultado['mensaje']));
        $this->assertStringContainsString('encabezado', $resultado['mensaje']);
    }

    public function testFilaInicialEncabezadoInvalido(): void
    {
        // Sobrescribir file_encabezado para devolver algo inválido
        $mock = $this->getMockBuilder(_xls_dispersion::class)
            ->onlyMethods(['file_encabezado'])
            ->getMock();


        $spreadsheet = new Spreadsheet();
        $hoja = $spreadsheet->getActiveSheet();

        $resultado = $this->callPrivateMethod($mock, 'fila_inicial', [$hoja]);

        $this->assertIsArray($resultado);
        $this->assertStringContainsString('Error al obtener fila de encabezado', $resultado['mensaje']);
    }

    /**
     * Helper para acceder al método privado fila_inicial
     */
    private function getPrivateFilaInicial(Worksheet $hoja): mixed
    {
        return $this->callPrivateMethod($this->xls, 'fila_inicial', [$hoja]);
    }

    private function callPrivateMethod($object, string $method, array $args): mixed
    {
        $reflection = new ReflectionClass($object);
        $refMethod = $reflection->getMethod($method);


        return $refMethod->invokeArgs($object, $args);
    }
}