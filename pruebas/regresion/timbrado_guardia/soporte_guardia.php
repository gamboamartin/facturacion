<?php
declare(strict_types=1);

namespace gamboamartin\errores {
    final class errores
    {
        public static bool $error = false;
        public function error(string $mensaje, mixed $data): array
        {
            self::$error = true;
            return ['error' => true, 'mensaje' => $mensaje, 'data' => $data];
        }
    }
}

namespace Pruebas\TimbradoGuardia {
    use PDO;
    use PDOException;
    use PDOStatement;
    use RuntimeException;

    /** Almacén efímero en memoria: no abre conexiones ni llama PAC. */
    final class Conexion extends PDO
    {
        public array $facturas = [1 => ['id' => 1, 'fc_csd_id' => 7, 'folio_fiscal' => '']];
        public array $sellos = [];
        public array $intentos = [];
        public array $consultas = [];
        public array $liberados = [];
        public bool $transaccion = false;
        public int $auto = 1;
        public bool $esquema = true;
        public bool $factura_ocupada = false;
        public bool $csd_ocupado = false;
        public bool $fallar_envio = false;

        public function __construct() {}
        public function inTransaction(): bool { return $this->transaccion; }
        public function rollBack(): bool { $this->transaccion = false; return true; }
        public function prepare(string $query, array $options = []): PDOStatement|false
        {
            return new Sentencia($this, $query);
        }
        public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): PDOStatement|false
        {
            $s = $this->prepare($query); $s->execute(); return $s;
        }
        public function ejecutar(string $sql, array $valores): array
        {
            $this->consultas[] = $sql;
            if ($sql === 'SELECT DATABASE()') { return [[['base' => 'qa_ficticia']], 0]; }
            if ($sql === 'SELECT @@SESSION.autocommit') { return [[[$this->auto]], 0]; }
            if (str_starts_with($sql, 'SELECT ENGINE FROM')) { return [$this->esquema ? [['ENGINE' => 'InnoDB']] : [], 0]; }
            if (str_contains($sql, 'WHERE 1=0')) { return [[], 0]; }
            if (str_starts_with($sql, 'SELECT GET_LOCK')) {
                $ocupado = str_starts_with($valores[0], 'fc_timbra_') ? $this->factura_ocupada : $this->csd_ocupado;
                return [[[$ocupado ? 0 : 1]], 0];
            }
            if (str_starts_with($sql, 'SELECT RELEASE_LOCK')) { $this->liberados[] = $valores[0]; return [[[1]], 0]; }
            if (str_starts_with($sql, 'SELECT id FROM fc_csd')) { return [$valores[0] === 7 ? [['id' => 7]] : [], 0]; }
            if (str_starts_with($sql, 'SELECT id,fc_csd_id,folio_fiscal FROM')) {
                return [isset($this->facturas[$valores[0]]) ? [$this->facturas[$valores[0]]] : [], 0];
            }
            if (str_starts_with($sql, 'SELECT uuid FROM fc_cfdi_sellado')) {
                return [array_map(static fn ($uuid): array => ['uuid' => $uuid], $this->sellos[$valores[0]] ?? []), 0];
            }
            if (str_starts_with($sql, 'SELECT intento,estado,uuid,codigo,mensaje FROM')) {
                return [isset($this->intentos[$valores[0]]) ? [$this->intentos[$valores[0]]] : [], 0];
            }
            if (str_starts_with($sql, 'INSERT INTO fc_timbrado_intento')) {
                $this->intentos[$valores[0]] = ['intento' => $valores[1], 'estado' => 'preparando', 'uuid' => '',
                    'codigo' => 'PREPARANDO', 'mensaje' => 'Se está preparando el comprobante.', 'xml_respuesta' => null];
                return [[], 1];
            }
            if (str_starts_with($sql, "UPDATE fc_timbrado_intento SET estado = 'enviado'")) {
                if ($this->fallar_envio) { throw new PDOException('SQL ficticio con dato privado que no debe salir'); }
                [$id, $intento] = $valores;
                if (($this->intentos[$id]['intento'] ?? '') !== $intento || $this->intentos[$id]['estado'] !== 'preparando') {
                    return [[], 0];
                }
                $this->intentos[$id]['estado'] = 'enviado';
                return [[], 1];
            }
            if (str_starts_with($sql, 'UPDATE fc_timbrado_intento SET estado=?')) {
                $id = $valores[count($valores) - 2]; $intento = $valores[count($valores) - 1];
                if (($this->intentos[$id]['intento'] ?? '') !== $intento) { return [[], 0]; }
                foreach (['estado', 'uuid', 'codigo', 'mensaje'] as $i => $campo) { $this->intentos[$id][$campo] = $valores[$i]; }
                if (str_contains($sql, ',xml_respuesta=?')) { $this->intentos[$id]['xml_respuesta'] = $valores[4]; }
                return [[], 1];
            }
            throw new RuntimeException('Consulta inesperada en doble de prueba.');
        }
    }

    final class Sentencia extends PDOStatement
    {
        private array $filas = [];
        private int $afectadas = 0;
        private int $posicion = 0;
        public function __construct(private readonly Conexion $conexion, private readonly string $sql) {}
        public function execute(?array $params = null): bool
        {
            [$this->filas, $this->afectadas] = $this->conexion->ejecutar($this->sql, $params ?? []);
            $this->posicion = 0; return true;
        }
        public function fetch(int $mode = PDO::FETCH_DEFAULT, int $cursorOrientation = PDO::FETCH_ORI_NEXT, int $cursorOffset = 0): mixed
        {
            return $this->filas[$this->posicion++] ?? false;
        }
        public function fetchColumn(int $column = 0): mixed
        {
            $fila = $this->fetch(); return $fila === false ? false : (array_values($fila)[$column] ?? false);
        }
        public function fetchAll(int $mode = PDO::FETCH_DEFAULT, mixed ...$args): array
        {
            return $mode === PDO::FETCH_COLUMN ? array_map(static fn ($r): mixed => array_values($r)[0], $this->filas) : $this->filas;
        }
        public function rowCount(): int { return $this->afectadas; }
    }
}
