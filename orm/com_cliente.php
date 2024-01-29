<?php
namespace gamboamartin\facturacion\models;
use gamboamartin\errores\errores;
use stdClass;

class com_cliente extends \gamboamartin\comercial\models\com_cliente {

    public function elimina_bd(int $id): array|stdClass
    {
        $filtro[$this->key_filtro_id] = $id;
        $del = (new fc_receptor_email(link: $this->link))->elimina_con_filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al eliminar receptores',data:  $del);
        }

        $del = parent::elimina_bd(id: $id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al eliminar ',data:  $del);
        }
        return $del;
    }
}