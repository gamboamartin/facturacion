<?php
namespace gamboamartin\facturacion\tests\controllers\_xml_dispersion;

use PHPUnit\Framework\TestCase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use gamboamartin\facturacion\controllers\_xls_dispersion;

final class buscar_primera_coincidenciaTest extends TestCase
{
    private _xls_dispersion $xls;

    protected function setUp(): void
    {
        $this->xls = new _xls_dispersion();
    }

    public function testEncuentraPrimeraCoincidencia(): void
    {
        $spreadsheet = new Spreadsheet();
        $hoja = $spreadsheet->getActiveSheet();
        $hoja->setCellValue('A3', 'CLAVE EMPLEADO');

        $resultado = $this->invokeBuscarPrimeraCoincidencia(
            $hoja, 'A', 10, ['CLAVE EMPLEADO', 'CLAVEEMPLEADO']
        );

        $this->assertSame(3, $resultado);
    }

    public function testNoEncuentraCoincidencia(): void
    {
        $spreadsheet = new Spreadsheet();
        $hoja = $spreadsheet->getActiveSheet();
        $hoja->setCellValue('A1', 'SIN MATCH');

        $resultado = $this->invokeBuscarPrimeraCoincidencia(
            $hoja, 'A', 5, ['CLAVE EMPLEADO']
        );

        $this->assertSame(0, $resultado);
    }


    public function testRetornaCeroSiColumnaInvalida(): void
    {
        $spreadsheet = new Spreadsheet();
        $hoja = $spreadsheet->getActiveSheet();

        $resultado = $this->invokeBuscarPrimeraCoincidencia(
            $hoja, '1A', 5, ['CLAVE EMPLEADO']
        );

        $this->assertSame(0, $resultado);
    }

    public function testRetornaCeroSiMaxFilasInvalido(): void
    {
        $spreadsheet = new Spreadsheet();
        $hoja = $spreadsheet->getActiveSheet();

        $resultado = $this->invokeBuscarPrimeraCoincidencia(
            $hoja, 'A', 0, ['CLAVE EMPLEADO']
        );

        $this->assertSame(0, $resultado);
    }

    /**
     * Helper: invocar método privado con Reflection.
     */
    private function invokeBuscarPrimeraCoincidencia($hoja, string $col, int $maxFilas, array $candidatos): int
    {
        $ref = new \ReflectionClass(_xls_dispersion::class);
        $method = $ref->getMethod('buscar_primera_coincidencia');
        return $method->invokeArgs($this->xls, [$hoja, $col, $maxFilas, $candidatos]);
    }
}