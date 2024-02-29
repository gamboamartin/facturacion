<?php
/**
 * @author Martin Gamboa Vazquez
 * @version 1.0.0
 * @created 2022-05-14
 * @final En proceso
 *
 */
namespace gamboamartin\facturacion\controllers;

use gamboamartin\errores\errores;
use gamboamartin\facturacion\html\fc_factura_documento_html;
use gamboamartin\facturacion\models\fc_factura_documento;
use gamboamartin\system\links_menu;
use gamboamartin\system\system;

use gamboamartin\template_1\html;
use PDO;
use stdClass;

class controlador_fc_factura_documento extends system{

    public array|stdClass $keys_selects = array();

    public function __construct(PDO $link, html $html = new html(), stdClass $paths_conf = new stdClass()){
        $modelo = new fc_factura_documento(link: $link);
        $html_ = new fc_factura_documento_html(html: $html);
        $obj_link = new links_menu(link: $link, registro_id:  $this->registro_id);

        parent::__construct(html:$html_, link: $link,modelo:  $modelo, obj_link: $obj_link,
            paths_conf: $paths_conf);

        $this->lista_get_data = true;

    }

    final public function descarga(bool $header, bool $ws = false): array|string{
        if($this->registro_id <= 0 ){
            return $this->retorno_error(mensaje: 'Error id debe ser mayor a 0',data:  $this->registro_id,
                header:  $header,ws:  $ws);
        }
        $registro = $this->modelo->registro(registro_id: $this->registro_id,retorno_obj: true);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener registro', data: $registro,
                header:  $header,ws:  $ws);
        }
        $file_url = $registro->doc_documento_ruta_absoluta;

        if(!file_exists($file_url)){
            return $this->retorno_error(mensaje: 'Error file_url no existe', data: $file_url,
                header:  $header,ws:  $ws);
        }

        ob_clean();
        if($header) {
            header('Content-Type: application/octet-stream');
            header("Content-Transfer-Encoding: Binary");
            header("Content-disposition: attachment; filename=\"" . basename($registro->doc_documento_name_out) . "\"");
            readfile($file_url);
            exit;
        }
        return file_get_contents($file_url);

    }

}
