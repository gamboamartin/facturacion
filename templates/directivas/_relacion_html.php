<?php

namespace gamboamartin\facturacion\html;

use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\_relacion;
use gamboamartin\system\html_controler;
use PDO;

class _relacion_html extends html_controler{


    public function select_entidad_relacion_id(
        int $cols, bool $con_registros, int $id_selected, _relacion $modelo_relacion, array $columns_ds = array(),
        bool $disabled = false, array $filtro = array(), array $registros = array()): array|string
    {

        $select = $this->select_catalogo(cols: $cols, con_registros: $con_registros, id_selected: $id_selected,
            modelo: $modelo_relacion, columns_ds: $columns_ds, disabled: $disabled, filtro: $filtro, label: 'Relacion',
            registros: $registros, required: true);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select', data: $select);
        }
        return $select;
    }


}
