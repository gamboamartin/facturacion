<?php
namespace gamboamartin\facturacion\tests\controllers\_xml_dispersion;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class init_valueTest extends TestCase
{
    private object $instance;
    private ReflectionClass $refClass;

    protected function setUp(): void
    {
        // Ajusta el namespace y clase real de _xls_dispersion
        $this->refClass = new ReflectionClass(\gamboamartin\facturacion\controllers\_xls_dispersion::class);
        $this->instance = $this->refClass->newInstanceWithoutConstructor();
    }

    /**
     * Helper para invocar el método privado init_value
     */
    private function callInitValue(mixed $value): string
    {
        $method = $this->refClass->getMethod('init_value');
        return $method->invoke($this->instance, $value);
    }

    /**
     * @dataProvider providerInitValue
     */
    public function testInitValue(mixed $input, string $expected): void
    {
        $this->assertSame($expected, $this->callInitValue($input));
    }

    public static function providerInitValue(): array
    {
        return [
            'null devuelve vacío'          => [null, ''],
            'string normalizado'           => ['hola', 'HOLA'],
            'string con espacios'          => ['  mundo  ', 'MUNDO'],
            'string mixto'                 => [' TeSt ', 'TEST'],
            'número entero'                => [123, '123'],
            'número decimal'               => [45.67, '45.67'],
            'boolean true'                 => [true, '1'],
            'boolean false'                => [false, ''],
            'string vacío'                 => ['', ''],
            'string con solo espacios'     => ['   ', ''],
            'caracteres especiales'        => ['áéíóú', 'áéíóú'],
        ];
    }
}