<?php
namespace gamboamartin\facturacion\tests\controllers\_xml_dispersion;

use PHPUnit\Framework\TestCase;
use gamboamartin\facturacion\controllers\_xls_dispersion;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use ReflectionClass;

final class celda_tiene_valorTest extends TestCase
{
    private Worksheet $hoja;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear un spreadsheet de prueba
        $spreadsheet = new Spreadsheet();
        $this->hoja = $spreadsheet->getActiveSheet();

        // Poblar algunos valores
        $this->hoja->setCellValue('A1', 'Hola');
        $this->hoja->setCellValue('B2', 0);
        $this->hoja->setCellValue('C3', null);
        $this->hoja->setCellValue('D4', '   ');
    }

    /**
     * Helper para invocar el método privado celda_tiene_valor()
     */
    private function callCeldaTieneValor(_xls_dispersion $obj, Worksheet $hoja, string $col, int $row): bool|array
    {
        $ref = new ReflectionClass($obj);
        $method = $ref->getMethod('celda_tiene_valor');
        $method->setAccessible(true);
        return $method->invoke($obj, $hoja, $col, $row);
    }

    public function testCeldaConTexto(): void
    {
        $cls = new _xls_dispersion();
        $res = $this->callCeldaTieneValor($cls, $this->hoja, 'A', 1);
        $this->assertTrue($res);
    }

    public function testCeldaConNumeroCero(): void
    {
        $cls = new _xls_dispersion();
        $res = $this->callCeldaTieneValor($cls, $this->hoja, 'B', 2);
        // 0 se considera contenido válido → true
        $this->assertTrue($res);
    }

    public function testCeldaNull(): void
    {
        $cls = new _xls_dispersion();
        $res = $this->callCeldaTieneValor($cls, $this->hoja, 'C', 3);
        $this->assertFalse($res);
    }

    public function testCeldaEspacios(): void
    {
        $cls = new _xls_dispersion();
        $res = $this->callCeldaTieneValor($cls, $this->hoja, 'D', 4);
        $this->assertFalse($res);
    }

    public function testFilaInvalida(): void
    {
        $cls = new _xls_dispersion();
        $res = $this->callCeldaTieneValor($cls, $this->hoja, 'A', 0);

        $this->assertIsArray($res); // debe devolver un array de error
        $this->assertArrayHasKey('mensaje', $res);
    }

    public function testColumnaInvalida(): void
    {
        $cls = new _xls_dispersion();
        $res = $this->callCeldaTieneValor($cls, $this->hoja, '1A', 1);

        $this->assertIsArray($res);
        $this->assertArrayHasKey('mensaje', $res);
    }
}