<?php declare(strict_types=1);
namespace Pruebas\Render;
class PartidaDoble extends \gamboamartin\facturacion\controllers\_ctl_partida {
 public function init_selects_inputs():array {$this->events[]='selects';if($this->fail==='selects')return $this->errores->error('FALLO_SELECTS',[]);return ['fc_factura_id'=>(object)['id_selected'=>-1]];}
}
