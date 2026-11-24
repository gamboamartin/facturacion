<?php
declare(strict_types=1);
namespace gamboamartin\facturacion\models;
/** Interrumpe el flujo antes de impuestos/ORM fiscal; la guardia externa es la clase real. */
final class fc_pago_pago {
    public function __construct(\PDO $link){}
    public function registro(int $registro_id):array {\gamboamartin\errores\errores::$error=true;return ['error'=>true,'mensaje'=>'Fallo sintético anterior a la contraparte'];}
}
