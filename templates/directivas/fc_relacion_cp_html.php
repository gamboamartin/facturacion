<?php
namespace gamboamartin\facturacion\html;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\fc_relacion_cp;
use gamboamartin\system\html_controler;
use PDO;

class fc_relacion_cp_html extends html_controler {

    public function select_fc_relacion_cp_id(int $cols, bool $con_registros, int $id_selected, PDO $link,
                                             bool $required = false): array|string

    {
        $modelo = new fc_relacion_cp(link: $link);

        $select = $this->select_catalogo(cols:$cols,con_registros:$con_registros,id_selected:$id_selected,
            modelo: $modelo, label: "Complemento Pago",required: $required);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select', data: $select);
        }
        return $select;
    }

}
