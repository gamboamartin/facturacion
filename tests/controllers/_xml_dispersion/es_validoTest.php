<?php

namespace gamboamartin\facturacion\tests\controllers\_xml_dispersion;

use gamboamartin\errores\errores;
use gamboamartin\facturacion\controllers\_xls_dispersion;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class es_validoTest extends TestCase
{
    private _xls_dispersion $xlsDispersion;

    protected function setUp(): void
    {
        errores::$error = false;
        $this->xlsDispersion = new _xls_dispersion();
        errores::$error = false;
    }

    public function testEsValidoConEncabezado(): void
    {
        // Arrange: hoja con "CLAVE EMPLEADO" en la fila 1, col A
        $spreadsheet = new Spreadsheet();
        $hoja = $spreadsheet->getActiveSheet();
        $hoja->setCellValue('A1', 'CLAVE EMPLEADO');

        // Act
        $resultado = $this->invokeEsValido($hoja);

        // Assert
        $this->assertTrue($resultado, 'Debe devolver true cuando existe el encabezado');
    }

    public function testEsValidoSinEncabezado(): void
    {
        errores::$error = false;
        // Arrange: hoja sin "CLAVE EMPLEADO"
        $spreadsheet = new Spreadsheet();
        $hoja = $spreadsheet->getActiveSheet();
        $hoja->setCellValue('A1', 'OTRO TEXTO');
        $hoja->setCellValue('A2', 'DATO CUALQUIERA');

        // Act
        $resultado = $this->invokeEsValido($hoja);

        // Assert: debe regresar arreglo de error de la clase errores
        $this->assertIsArray($resultado, 'Debe devolver un arreglo de error cuando no encuentra encabezado');
        $this->assertArrayHasKey('mensaje', $resultado);
        $this->assertStringContainsString('Encabezado no encontrado en la hoja', $resultado['mensaje']);

    }

    /**
     * Helper para acceder al método privado es_valido usando Reflection
     */
    private function invokeEsValido($hoja)
    {
        $reflection = new ReflectionClass(_xls_dispersion::class);
        $method = $reflection->getMethod('es_valido');

        return $method->invokeArgs($this->xlsDispersion, [$hoja]);
    }
}
