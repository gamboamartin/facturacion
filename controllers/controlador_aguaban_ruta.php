<?php
declare(strict_types=1);

namespace gamboamartin\facturacion\controllers;

use base\controller\controler;
use gamboamartin\administrador\models\adm_menu;
use gamboamartin\administrador\models\adm_seccion;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\aguaban_ruta;
use gamboamartin\facturacion\models\_aguaban_ruta_acceso;
use InvalidArgumentException;
use PDO;
use RuntimeException;
use stdClass;
use Throwable;

/** CRUD aislado del catálogo de rutas; ninguna escritura se ejecuta por GET. */
final class controlador_aguaban_ruta extends controler
{
    public array $ruta_config = [];
    public array $menu_permitido = [];
    public int $adm_menu_id = -1;
    public string $menu_header = '';
    private aguaban_ruta $rutas;

    public function __construct(PDO $link, stdClass $paths_conf = new stdClass())
    {
        $accion = is_string($_GET['accion'] ?? null) ? $_GET['accion'] : '';
        $usuario = _aguaban_ruta_acceso::usuario_sesion();
        $acceso = new _aguaban_ruta_acceso($link, $usuario);
        try {
            $acceso->exigir($accion);
        } catch (Throwable) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=utf-8');
            exit('No tienes permiso para acceder al catálogo de rutas.');
        }
        $this->rutas = new aguaban_ruta($link, $usuario);
        parent::__construct($link);
        $this->paths_conf = $paths_conf;
        $this->tabla = 'aguaban_ruta';
        $this->seccion = 'aguaban_ruta';
        $this->accion = $accion;
        $this->registro_id = $this->id_ruta_seguro();
        $this->accion_titulo = ucwords(str_replace('_', ' ', $accion));
        $this->seccion_titulo = 'Rutas de medición';
        $this->titulo_lista = 'Rutas de medición';
        $this->ruta_config = $this->configuracion_ruta();
        $this->navegacion_ruta();
    }

    public function url_ruta(string $accion, ?int $id = null, array $extra = []): string
    {
        if (!in_array($accion, _aguaban_ruta_acceso::ACCIONES, true) || ($id !== null && $id <= 0)) {
            throw new InvalidArgumentException('La dirección de rutas no es válida.');
        }
        $query = ['seccion' => 'aguaban_ruta', 'accion' => $accion];
        $sesion = isset($this->session_id) ? $this->session_id : ($_GET['session_id'] ?? '');
        if (is_string($sesion) && $sesion !== '') {
            $query['session_id'] = $sesion;
        }
        if ($this->adm_menu_id > 0) {
            $query['adm_menu_id'] = $this->adm_menu_id;
        }
        if ($id !== null) {
            $query['registro_id'] = $id;
        }
        foreach (['codigo', 'descripcion', 'sector', 'adm_usuario_id', 'status', 'pagina', 'limite'] as $campo) {
            if (isset($extra[$campo]) && is_scalar($extra[$campo])) {
                $query[$campo] = (string)$extra[$campo];
            }
        }
        return 'index.php?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    public function lista(bool $header = true, bool $ws = false): array|stdClass
    {
        return $this->leer_ruta('lista', function (): void {
            $filtros = array_intersect_key($_GET, array_flip(['codigo', 'descripcion', 'sector', 'adm_usuario_id', 'status']));
            $this->ruta_config['filtros'] = $filtros;
            $this->ruta_config['resultado'] = $this->rutas->listar_rutas($filtros,
                $this->entero_ruta($_GET['pagina'] ?? '1', 'La página'),
                $this->entero_ruta($_GET['limite'] ?? '20', 'El tamaño de página'));
        });
    }

    public function alta(bool $header = true, bool $ws = false): array|stdClass
    {
        return $this->leer_ruta('alta', function (): void {
            $this->ruta_config['registro'] = ['codigo' => '', 'descripcion' => '', 'sector' => null,
                'adm_usuario_id' => null, 'status' => 'activo'];
        });
    }

    public function modifica(bool $header = true, bool $ws = false): array|stdClass
    {
        return $this->leer_registro_ruta('modifica');
    }

    public function consulta(bool $header = true, bool $ws = false): array|stdClass
    {
        return $this->leer_registro_ruta('consulta');
    }

    public function elimina(bool $header = true, bool $ws = false): array|stdClass
    {
        return $this->leer_registro_ruta('elimina');
    }

    public function alta_bd(bool $header = true, bool $ws = false): array|stdClass
    {
        return $this->escribir_ruta('alta_bd', fn(array $datos): array => $this->rutas->crear_ruta($datos),
            'La ruta se creó correctamente.', $header, $ws);
    }

    public function modifica_bd(bool $header = true, bool $ws = false): array|stdClass
    {
        return $this->escribir_ruta('modifica_bd', fn(array $datos): array => $this->rutas->actualizar_ruta($this->id_ruta(), $datos),
            'La ruta se actualizó correctamente.', $header, $ws);
    }

    public function elimina_bd(bool $header = true, bool $ws = false): array|stdClass
    {
        return $this->escribir_ruta('elimina_bd', function (array $datos): array {
            if ($datos !== []) {
                throw new InvalidArgumentException('El formulario de eliminación no es válido.');
            }
            return $this->rutas->eliminar_ruta($this->id_ruta());
        }, 'La ruta se eliminó correctamente.', $header, $ws);
    }

    public function status(bool $header = true, bool $ws = false): array|stdClass
    {
        return $this->escribir_ruta('status', function (array $datos): array {
            if (array_keys($datos) !== ['status'] || !is_string($datos['status'])) {
                throw new InvalidArgumentException('El formulario de estado no es válido.');
            }
            return $this->rutas->estado_ruta($this->id_ruta(), $datos['status']);
        }, 'El estado de la ruta se actualizó correctamente.', $header, $ws);
    }

    /** Conserva las fuentes de navegación del framework sin heredar sus escrituras ORM. */
    private function navegacion_ruta(): void
    {
        $secciones = new adm_seccion($this->link);
        $seccion = $secciones->seccion_by_descripcion('aguaban_ruta');
        if (errores::$error || !is_object($seccion)) {
            throw new RuntimeException('No se pudo identificar el menú de rutas.');
        }
        $menuId = (int)($seccion->adm_menu_id ?? 0);
        if ($menuId <= 0) {
            throw new RuntimeException('La sección de rutas no tiene un menú válido.');
        }
        $menus = (new adm_menu($this->link))->menus_visibles_permitidos_full();
        if (errores::$error || !is_array($menus)) {
            throw new RuntimeException('No se pudieron obtener los menús permitidos.');
        }
        $permitidas = $secciones->secciones_permitidas(adm_menu_id: $menuId);
        if (errores::$error) {
            throw new RuntimeException('No se pudieron obtener las secciones permitidas.');
        }
        $this->adm_menu_id = $menuId;
        $this->menu_permitido = $menus;
        $this->secciones_permitidas = $permitidas;
        $html = '';
        foreach ($menus as $menu) {
            if (!is_array($menu) || (int)($menu['adm_menu_id'] ?? 0) <= 0
                || !is_string($menu['adm_menu_titulo'] ?? null)) {
                throw new RuntimeException('La navegación contiene un menú inválido.');
            }
            $url = 'index.php?'.http_build_query(['seccion' => 'adm_session', 'accion' => 'inicio',
                'session_id' => $this->session_id, 'adm_menu_id' => (int)$menu['adm_menu_id']], '', '&', PHP_QUERY_RFC3986);
            $html .= '<li><a href="'.htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'">'
                .htmlspecialchars($menu['adm_menu_titulo'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</a></li>';
        }
        $this->menu_header = $html;
        // Las URLs comunes también llevan el menú real, nunca el parámetro recibido.
        foreach (_aguaban_ruta_acceso::ACCIONES as $accion) {
            $this->ruta_config['urls'][$accion] = $this->url_ruta($accion);
        }
    }

    private function configuracion_ruta(): array
    {
        if (!is_string($_SESSION['aguaban_ruta_csrf'] ?? null) || !preg_match('/^[a-f0-9]{64}$/D', $_SESSION['aguaban_ruta_csrf'])) {
            $_SESSION['aguaban_ruta_csrf'] = bin2hex(random_bytes(32));
        }
        $operadores = $this->rutas->operadores_ruta();
        foreach ($operadores as &$operador) {
            if ($operador['status'] !== 'activo') {
                $operador['descripcion'] .= ' (inactivo)';
            }
        }
        unset($operador);
        $urls = [];
        foreach (_aguaban_ruta_acceso::ACCIONES as $accion) {
            $urls[$accion] = $this->url_ruta($accion);
        }
        $exito = $_SESSION['aguaban_ruta_exito'] ?? '';
        unset($_SESSION['aguaban_ruta_exito']);
        return ['csrf' => $_SESSION['aguaban_ruta_csrf'], 'permisos' => $this->rutas->permisos_ruta(),
            'urls' => $urls, 'registro' => [], 'operadores' => $operadores, 'filtros' => [],
            'resultado' => ['registros' => [], 'total' => 0, 'pagina' => 1, 'paginas' => 1, 'limite' => 20],
            'error' => '', 'exito' => is_string($exito) ? $exito : ''];
    }

    private function leer_registro_ruta(string $accion): array|stdClass
    {
        return $this->leer_ruta($accion, function () use ($accion): void {
            $this->ruta_config['registro'] = $this->rutas->obtener_ruta($this->id_ruta(), $accion);
        });
    }

    private function leer_ruta(string $accion, callable $leer): array
    {
        try {
            $this->exigir_ruta($accion);
            $leer();
            return ['ok' => true];
        } catch (Throwable $error) {
            return $this->error_ruta($error);
        }
    }

    private function escribir_ruta(string $accion, callable $escribir, string $mensaje, bool $header, bool $ws): array
    {
        try {
            $this->exigir_ruta($accion);
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
                throw new RuntimeException('Esta operación requiere enviar el formulario por POST.', 405);
            }
            $csrf = $_POST['csrf'] ?? null;
            if (!is_string($csrf) || !is_string($_SESSION['aguaban_ruta_csrf'] ?? null)
                || !hash_equals($_SESSION['aguaban_ruta_csrf'], $csrf)) {
                throw new RuntimeException('La sesión del formulario venció. Actualiza la pantalla.', 403);
            }
            $datos = $_POST;
            unset($datos['csrf']);
            $registro = $escribir($datos);
            $this->ruta_config['registro'] = $registro;
            $this->ruta_config['exito'] = $mensaje;
            if ($header && !$ws) {
                $_SESSION['aguaban_ruta_exito'] = $mensaje;
                $destino = $accion === 'elimina_bd' ? 'lista' : 'consulta';
                if (!($this->rutas->permisos_ruta()[$destino] ?? false)) {
                    $destino = 'lista';
                }
                $id = $destino === 'consulta' ? (int)$registro['id'] : null;
                header('Location: '.$this->url_ruta($destino, $id), true, 303);
                exit;
            }
            return ['ok' => true, 'registro' => $registro, 'mensaje' => $mensaje];
        } catch (Throwable $error) {
            // Solo el formulario de edición conserva sus campos editables, nunca auditoría ni hash.
            if (in_array($accion, ['alta_bd', 'modifica_bd'], true)) {
                $this->ruta_config['registro'] = array_intersect_key($_POST, array_flip(['codigo', 'descripcion', 'sector', 'adm_usuario_id', 'status']));
                if ($accion === 'modifica_bd') {
                    $this->ruta_config['registro']['id'] = $this->id_ruta_seguro();
                    // El estado oculto vuelve al valor real si su permiso fue retirado.
                    // La lectura vuelve a exigir modifica_bd: nunca recupera datos tras revocarlo.
                    try {
                        $permisos = $this->rutas->permisos_ruta();
                        $this->ruta_config['permisos'] = $permisos;
                        if (!$permisos['status'] && $permisos['modifica_bd']) {
                            $actual = $this->rutas->obtener_ruta($this->id_ruta(), 'modifica_bd');
                            $this->ruta_config['registro']['status'] = $actual['status'];
                        }
                    } catch (Throwable) {
                        // Conservar únicamente la captura recibida, sin consultar otra vía.
                    }
                }
            } elseif ($this->id_ruta_seguro() > 0) {
                try {
                    $this->ruta_config['registro'] = $this->rutas->obtener_ruta($this->id_ruta_seguro(), $accion);
                } catch (Throwable) {
                    $this->ruta_config['registro'] = [];
                }
            }
            return $this->error_ruta($error);
        }
    }

    private function exigir_ruta(string $accion): void
    {
        if (!(new _aguaban_ruta_acceso($this->link, _aguaban_ruta_acceso::usuario_sesion()))->puede($accion)) {
            throw new RuntimeException('No tienes permiso para realizar esta operación de rutas.', 403);
        }
        $this->ruta_config['permisos'] = $this->rutas->permisos_ruta();
    }

    private function id_ruta(): int
    {
        return $this->entero_ruta($_GET['registro_id'] ?? null, 'El identificador de ruta');
    }

    private function id_ruta_seguro(): int
    {
        try {
            return $this->id_ruta();
        } catch (Throwable) {
            return 0;
        }
    }

    private function entero_ruta(mixed $valor, string $nombre): int
    {
        if ((!is_string($valor) && !is_int($valor)) || !preg_match('/^[1-9][0-9]*$/D', (string)$valor)
            || filter_var($valor, FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException($nombre.' debe ser un entero positivo.');
        }
        return (int)$valor;
    }

    private function error_ruta(Throwable $error): array
    {
        $conocido = ($error instanceof InvalidArgumentException || $error instanceof RuntimeException) && !$error instanceof \PDOException;
        $mensaje = $conocido ? $error->getMessage() : 'No se pudo completar la operación de rutas.';
        $codigo = in_array($error->getCode(), [403, 404, 405, 409], true) ? $error->getCode() : ($conocido ? 422 : 500);
        http_response_code($codigo);
        $this->ruta_config['error'] = $mensaje;
        return ['ok' => false, 'mensaje' => $mensaje];
    }
}
