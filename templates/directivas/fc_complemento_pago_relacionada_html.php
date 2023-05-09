<?php
namespace gamboamartin\facturacion\html;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\fc_complemento_pago_relacionada;
use gamboamartin\system\html_controler;
use PDO;

class fc_complemento_pago_relacionada_html extends html_controler {

    public function select_fc_complemento_pago_relacionada_id(int $cols, bool $con_registros, int $id_selected, PDO $link,
                                         array $columns_ds = array('fc_complemento_pago_descripcion_select'),
                                         bool $disabled = false, array $filtro = array(),
                                         array $registros = array()): array|string
    {
        $modelo = new fc_complemento_pago_relacionada(link: $link);

        $select = $this->select_catalogo(cols: $cols, con_registros: $con_registros, id_selected: $id_selected,
            modelo: $modelo, columns_ds: $columns_ds, disabled: $disabled, filtro: $filtro, label: 'Complemento Pago Relacionada',
            registros: $registros, required: true);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select', data: $select);
        }
        return $select;
    }

}
