<?php
namespace gamboamartin\facturacion\models;

use gamboamartin\errores\errores;
use stdClass;

class com_sucursal extends \gamboamartin\comercial\models\com_sucursal {

    public function alta_bd(): array|stdClass
    {
        if (isset($this->registro['cp'])) {
            $this->registro['cp'] = str_pad((string)$this->registro['cp'], 5, '0', STR_PAD_LEFT);
        }

        return parent::alta_bd();
    }

    public function modifica_bd(array $registro, int $id, bool $reactiva = false): array|stdClass
    {
        if (isset($registro['cp'])) {
            $registro['cp'] = str_pad((string)$registro['cp'], 5, '0', STR_PAD_LEFT);
        }

        return parent::modifica_bd(registro: $registro, id: $id, reactiva: $reactiva);
    }
}