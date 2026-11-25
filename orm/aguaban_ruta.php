<?php
declare(strict_types=1);

namespace gamboamartin\facturacion\models;

use InvalidArgumentException;
use PDO;
use PDOException;
use RuntimeException;

/** Catálogo de rutas con el esquema solicitado, sin los campos auxiliares de otros catálogos. */
final class aguaban_ruta
{
    private readonly int $usuarioRuta;
    private readonly _aguaban_ruta_acceso $accesoRuta;
    private const CAMPOS = ['codigo', 'descripcion', 'sector', 'adm_usuario_id', 'status'];

    public function __construct(private readonly PDO $link, int $usuarioId = 0)
    {
        $this->usuarioRuta = $usuarioId > 0 ? $usuarioId : _aguaban_ruta_acceso::usuario_sesion();
        $this->accesoRuta = new _aguaban_ruta_acceso($link, $this->usuarioRuta);
    }

    public function permisos_ruta(): array
    {
        return $this->accesoRuta->permisos();
    }

    public function listar_rutas(array $filtros = [], int $pagina = 1, int $limite = 20): array
    {
        $this->accesoRuta->exigir('lista');
        if ($pagina < 1 || !in_array($limite, [20, 50, 100], true)) {
            throw new InvalidArgumentException('La página o el tamaño de la lista no son válidos.');
        }
        [$where, $params] = $this->filtros_ruta($filtros);
        $total = (int)$this->consulta_ruta('SELECT COUNT(*) FROM aguaban_ruta r'.$where, $params)->fetchColumn();
        $paginas = max(1, (int)ceil($total / $limite));
        $pagina = min($pagina, $paginas);
        $offset = ($pagina - 1) * $limite;
        // LIMIT y OFFSET proceden únicamente de enteros acotados, nunca del SQL del formulario.
        $registros = $this->consulta_ruta($this->select_ruta().$where.' ORDER BY r.codigo,r.id LIMIT '.$limite.' OFFSET '.$offset, $params)
            ->fetchAll(PDO::FETCH_ASSOC);
        return compact('registros', 'total', 'pagina', 'paginas', 'limite');
    }

    public function obtener_ruta(int $id, string $accion = 'consulta'): array
    {
        if (!in_array($accion, ['consulta', 'modifica', 'elimina', 'status', 'modifica_bd', 'elimina_bd'], true)) {
            throw new InvalidArgumentException('La operación de consulta no es válida.');
        }
        $this->accesoRuta->exigir($accion);
        return $this->registro_ruta($id);
    }

    public function operadores_ruta(): array
    {
        if (!in_array(true, $this->permisos_ruta(), true)) {
            throw new RuntimeException('No tienes permiso para consultar operadores de rutas.', 403);
        }
        return $this->consulta_ruta("SELECT u.id,COALESCE(NULLIF(TRIM(u.descripcion),''),NULLIF(TRIM(u.nombre),''),NULLIF(TRIM(u.user),''),CONCAT('Usuario #',u.id)) AS descripcion,u.status FROM adm_usuario u ORDER BY descripcion,u.id")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function crear_ruta(array $datos): array
    {
        $this->accesoRuta->exigir('alta_bd');
        $valores = $this->datos_ruta($datos);
        $hash = bin2hex(random_bytes(32));
        $this->consulta_ruta('INSERT INTO aguaban_ruta (codigo,descripcion,sector,adm_usuario_id,status,hash_version,usuario_alta_id,usuario_update_id)
            VALUES (?,?,?,?,?,?,?,?)', [...array_values($valores), $hash, $this->usuarioRuta, $this->usuarioRuta]);
        return $this->registro_ruta((int)$this->link->lastInsertId());
    }

    public function actualizar_ruta(int $id, array $datos): array
    {
        $this->accesoRuta->exigir('modifica_bd');
        $actual = $this->registro_ruta($id);
        $valores = $this->datos_ruta($datos);
        if ($valores['status'] !== $actual['status']) {
            $this->accesoRuta->exigir('status');
        }
        $this->consulta_ruta('UPDATE aguaban_ruta SET codigo=?,descripcion=?,sector=?,adm_usuario_id=?,status=?,hash_version=?,usuario_update_id=? WHERE id=?',
            [...array_values($valores), bin2hex(random_bytes(32)), $this->usuarioRuta, $id]);
        return $this->registro_ruta($id);
    }

    public function estado_ruta(int $id, string $status): array
    {
        $this->accesoRuta->exigir('status');
        $this->estado_valido($status);
        $this->registro_ruta($id);
        $this->consulta_ruta('UPDATE aguaban_ruta SET status=?,hash_version=?,usuario_update_id=? WHERE id=?',
            [$status, bin2hex(random_bytes(32)), $this->usuarioRuta, $id]);
        return $this->registro_ruta($id);
    }

    public function eliminar_ruta(int $id): array
    {
        $this->accesoRuta->exigir('elimina_bd');
        $registro = $this->registro_ruta($id);
        $q = $this->consulta_ruta('DELETE FROM aguaban_ruta WHERE id=?', [$id]);
        if ($q->rowCount() !== 1) {
            throw new RuntimeException('La ruta cambió o ya no existe. Actualiza la lista.', 404);
        }
        return ['id' => $id, 'codigo' => $registro['codigo']];
    }

    private function registro_ruta(int $id): array
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('El identificador de ruta debe ser un entero positivo.');
        }
        $registro = $this->consulta_ruta($this->select_ruta().' WHERE r.id=?', [$id])->fetch(PDO::FETCH_ASSOC);
        if (!is_array($registro)) {
            throw new RuntimeException('La ruta solicitada no existe.', 404);
        }
        return $registro;
    }

    private function select_ruta(): string
    {
        return "SELECT r.*,CASE WHEN u.id IS NULL THEN NULL ELSE COALESCE(NULLIF(TRIM(u.descripcion),''),NULLIF(TRIM(u.nombre),''),NULLIF(TRIM(u.user),''),CONCAT('Usuario #',u.id)) END AS operador_descripcion FROM aguaban_ruta r LEFT JOIN adm_usuario u ON u.id=r.adm_usuario_id";
    }

    private function datos_ruta(array $datos): array
    {
        if (array_diff(array_keys($datos), self::CAMPOS)) {
            throw new InvalidArgumentException('El formulario contiene campos que no se pueden modificar.');
        }
        $codigo = $this->texto_ruta($datos['codigo'] ?? null, 'El código', 32, true);
        $descripcion = $this->texto_ruta($datos['descripcion'] ?? null, 'La descripción', 150, true);
        $sector = $this->texto_ruta($datos['sector'] ?? null, 'El sector', 100, false);
        $operador = $datos['adm_usuario_id'] ?? null;
        if ($operador === '' || $operador === null) {
            $operador = null;
        } elseif ((!is_string($operador) && !is_int($operador)) || !preg_match('/^[1-9][0-9]*$/D', (string)$operador)
            || filter_var($operador, FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException('Selecciona un operador válido o deja la ruta sin asignar.');
        } else {
            $operador = (int)$operador;
            if (!$this->consulta_ruta('SELECT id FROM adm_usuario WHERE id=?', [$operador])->fetchColumn()) {
                throw new InvalidArgumentException('El operador seleccionado no existe.');
            }
        }
        $status = $datos['status'] ?? 'activo';
        if (!is_string($status)) {
            throw new InvalidArgumentException('El estado de la ruta no es válido.');
        }
        $this->estado_valido($status);
        return ['codigo' => $codigo, 'descripcion' => $descripcion, 'sector' => $sector, 'adm_usuario_id' => $operador, 'status' => $status];
    }

    private function estado_valido(string $status): void
    {
        if (!in_array($status, ['activo', 'inactivo'], true)) {
            throw new InvalidArgumentException('El estado de la ruta debe ser activo o inactivo.');
        }
    }

    private function texto_ruta(mixed $valor, string $nombre, int $maximo, bool $obligatorio): ?string
    {
        if ($valor === null && !$obligatorio) {
            return null;
        }
        if (!is_string($valor) || !mb_check_encoding($valor, 'UTF-8')) {
            throw new InvalidArgumentException($nombre.' no es válido.');
        }
        $valor = trim($valor);
        if (($obligatorio && $valor === '') || mb_strlen($valor, 'UTF-8') > $maximo || preg_match('/[\x00-\x1F\x7F]/u', $valor)) {
            throw new InvalidArgumentException($nombre.' es obligatorio, no admite caracteres de control y debe tener como máximo '.$maximo.' caracteres.');
        }
        return $valor === '' && !$obligatorio ? null : $valor;
    }

    private function filtros_ruta(array $filtros): array
    {
        if (array_diff(array_keys($filtros), self::CAMPOS)) {
            throw new InvalidArgumentException('El filtro de rutas no es válido.');
        }
        $where = [];
        $params = [];
        foreach (['codigo' => 32, 'descripcion' => 150, 'sector' => 100] as $campo => $maximo) {
            $valor = $this->texto_ruta($filtros[$campo] ?? null, 'El filtro '.$campo, $maximo, false);
            if ($valor !== null) {
                $where[] = 'r.'.$campo.' LIKE ? ESCAPE \'!\'';
                $params[] = '%'.strtr($valor, ['!' => '!!', '%' => '!%', '_' => '!_']).'%';
            }
        }
        $status = $filtros['status'] ?? '';
        if ($status !== '' && $status !== null) {
            if (!is_string($status)) {
                throw new InvalidArgumentException('El filtro de estado no es válido.');
            }
            $this->estado_valido($status);
            $where[] = 'r.status=?';
            $params[] = $status;
        }
        $operador = $filtros['adm_usuario_id'] ?? '';
        if ($operador !== '' && $operador !== null) {
            if ((!is_string($operador) && !is_int($operador)) || !preg_match('/^[1-9][0-9]*$/D', (string)$operador)
                || filter_var($operador, FILTER_VALIDATE_INT) === false) {
                throw new InvalidArgumentException('El filtro de operador no es válido.');
            }
            $where[] = 'r.adm_usuario_id=?';
            $params[] = (int)$operador;
        }
        return [$where ? ' WHERE '.implode(' AND ', $where) : '', $params];
    }

    private function consulta_ruta(string $sql, array $params = []): \PDOStatement
    {
        try {
            $q = $this->link->prepare($sql);
            if (!$q || !$q->execute($params)) {
                throw new RuntimeException('No se pudo completar la operación de rutas.');
            }
            return $q;
        } catch (PDOException $error) {
            $codigo = (int)($error->errorInfo[1] ?? 0);
            if ($codigo === 1062) {
                throw new InvalidArgumentException('Ya existe una ruta con ese código.');
            }
            if ($codigo === 1451) {
                throw new RuntimeException('La ruta tiene registros relacionados y no se puede eliminar.', 409);
            }
            if ($codigo === 1452) {
                throw new InvalidArgumentException('El operador seleccionado ya no existe. Actualiza el formulario.');
            }
            throw new RuntimeException('No se pudo completar la operación de rutas. Revisa la conexión y vuelve a intentarlo.');
        }
    }
}
