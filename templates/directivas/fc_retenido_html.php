<?php
namespace html;


use gamboamartin\errores\errores;
use gamboamartin\facturacion\controllers\controlador_fc_cer_csd;
use gamboamartin\organigrama\controllers\controlador_org_empresa;
use gamboamartin\system\html_controler;

use models\base\limpieza;
use models\fc_cer_csd;
use models\fc_retenido;
use models\org_empresa;
use PDO;
use stdClass;


class fc_retenido_html extends html_controler {

    public function select_fc_retenido_id(int $cols, bool $con_registros, int $id_selected, PDO $link,
                                          bool $required = false): array|string
    {
        $modelo = new fc_retenido($link);

        $select = $this->select_catalogo(cols:$cols,con_registros:$con_registros,id_selected:$id_selected,
            modelo: $modelo, label: "Retenido", required: $required);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select', data: $select);
        }
        return $select;
    }

}
