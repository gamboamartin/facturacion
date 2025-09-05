<?php
namespace gamboamartin\facturacion\tests\controllers\_xml_dispersion;
use PHPUnit\Framework\TestCase;
use gamboamartin\facturacion\controllers\_xls_dispersion;
use ReflectionClass;

final class tiene_contenido_no_vacioTest extends TestCase
{
    private _xls_dispersion $xls;

    protected function setUp(): void
    {
        $this->xls = new _xls_dispersion();
    }

    /**
     * Accede al método privado tiene_contenido_no_vacio
     */
    private function callTieneContenido(mixed $valor): bool
    {
        $ref = new ReflectionClass($this->xls);
        $method = $ref->getMethod('tiene_contenido_no_vacio');
        return $method->invokeArgs($this->xls, [$valor]);
    }

    public function testNullValue(): void
    {
        $this->assertFalse($this->callTieneContenido(null));
    }

    public function testEmptyString(): void
    {
        $this->assertFalse($this->callTieneContenido(''));
    }

    public function testSpacesOnly(): void
    {
        $this->assertFalse($this->callTieneContenido('   '));
    }

    public function testStringWithContent(): void
    {
        $this->assertTrue($this->callTieneContenido('ABC'));
    }

    public function testStringWithSpacesAndContent(): void
    {
        $this->assertTrue($this->callTieneContenido('  XYZ  '));
    }

    public function testNumericZero(): void
    {
        // Se castea a "0", que tras trim() no es vacío
        $this->assertTrue($this->callTieneContenido(0));
    }

    public function testNumericValue(): void
    {
        $this->assertTrue($this->callTieneContenido(123));
    }

    public function testBooleanFalse(): void
    {
        // (string)false === "" → vacío
        $this->assertFalse($this->callTieneContenido(false));
    }

    public function testBooleanTrue(): void
    {
        // (string)true === "1" → no vacío
        $this->assertTrue($this->callTieneContenido(true));
    }
}