<?php
declare(strict_types=1);
namespace Pruebas\Partida { final class ConexionSinRed extends \PDO {public function __construct(){}} }
namespace base\controller {class controler {}}
namespace base\orm {class modelo {public function __construct(\PDO $link){}}}
namespace gamboamartin\errores {class errores {public static bool $error=false;}}
namespace gamboamartin\template {class html {}}
namespace gamboamartin\template_1 {class html extends \gamboamartin\template\html {}}
namespace gamboamartin\system {
 class html_controler {public function __construct(\gamboamartin\template\html $html){}}
 class links_menu {public array $ultima=[];public function __construct(\PDO $link,int $registro_id){} public function link_con_id(string $accion,\PDO $link,int $registro_id,string $seccion):string{$this->ultima=[$accion,$link,$registro_id,$seccion];return 'ENLACE_FICTICIO';}}
 class _ctl_base extends \base\controller\controler {
  public int $registro_id=-1;public bool $lista_get_data=false;public \PDO $link;public links_menu $obj_link;public \stdClass $config_recibida;public \stdClass $rutas_recibidas;public int $inicializaciones=0;
  public function __construct(html_controler $html,\PDO $link,\base\orm\modelo $modelo,links_menu $obj_link,\stdClass $datatables,\stdClass $paths_conf){$this->link=$link;$this->obj_link=$obj_link;$this->config_recibida=$datatables;$this->rutas_recibidas=$paths_conf;$this->inicializaciones++;}
 }
}
namespace gamboamartin\facturacion\models {class fc_partida extends \base\orm\modelo {} class fc_partida_nc extends \base\orm\modelo {}}
namespace gamboamartin\facturacion\html {class fc_partida_html extends \gamboamartin\system\html_controler {} class fc_partida_nc_html extends \gamboamartin\system\html_controler {}}
namespace gamboamartin\facturacion\controllers {class controlador_fc_traslado {public function __construct(\PDO $link,\stdClass $paths_conf){}}}
