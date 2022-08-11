<?php
namespace html;


use gamboamartin\errores\errores;
use gamboamartin\organigrama\controllers\controlador_org_empresa;
use gamboamartin\system\html_controler;

use models\base\limpieza;
use models\fc_cfd;
use models\org_empresa;
use PDO;
use stdClass;


class fc_cfd_html extends html_controler {


    public function select_fc_cfd_id(int $cols, bool $con_registros, int $id_selected, PDO $link): array|string
    {
        $modelo = new fc_cfd($link);

        $select = $this->select_catalogo(cols:$cols,con_registros:$con_registros,id_selected:$id_selected, modelo: $modelo, label: "CFD");
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select', data: $select);
        }
        return $select;
    }

}
