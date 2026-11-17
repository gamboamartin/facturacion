<?php
namespace gamboamartin\comercial\controllers;
class controlador_com_sucursal {
 public bool $lista_get_data=false;
 public int $precargas=0;
 public function lista():array { if (!$this->lista_get_data) $this->precargas++; return []; }
 public function get_data():array { return ['permisos'=>'heredados','pagina'=>10]; }
}
