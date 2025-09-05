<?php
namespace gamboamartin\facturacion\tests\controllers\_xml_dispersion;

use PHPUnit\Framework\TestCase;
use gamboamartin\facturacion\controllers\_xls_dispersion;
use ReflectionClass;

final class normaliza_textoTest extends TestCase
{
    private function callNormalizaTexto(_xls_dispersion $obj, mixed $val): string
    {
        $ref = new ReflectionClass($obj);
        $method = $ref->getMethod('normaliza_texto');
        return $method->invoke($obj, $val);
    }

    /**
     * @dataProvider providerValores
     */
    public function testNormalizaTexto(mixed $entrada, string $esperado): void
    {
        $cls = new _xls_dispersion();
        $resultado = $this->callNormalizaTexto($cls, $entrada);

        $this->assertSame(
            $esperado,
            $resultado,
            "Fallo al normalizar: entrada=" . var_export($entrada, true)
        );
    }

    public static function providerValores(): array
    {
        return [
            // Casos nulos y vacíos
            [null, ''],
            ['', ''],
            ['   ', ''],
            // Casos básicos
            ['hola', 'HOLA'],
            [' HOLA ', 'HOLA'],
            ['HoLa', 'HOLA'],
            // Con números
            ['123', '123'],
            [' 123 ', '123'],
            // Con símbolos
            ['@abc', '@ABC'],
            // Booleanos
            [true, '1'],   // (string)true = "1"
            [false, ''],   // (string)false = ""
            // Enteros y floats
            [0, '0'],
            [10, '10'],
            [10.5, '10.5'],
        ];
    }
}