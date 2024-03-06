<?php
namespace gamboamartin\facturacion\controllers;

use gamboamartin\errores\errores;
use gamboamartin\system\system;

class _entidad_docto extends system
{

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
