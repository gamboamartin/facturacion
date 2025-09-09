<?php
namespace gamboamartin\facturacion\tests\controllers\_xml_dispersion;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use gamboamartin\facturacion\controllers\_xls_dispersion;
use gamboamartin\errores\errores;

final class valida_columnasTest extends TestCase
{
    private _xls_dispersion $obj;
    private ReflectionClass $ref;

    protected function setUp(): void
    {
        $this->obj = new _xls_dispersion();
        $this->ref = new ReflectionClass($this->obj);
        errores::$error = false; // reset bandera global
    }

    private function callPrivate(string $method, mixed ...$args): mixed
    {
        $m = $this->ref->getMethod($method);
        $m->setAccessible(true);
        return $m->invoke($this->obj, ...$args);
    }

    public function testValidaColumnas_ok(): void
    {
        $result = $this->callPrivate('valida_columnas', ['A', 'B', 'C']);
        $this->assertTrue($result);
        $this->assertFalse(errores::$error);
    }

    public function testValidaColumnas_empty(): void
    {
        $result = $this->callPrivate('valida_columnas', []);
        $this->assertIsArray($result);
        $this->assertTrue(errores::$error);
        $this->assertArrayHasKey('mensaje', $result);
    }

    public function testValidaColumnas_invalidaMinuscula(): void
    {
        errores::$error = false;
        $result = $this->callPrivate('valida_columnas', ['a', 'B']);
        $this->assertIsArray($result);
        $this->assertTrue(errores::$error);
        $this->assertStringContainsString('Columna inválida', $result['mensaje']);
    }

    public function testValidaColumnas_invalidaNoString(): void
    {
        errores::$error = false;
        $result = $this->callPrivate('valida_columnas', ['A', 123]);
        $this->assertIsArray($result);
        $this->assertTrue(errores::$error);
        $this->assertEquals('123', (string)$result['data']['columna']);
    }
}