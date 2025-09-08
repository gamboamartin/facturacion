<?php
namespace gamboamartin\facturacion\tests\controllers\_xml_dispersion;
use PHPUnit\Framework\TestCase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use gamboamartin\facturacion\controllers\_xls_dispersion;

final class valor_celda_normalizadoTest extends TestCase
{
    private _xls_dispersion $xls;

    protected function setUp(): void
    {
        $this->xls = new _xls_dispersion();
    }

    public function testDevuelveTextoNormalizado(): void
    {
        $spreadsheet = new Spreadsheet();
        $hoja = $spreadsheet->getActiveSheet();
        $hoja->setCellValue('A1', ' hola ');

        $resultado = $this->invokeValorCeldaNormalizado($hoja, 'A', 1);

        $this->assertSame('HOLA', $resultado);
    }

    public function testDevuelveNumeroComoTexto(): void
    {
        $spreadsheet = new Spreadsheet();
        $hoja = $spreadsheet->getActiveSheet();
        $hoja->setCellValue('B2', 123);

        $resultado = $this->invokeValorCeldaNormalizado($hoja, 'B', 2);

        $this->assertSame('123', $resultado);
    }



    public function testDevuelveVacioSiColumnaInvalida(): void
    {
        $spreadsheet = new Spreadsheet();
        $hoja = $spreadsheet->getActiveSheet();

        $resultado = $this->invokeValorCeldaNormalizado($hoja, '1A', 1);
        $this->assertSame('', $resultado);
    }

    public function testDevuelveVacioSiFilaInvalida(): void
    {
        $spreadsheet = new Spreadsheet();
        $hoja = $spreadsheet->getActiveSheet();

        $resultado = $this->invokeValorCeldaNormalizado($hoja, 'A', 0);
        $this->assertSame('', $resultado);
    }

    public function testDevuelveVacioSiLanzaExcepcion(): void
    {
        // Mock de hoja que lanza excepción en getCell()
        $hojaMock = $this->createMock(Worksheet::class);
        $hojaMock->method('getCell')
            ->willThrowException(new \RuntimeException('Celda inválida'));

        $resultado = $this->invokeValorCeldaNormalizado($hojaMock, 'A', 1);
        $this->assertSame('', $resultado);
    }

    /**
     * Helper para invocar método privado con Reflection.
     */
    private function invokeValorCeldaNormalizado($hoja, string $col, int $fila): string
    {
        $ref = new \ReflectionClass(_xls_dispersion::class);
        $method = $ref->getMethod('valor_celda_normalizado');
        return $method->invokeArgs($this->xls, [$hoja, $col, $fila]);
    }
}