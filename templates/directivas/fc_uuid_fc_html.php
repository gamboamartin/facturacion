<?php
namespace gamboamartin\facturacion\html;

use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\fc_uuid_fc;
use gamboamartin\system\html_controler;
use PDO;
class fc_uuid_fc_html extends html_controler {


    public function select_fc_uuid_fc_id(int $cols, bool $con_registros, int $id_selected, PDO $link,
                                         array $columns_ds = array('fc_uuid_uuid','com_cliente_rfc','fc_uuid_folio', 'fc_uuid_total'),
                                         bool $disabled = false, array $filtro = array(),
                                         array $registros = array()): array|string
    {
        $modelo = new fc_uuid_fc(link: $link);

        $select = $this->select_catalogo(cols: $cols, con_registros: $con_registros, id_selected: $id_selected,
            modelo: $modelo, columns_ds: $columns_ds, disabled: $disabled, filtro: $filtro, label: 'Factura Externa Rel',
            registros: $registros, required: true);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select', data: $select);
        }
        return $select;
    }
}
