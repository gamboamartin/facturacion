<?php
declare(strict_types=1);
namespace gamboamartin\errores {
    class errores { public static bool $error = false; public function error(string $mensaje, mixed $data): array { self::$error=true; return ['error'=>true,'mensaje'=>$mensaje,'data'=>$data]; } }
}
namespace Pruebas\Complemento {
    // Doble explícito: comprueba únicamente las precondiciones usadas por estos casos.
    // No acredita la implementación real del paquete de validación.
    final class ValidadorAislado {
        public ?string $falloForzado = null;
        public array $llamadas = [];
        public function valida_existencia_keys(array $keys,array $registro): bool|array {
            $this->llamadas[]="claves";
            if ($this->falloForzado === "claves") return (new \gamboamartin\errores\errores())->error("Fallo ficticio de claves", $keys);
            foreach ($keys as $key) if (!array_key_exists($key,$registro)) throw new \LogicException('Dato ficticio incompleto: '.$key);
            return true;
        }
        public function valida_numerics(array $keys,array $row): bool|array {
            $this->llamadas[]="numeros";
            if ($this->falloForzado === "numeros") return (new \gamboamartin\errores\errores())->error("Fallo ficticio de numeros", $keys);
            foreach ($keys as $key) if (!is_numeric($row[$key])) throw new \LogicException('Dato ficticio no numérico: '.$key);
            return true;
        }
    }
}
namespace gamboamartin\facturacion\models {
    class _transacciones_fc { public \Pruebas\Complemento\ValidadorAislado $validacion; public \gamboamartin\errores\errores $error; public string $tabla=''; }
}
namespace gamboamartin\facturacion\controllers {
    class _base_system_fc { public \gamboamartin\facturacion\models\fc_complemento_pago $modelo; }
}
