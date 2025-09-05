<?php
namespace gamboamartin\facturacion\tests\controllers\_xml_dispersion;
use gamboamartin\errores\errores;
use PHPUnit\Framework\TestCase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use gamboamartin\facturacion\controllers\_xls_dispersion;
use ReflectionClass;

final class file_encabezadoTest extends TestCase
{
    private _xls_dispersion $xlsDispersion;

    protected function setUp(): void
    {
        errores::$error = false;
        $this->xlsDispersion = new _xls_dispersion();
        errores::$error = false;
    }

    public function testFileEncabezadoEncabezadoEnPrimeraFila(): void
    {
        errores::$error = false;
        // Arrange
        $spreadsheet = new Spreadsheet();
        $hoja = $spreadsheet->getActiveSheet();
        $hoja->setCellValue('A1', 'CLAVE EMPLEADO');

        // Act
        $resultado = $this->invokeFileEncabezado($hoja);

        // Assert
        $this->assertIsInt($resultado);
        $this->assertEquals(1, $resultado);
    }

    public function testFileEncabezadoEncabezadoEnFilaIntermedia(): void
    {
        errores::$error = false;
        // Arrange
        $spreadsheet = new Spreadsheet();
        $hoja = $spreadsheet->getActiveSheet();
        $hoja->setCellValue('A10', 'CLAVE EMPLEADO');

        // Act
        $resultado = $this->invokeFileEncabezado($hoja);

        // Assert
        $this->assertIsInt($resultado);
        $this->assertEquals(10, $resultado);
    }

    public function testFileEncabezadoSinEncabezado(): void
    {
        errores::$error = false;
        // Arrange
        $spreadsheet = new Spreadsheet();
        $hoja = $spreadsheet->getActiveSheet();
        $hoja->setCellValue('A1', 'DATO CUALQUIERA');
        $hoja->setCellValue('A2', 'OTRO VALOR');

        // Act
        $resultado = $this->invokeFileEncabezado($hoja);

        // Assert
        $this->assertIsArray($resultado, 'Debe devolver un arreglo de error si no encuentra encabezado');
        $this->assertArrayHasKey('mensaje', $resultado);
        $this->assertStringContainsString('Encabezado no encontrado en la columna indicada', $resultado['mensaje']);
    }

    /**
     * Helper para invocar método privado con Reflection
     */
    private function invokeFileEncabezado($hoja)
    {
        $reflection = new ReflectionClass(_xls_dispersion::class);
        $method = $reflection->getMethod('file_encabezado');


        return $method->invokeArgs($this->xlsDispersion, [$hoja]);
    }
}