<?php
namespace gamboamartin\facturacion\tests\controllers\_xml_dispersion;

use PHPUnit\Framework\TestCase;
use gamboamartin\facturacion\controllers\_xls_dispersion;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use ReflectionClass;
use Throwable;

final class leer_valor_celdaTest extends TestCase
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
        $this->hoja->setCellValue('B2', 123);
        $this->hoja->setCellValue('C3', null);
    }

    /**
     * Helper para invocar el método privado leer_valor_celda()
     */
    private function callLeerValorCelda(_xls_dispersion $obj, Worksheet $hoja, string $col, int $row): mixed
    {
        $ref = new ReflectionClass($obj);
        $method = $ref->getMethod('leer_valor_celda');
        return $method->invoke($obj, $hoja, $col, $row);
    }

    public function testLeeTexto(): void
    {
        $cls = new _xls_dispersion();
        $resultado = $this->callLeerValorCelda($cls, $this->hoja, 'A', 1);

        $this->assertSame('Hola', $resultado);
    }

    public function testLeeNumero(): void
    {
        $cls = new _xls_dispersion();
        $resultado = $this->callLeerValorCelda($cls, $this->hoja, 'B', 2);

        $this->assertSame(123, $resultado);
    }

    public function testLeeNull(): void
    {
        $cls = new _xls_dispersion();
        $resultado = $this->callLeerValorCelda($cls, $this->hoja, 'C', 3);

        $this->assertNull($resultado);
    }


}