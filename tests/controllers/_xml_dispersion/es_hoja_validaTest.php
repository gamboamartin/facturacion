<?php
namespace gamboamartin\facturacion\tests\controllers\_xml_dispersion;
use PHPUnit\Framework\TestCase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use gamboamartin\facturacion\controllers\_xls_dispersion;
use ReflectionClass;

final class es_hoja_validaTest extends TestCase
{
    private _xls_dispersion $xls;

    protected function setUp(): void
    {
        $this->xls = new _xls_dispersion();
    }

    /**
     * Helper para acceder al método privado es_hoja_valida
     */
    private function callEsHojaValida(mixed $hoja): bool
    {
        $ref = new ReflectionClass($this->xls);
        $method = $ref->getMethod('es_hoja_valida');

        return $method->invokeArgs($this->xls, [$hoja]);
    }

    public function testHojaValida(): void
    {
        $spreadsheet = new Spreadsheet();
        $hoja = $spreadsheet->getActiveSheet();

        $this->assertTrue($this->callEsHojaValida($hoja));
    }

    public function testHojaNula(): void
    {
        $this->assertFalse($this->callEsHojaValida(null));
    }

    public function testHojaTipoString(): void
    {
        $this->assertFalse($this->callEsHojaValida("no es hoja"));
    }

    public function testHojaTipoInt(): void
    {
        $this->assertFalse($this->callEsHojaValida(123));
    }

    public function testHojaArray(): void
    {
        $this->assertFalse($this->callEsHojaValida(['A1' => 'valor']));
    }

    public function testHojaStdClass(): void
    {
        $this->assertFalse($this->callEsHojaValida(new \stdClass()));
    }
}