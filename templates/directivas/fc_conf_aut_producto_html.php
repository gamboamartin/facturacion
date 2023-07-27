<?php
namespace gamboamartin\facturacion\html;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\fc_conf_aut_producto;
use gamboamartin\system\html_controler;
use PDO;

class fc_conf_aut_producto_html extends html_controler {

    public function select_fc_conf_aut_producto_id(int $cols, bool $con_registros, int $id_selected, PDO $link,
                                         array $columns_ds = array('fc_conf_automatico_descripcion','com_producto_descripcion'),
                                         bool $disabled = false, array $filtro = array(),
                                         array $registros = array()): array|string
    {
        $modelo = new fc_conf_aut_producto(link: $link);

        $select = $this->select_catalogo(cols: $cols, con_registros: $con_registros, id_selected: $id_selected,
            modelo: $modelo, columns_ds: $columns_ds, disabled: $disabled, filtro: $filtro, label: 'Configuracion de productos',
            registros: $registros, required: true);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select', data: $select);
        }
        return $select;
    }


}
