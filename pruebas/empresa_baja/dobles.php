<?php
declare(strict_types=1);

namespace gamboamartin\errores {
    final class errores {
        public static bool $error = false;
        public function error(string $mensaje, mixed $data, bool $es_final = false): array {
            self::$error = true;
            return ['mensaje' => $mensaje, 'data' => $data, 'es_final' => $es_final];
        }
    }
}
namespace gamboamartin\organigrama\models {
    /** Solo contrato del padre ORM: DELETE real y fallos controlados. Sin configuración activa. */
    class org_empresa {
        public bool $desactiva_dependientes = true;
        public \gamboamartin\errores\errores $error;
        public static int $llamadas = 0;
        public static ?bool $flag_durante_baja = null;
        public static string $fallo = '';
        public function __construct(public \PDO $link) { $this->error = new \gamboamartin\errores\errores(); }
        public function elimina_bd(int $id): array|\stdClass {
            self::$llamadas++;
            self::$flag_durante_baja = $this->desactiva_dependientes;
            $q = $this->link->prepare('DELETE FROM org_empresa WHERE id = ?');
            $q->execute([$id]);
            if (self::$fallo === 'throw') { throw new \RuntimeException('Fallo sintético del ORM'); }
            if (self::$fallo === 'error') { return $this->error->error('Fallo sintético del ORM', $id); }
            return (object)['registro_id' => $id];
        }
    }
}
namespace gamboamartin\template { class html {} }
namespace gamboamartin\template_1 { class html extends \gamboamartin\template\html {} }
namespace gamboamartin\organigrama\controllers {
    class controlador_org_empresa {
        public \gamboamartin\organigrama\models\org_empresa $modelo;
        public \gamboamartin\template\html $html_recibido;
        public \stdClass $paths_recibidos;
        public function __construct(\PDO $link, \gamboamartin\template\html $html, \stdClass $paths_conf) {
            $this->modelo = new \gamboamartin\organigrama\models\org_empresa($link);
            $this->html_recibido = $html;
            $this->paths_recibidos = $paths_conf;
        }
        public function elimina_bd(int $id): array|\stdClass { return $this->modelo->elimina_bd($id); }
    }
}
namespace gamboamartin\facturacion\models {
    /** Adaptador exclusivo de la prueba; ejecuta la cascada CSD real con PDO. */
    final class fc_csd {
        public function __construct(private \PDO $link) {}
        public function elimina_bd(int $id): \stdClass {
            return (new referencias_baja_csd($this->link))->eliminar($id, function () use ($id): \stdClass {
                $q = $this->link->prepare('DELETE FROM fc_csd WHERE id = ?');
                $q->execute([$id]);
                if ($q->rowCount() !== 1) { throw new \RuntimeException('No se retiró el CSD sintético.'); }
                return (object)['registro_id' => $id];
            });
        }
    }
}
