<?php
declare(strict_types=1);

namespace gamboamartin\facturacion\models;

use DateTimeImmutable;
use InvalidArgumentException;
use PDO;

/** Una sola definición de pendientes y filtros para consultar y congelar la selección. */
final class _timbrado_pendientes
{
    public const MAXIMO = 100;
    public const POR_PAGINA = 25;

    public function __construct(private readonly PDO $link) {}

    public static function filtros(array $entrada): array
    {
        $permitidos = ['fecha_inicio', 'fecha_fin', 'serie', 'folio', 'rfc', 'cliente', 'emisor_id', 'total_min', 'total_max'];
        if (array_diff(array_keys($entrada), $permitidos)) {
            throw new InvalidArgumentException('El filtro contiene campos no permitidos.');
        }
        $salida = [];
        foreach ($permitidos as $campo) {
            $valor = $entrada[$campo] ?? '';
            $limite = $campo === 'cliente' ? 1024 : 160;
            if (!is_scalar($valor) || strlen((string)$valor) > $limite
                || ($campo === 'cliente' && mb_strlen((string)$valor, 'UTF-8') > 255)
                || preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/', (string)$valor)) {
                throw new InvalidArgumentException('El filtro no tiene un formato válido.');
            }
            $salida[$campo] = trim((string)$valor);
        }
        foreach (['fecha_inicio', 'fecha_fin'] as $campo) {
            $valor = $salida[$campo];
            $fecha = $valor === '' ? false : DateTimeImmutable::createFromFormat('!Y-m-d', $valor);
            if ($valor !== '' && (!$fecha || $fecha->format('Y-m-d') !== $valor)) {
                throw new InvalidArgumentException('Indica fechas válidas.');
            }
        }
        if ($salida['fecha_inicio'] !== '' && $salida['fecha_fin'] !== '' && $salida['fecha_inicio'] > $salida['fecha_fin']) {
            throw new InvalidArgumentException('La fecha inicial no puede ser posterior a la final.');
        }
        if ($salida['emisor_id'] !== '' && !preg_match('/^[1-9][0-9]{0,17}$/D', $salida['emisor_id'])) {
            throw new InvalidArgumentException('Selecciona un emisor válido.');
        }
        foreach (['total_min', 'total_max'] as $campo) {
            if ($salida[$campo] !== '' && !preg_match('/^[0-9]{1,12}(?:\.[0-9]{1,2})?$/D', $salida[$campo])) {
                throw new InvalidArgumentException('Indica importes positivos con hasta dos decimales.');
            }
        }
        if ($salida['total_min'] !== '' && $salida['total_max'] !== '' && (float)$salida['total_min'] > (float)$salida['total_max']) {
            throw new InvalidArgumentException('El importe mínimo no puede superar al máximo.');
        }
        return $salida;
    }

    /** Retiene también las restricciones históricas: una etapa vieja nunca autoriza retimbrar. */
    private function consulta(array $filtros): array
    {
        $where = ["f.status = 'activo'", "COALESCE(f.es_plantilla,'inactivo') <> 'activo'",
            "UPPER(TRIM(f.etapa)) NOT IN ('TIMBRADO','CANCELADO')",
            "UPPER(TRIM(COALESCE(f.folio_fiscal,''))) IN ('','SIN UUID')",
            "NOT EXISTS (SELECT 1 FROM fc_cfdi_sellado s WHERE s.fc_factura_id=f.id)",
            "NOT EXISTS (SELECT 1 FROM fc_timbrado_intento i WHERE i.factura_id=f.id
                AND i.estado IN ('enviado','aceptado','completo','revision_requerida'))",
            "NOT EXISTS (SELECT 1 FROM fc_factura_etapa e
                INNER JOIN pr_etapa_proceso ep ON ep.id=e.pr_etapa_proceso_id
                INNER JOIN pr_etapa pe ON pe.id=ep.pr_etapa_id
                WHERE e.fc_factura_id=f.id AND UPPER(TRIM(pe.descripcion)) IN ('TIMBRADO','CANCELADO'))"];
        $params = [];
        foreach (['fecha_inicio' => '>=', 'fecha_fin' => '<='] as $campo => $operador) {
            if ($filtros[$campo] !== '') {
                $where[] = 'LEFT(f.fecha,10) '.$operador.' ?';
                $params[] = $filtros[$campo];
            }
        }
        foreach (['serie' => 'f.serie', 'folio' => 'f.folio', 'rfc' => 'c.rfc', 'cliente' => 'c.razon_social'] as $campo => $columna) {
            if ($filtros[$campo] !== '') {
                $where[] = $columna." LIKE ? ESCAPE '!'";
                $params[] = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $filtros[$campo]).'%';
            }
        }
        if ($filtros['emisor_id'] !== '') {
            $where[] = 'empresa.id = ?';
            $params[] = (int)$filtros['emisor_id'];
        }
        foreach (['total_min' => '>=', 'total_max' => '<='] as $campo => $operador) {
            if ($filtros[$campo] !== '') {
                $where[] = 'f.total '.$operador.' ?';
                $params[] = $filtros[$campo];
            }
        }
        $from = ' FROM fc_factura f INNER JOIN com_sucursal suc ON suc.id=f.com_sucursal_id
            INNER JOIN com_cliente c ON c.id=suc.com_cliente_id
            INNER JOIN fc_csd csd ON csd.id=f.fc_csd_id
            INNER JOIN org_sucursal os ON os.id=csd.org_sucursal_id
            INNER JOIN org_empresa empresa ON empresa.id=os.org_empresa_id
            INNER JOIN cat_sat_moneda moneda ON moneda.id=f.cat_sat_moneda_id
            WHERE '.implode(' AND ', $where);
        return [$from, $params];
    }

    public function emisores(): array
    {
        return $this->link->query("SELECT id,razon_social AS nombre FROM org_empresa WHERE status='activo' ORDER BY razon_social,id")
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listar(array $entrada, int $pagina = 1): array
    {
        $filtros = self::filtros($entrada);
        [$from, $params] = $this->consulta($filtros);
        $q = $this->link->prepare('SELECT COUNT(*)'.$from);
        $q->execute($params);
        $total = (int)$q->fetchColumn();
        $paginas = max(1, (int)ceil($total / self::POR_PAGINA));
        $pagina = max(1, min($pagina, $paginas));
        $seleccion = $total <= self::MAXIMO ? array_column($this->filas($from, $params, self::MAXIMO + 1), 'id') : [];
        return ['filas' => $this->filas($from, $params, self::POR_PAGINA, ($pagina - 1) * self::POR_PAGINA),
            'total' => $total, 'pagina' => $pagina, 'paginas' => $paginas, 'maximo' => self::MAXIMO,
            'seleccion_ids' => count($seleccion) === $total ? $seleccion : []];
    }

    private function filas(string $from, array $params, int $limite, int $offset = 0): array
    {
        $q = $this->link->prepare('SELECT f.id,f.folio,f.serie,c.razon_social AS cliente,c.rfc,
            empresa.razon_social AS emisor,empresa.id AS empresa_id,f.fecha,f.total,moneda.codigo AS moneda,f.etapa'
            .$from.' ORDER BY f.fecha,f.id LIMIT '.$limite.' OFFSET '.$offset);
        $q->execute($params);
        $filas = $q->fetchAll(PDO::FETCH_ASSOC);
        foreach ($filas as &$fila) {
            $fila['id'] = (int)$fila['id'];
            $fila['empresa_id'] = (int)$fila['empresa_id'];
        }
        return $filas;
    }

    public function seleccionar(array $entrada, string $modo, array $ids): array
    {
        [$from, $params] = $this->consulta(self::filtros($entrada));
        if ($modo === 'ids') {
            if (!$ids || count($ids) > self::MAXIMO) {
                throw new InvalidArgumentException('Selecciona entre 1 y 100 facturas.');
            }
            foreach ($ids as $id) {
                if ((!is_int($id) && !is_string($id)) || !preg_match('/^[1-9][0-9]{0,17}$/D', (string)$id)) {
                    throw new InvalidArgumentException('La selección contiene identificadores inválidos.');
                }
            }
            $ids = array_values(array_unique(array_map('intval', $ids)));
            $from .= ' AND f.id IN ('.implode(',', array_fill(0, count($ids), '?')).')';
            $params = [...$params, ...$ids];
        } elseif ($modo !== 'todas') {
            throw new InvalidArgumentException('El modo de selección no es válido.');
        }
        $filas = $this->filas($from, $params, self::MAXIMO + 1);
        if (!$filas) {
            throw new InvalidArgumentException('No hay facturas pendientes que cumplan esa selección.');
        }
        if (count($filas) > self::MAXIMO) {
            throw new InvalidArgumentException('El filtro devuelve más de 100 facturas. Ajusta los filtros antes de seleccionar todas.');
        }
        if ($modo === 'ids' && count($filas) !== count($ids)) {
            throw new InvalidArgumentException('La selección cambió o incluye facturas fuera del filtro. Actualiza la lista.');
        }
        return $filas;
    }
}
