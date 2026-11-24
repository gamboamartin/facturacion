<?php
declare(strict_types=1);

namespace gamboamartin\facturacion\models;

use Closure;
use DateTimeImmutable;
use gamboamartin\errores\errores;
use InvalidArgumentException;
use PDO;
use RuntimeException;
use Throwable;

/** Prepara en lectura y crea un complemento pendiente, sin emitir documentos. */
final class _complemento_desde_factura
{
    private Closure $permiso;
    private ?Closure $crearEstructura;

    public function __construct(private readonly PDO $link, private readonly int $usuarioId,
        callable $permiso, ?callable $crearEstructura = null)
    {
        $this->permiso = Closure::fromCallable($permiso);
        $this->crearEstructura = $crearEstructura === null ? null : Closure::fromCallable($crearEstructura);
    }

    public function preparar(int $facturaId): array
    {
        $this->autorizar($facturaId);
        $factura = $this->factura($facturaId);
        $saldo = $this->saldos($facturaId, $factura);
        $this->exigir($saldo['saldo'] > 0, 'La factura ya no tiene saldo pendiente.');
        $csds = $this->csds($factura);
        $this->exigir($csds !== [], 'No hay un CSD activo del emisor para crear el complemento.');
        $tipos = $this->tiposCambio($factura);
        $csdId = in_array((int)$factura['fc_csd_id'], array_column($csds, 'id'), true)
            ? (int)$factura['fc_csd_id'] : (count($csds) === 1 ? $csds[0]['id'] : '');
        $hoy = array_values(array_filter($tipos, static fn(array $t): bool =>
            $t['fecha'] === date('Y-m-d') && $t['moneda_id'] === (int)$factura['cat_sat_moneda_id']));
        return ['factura'=>['id'=>$facturaId, 'folio'=>$this->folio($factura), 'uuid'=>$factura['uuid'],
            'emisor'=>$factura['emisor'], 'cliente'=>$factura['cliente'], 'moneda'=>$factura['moneda'],
            'saldo'=>$this->decimal($saldo['saldo']), 'parcialidad'=>$saldo['parcialidades'] + 1],
            'valores'=>['monto'=>$this->decimal($saldo['saldo']), 'fecha_pago'=>'', 'cat_sat_forma_pago_id'=>'',
                'com_tipo_cambio_id'=>count($hoy) === 1 ? $hoy[0]['id'] : '', 'fc_csd_id'=>$csdId],
            'formas_pago'=>$this->filas("SELECT id,codigo,descripcion FROM cat_sat_forma_pago WHERE status='activo' AND codigo<>'99' ORDER BY codigo"),
            'tipos_cambio'=>$tipos, 'csds'=>$csds];
    }

    public function crear(int $facturaId, array $datos): array
    {
        $this->autorizar($facturaId);
        $datos = $this->validarDatos($datos);
        $this->exigir(!$this->link->inTransaction(), 'La creación requiere una conexión sin otra transacción abierta.');
        $autocommit = $this->link->query('SELECT @@autocommit');
        $this->exigir($autocommit !== false && (int)$autocommit->fetchColumn() === 1,
            'La creación requiere una conexión con autocommit habilitado.');
        $codigo = 'CPF-'.hash('sha256', $this->usuarioId.':'.$facturaId.':'.$datos['clave_solicitud']);
        $huella = 'CPF-'.hash('sha256', $codigo.json_encode($datos, JSON_THROW_ON_ERROR));
        $base = $this->link->query('SELECT DATABASE()')->fetchColumn();
        $this->exigir(is_string($base) && $base !== '', 'No se pudo identificar la base de facturación.');
        $lock = 'cp_factura_'.substr(hash('sha256', $base.':'.$facturaId), 0, 50);
        $q = $this->link->prepare('SELECT GET_LOCK(?,0)');
        $this->exigir($q && $q->execute([$lock]) && (int)$q->fetchColumn() === 1,
            'La factura está en uso. Espera a que termine la operación y vuelve a intentar.');
        try {
            $existente = $this->existente($codigo, $huella);
            if ($existente !== null) { return ['fc_complemento_pago_id'=>$existente, 'reutilizado'=>true]; }
            return (new bloqueo_uso_csd($this->link))->ejecutar($datos['fc_csd_id'], function () use ($facturaId, $datos, $codigo, $huella): array {
                $this->link->beginTransaction();
                try {
                    $factura = $this->factura($facturaId, true);
                    $saldo = $this->saldos($facturaId, $factura, true);
                    $this->exigir($saldo['saldo'] > 0, 'La factura ya no tiene saldo pendiente.');
                    $csds = $this->csds($factura, true);
                    $this->exigir(in_array($datos['fc_csd_id'], array_column($csds, 'id'), true),
                        'Selecciona un CSD activo de la misma empresa y RFC emisor de la factura.');
                    $formas = $this->filas("SELECT id,codigo FROM cat_sat_forma_pago WHERE id=? AND status='activo' AND codigo<>'99' FOR UPDATE", [$datos['cat_sat_forma_pago_id']]);
                    $this->exigir(count($formas) === 1, 'Selecciona la forma real de pago; 99 no corresponde al pago recibido.');
                    $tipos = $this->tiposCambio($factura, true);
                    $tipo = array_values(array_filter($tipos, static fn(array $t): bool => $t['id'] === $datos['com_tipo_cambio_id']));
                    $this->exigir(count($tipo) === 1, 'El tipo de cambio no está activo o ese par de monedas no está soportado.');
                    $tipo = $tipo[0];
                    $pagado = $this->montoFactura($datos['monto'], $factura, $tipo);
                    $this->exigir($pagado > 0 && $pagado <= $saldo['saldo'], 'El importe debe ser positivo y no superar el saldo pendiente de la factura.');
                    $cabecera = $this->cabecera($factura, $datos['fc_csd_id'], $codigo, $huella);
                    $pago = ['fecha_pago'=>$datos['fecha_pago'], 'cat_sat_forma_pago_id'=>$datos['cat_sat_forma_pago_id'],
                        'cat_sat_moneda_id'=>$tipo['moneda_id'], 'com_tipo_cambio_id'=>$tipo['id'], 'monto'=>$datos['monto']];
                    // El modelo existente recibe el importe en moneda de pago y calcula su equivalente en la factura.
                    $docto = ['fc_factura_id'=>$facturaId, 'imp_pagado'=>$datos['monto']];
                    $this->autorizar($facturaId);
                    $this->actualizarSaldo($facturaId, $saldo);
                    $id = $this->crearEstructura !== null
                        ? ($this->crearEstructura)($cabecera, $pago, $docto)
                        : $this->crearModelos($cabecera, $pago, $docto);
                    $this->exigir(is_int($id) && $id > 0 && $this->existente($codigo, $huella) === $id,
                        'No se confirmó la creación completa del complemento.');
                    $this->link->commit();
                    return ['fc_complemento_pago_id'=>$id, 'reutilizado'=>false];
                } catch (Throwable $error) {
                    if ($this->link->inTransaction()) { $this->link->rollBack(); }
                    throw $error;
                }
            });
        } finally {
            $q = $this->link->prepare('SELECT RELEASE_LOCK(?)');
            $this->exigir($q && $q->execute([$lock]) && (int)$q->fetchColumn() === 1,
                'No se confirmó el cierre de la operación. Reenvía la misma solicitud para consultar su resultado.');
        }
    }

    private function autorizar(int $facturaId): void
    {
        $this->exigir($this->usuarioId > 0 && ($this->permiso)() === true, 'No tienes permiso para crear complementos de pago.');
        $this->exigir($facturaId > 0, 'Selecciona una factura válida.');
    }

    private function validarDatos(array $datos): array
    {
        $out = [];
        foreach (['clave_solicitud','monto','fecha_pago'] as $key) {
            $this->exigir(isset($datos[$key]) && is_string($datos[$key]), 'Completa los datos del pago.');
            $this->exigir(preg_match('/[\x00-\x1F\x7F]/', $datos[$key]) === 0, 'Los datos del pago contienen caracteres no válidos.');
            $out[$key] = trim($datos[$key]);
        }
        $this->exigir(preg_match('/^[a-f0-9]{32}$/D', $out['clave_solicitud']) === 1, 'La solicitud del formulario no es válida.');
        $this->exigir(preg_match('/^\d{1,12}(?:\.\d{1,2})?$/D', $out['monto']) === 1 && $this->centavos($out['monto']) > 0,
            'Indica un importe positivo con hasta dos decimales.');
        $out['monto'] = $this->decimal($this->centavos($out['monto']));
        $fecha = str_replace('T', ' ', $out['fecha_pago']);
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/D', $fecha)) { $fecha .= ':00'; }
        $this->exigir(preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/D', $fecha) === 1, 'Indica la fecha y hora reales del pago.');
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $fecha);
        $this->exigir($parsed !== false && $parsed->format('Y-m-d H:i:s') === $fecha, 'La fecha del pago no es válida.');
        $out['fecha_pago'] = $fecha;
        foreach (['fc_csd_id','cat_sat_forma_pago_id','com_tipo_cambio_id'] as $key) {
            $value = $datos[$key] ?? null;
            $this->exigir((is_int($value) || is_string($value)) && preg_match('/^[1-9]\d{0,9}$/D', (string)$value) === 1,
                'Selecciona el CSD, la forma de pago y el tipo de cambio.');
            $out[$key] = (int)$value;
        }
        return $out;
    }

    private function factura(int $id, bool $bloquear = false): array
    {
        $rows = $this->filas("SELECT f.id,f.folio,f.serie,f.folio_fiscal,f.etapa,f.status,f.fc_csd_id,f.com_sucursal_id,
            f.cat_sat_moneda_id,f.com_tipo_cambio_id,f.total,f.saldo,mp.codigo AS metodo,m.codigo AS moneda,
            tc.monto AS tipo_cambio_factura,os.org_empresa_id,e.rfc AS emisor_rfc,e.razon_social AS emisor,
            cs.com_cliente_id,cl.rfc AS cliente_rfc,cl.razon_social AS cliente
            FROM fc_factura f INNER JOIN fc_csd c ON c.id=f.fc_csd_id INNER JOIN org_sucursal os ON os.id=c.org_sucursal_id
            INNER JOIN org_empresa e ON e.id=os.org_empresa_id INNER JOIN com_sucursal cs ON cs.id=f.com_sucursal_id
            INNER JOIN com_cliente cl ON cl.id=cs.com_cliente_id INNER JOIN cat_sat_metodo_pago mp ON mp.id=f.cat_sat_metodo_pago_id
            INNER JOIN cat_sat_moneda m ON m.id=f.cat_sat_moneda_id INNER JOIN com_tipo_cambio tc ON tc.id=f.com_tipo_cambio_id
            WHERE f.id=?".($bloquear ? ' FOR UPDATE' : ''), [$id]);
        $this->exigir(count($rows) === 1, 'No se encontró la factura seleccionada.');
        $f = $rows[0];
        $this->exigir($f['status'] === 'activo' && $f['etapa'] === 'TIMBRADO' && $f['metodo'] === 'PPD',
            'El complemento requiere una factura activa, timbrada y con método PPD.');
        $historial = $this->filas('SELECT e.descripcion FROM fc_factura_etapa fe
            INNER JOIN pr_etapa_proceso ep ON ep.id=fe.pr_etapa_proceso_id INNER JOIN pr_etapa e ON e.id=ep.pr_etapa_id
            WHERE fe.fc_factura_id=?'.($bloquear ? ' FOR UPDATE' : ''), [$id]);
        foreach ($historial as $etapa) {
            $this->exigir(strtoupper(trim($etapa['descripcion'])) !== 'CANCELADO', 'La factura tiene una cancelación registrada.');
        }
        $sellos = $this->filas('SELECT uuid FROM fc_cfdi_sellado WHERE fc_factura_id=?'.($bloquear ? ' FOR UPDATE' : ''), [$id]);
        $this->exigir(count($sellos) === 1 && is_string($sellos[0]['uuid'])
            && preg_match('/^[a-f0-9]{8}(?:-[a-f0-9]{4}){3}-[a-f0-9]{12}$/iD', $sellos[0]['uuid']) === 1
            && strcasecmp(trim((string)$f['folio_fiscal']), $sellos[0]['uuid']) === 0,
            'La factura no tiene un UUID sellado válido y consistente.');
        $f['uuid'] = strtoupper($sellos[0]['uuid']);
        return $f;
    }

    private function saldos(int $id, array $factura, bool $bloquear = false): array
    {
        $suffix = $bloquear ? ' FOR UPDATE' : '';
        $docs = $this->filas('SELECT dr.imp_pagado,dr.status,cp.status AS cp_status,cp.aplica_saldo FROM fc_docto_relacionado dr
            INNER JOIN fc_pago_pago pp ON pp.id=dr.fc_pago_pago_id INNER JOIN fc_pago p ON p.id=pp.fc_pago_id
            INNER JOIN fc_complemento_pago cp ON cp.id=p.fc_complemento_pago_id WHERE dr.fc_factura_id=?'.$suffix, [$id]);
        $notas = $this->filas("SELECT r.monto_aplicado_factura FROM fc_nc_rel r
            INNER JOIN fc_relacion_nc rn ON rn.id=r.fc_relacion_nc_id INNER JOIN fc_nota_credito n ON n.id=rn.fc_nota_credito_id
            WHERE r.fc_factura_id=? AND n.aplica_saldo='activo'".$suffix, [$id]);
        $cp = $nc = $parcialidades = 0;
        foreach ($docs as $doc) {
            if ($doc['aplica_saldo'] === 'activo') { $cp += $this->centavos((string)$doc['imp_pagado']); }
            if ($doc['status'] === 'activo' && $doc['cp_status'] === 'activo') { $parcialidades++; }
        }
        foreach ($notas as $nota) { $nc += $this->centavos((string)$nota['monto_aplicado_factura']); }
        return ['saldo'=>$this->centavos((string)$factura['total']) - $cp - $nc, 'cp'=>$cp, 'nc'=>$nc, 'parcialidades'=>$parcialidades];
    }

    private function actualizarSaldo(int $id, array $saldo): void
    {
        $q = $this->link->prepare('UPDATE fc_factura SET saldo=?,monto_pago_cp=?,monto_pago_nc=?,monto_saldo_aplicado=? WHERE id=?');
        $this->exigir($q && $q->execute([$this->decimal($saldo['saldo']), $this->decimal($saldo['cp']),
            $this->decimal($saldo['nc']), $this->decimal($saldo['cp'] + $saldo['nc']), $id]), 'No se pudo actualizar el saldo de referencia.');
    }

    private function csds(array $factura, bool $bloquear = false): array
    {
        $rows = $this->filas("SELECT c.id,c.descripcion_select AS descripcion,e.rfc FROM fc_csd c
            INNER JOIN org_sucursal s ON s.id=c.org_sucursal_id INNER JOIN org_empresa e ON e.id=s.org_empresa_id
            WHERE c.status='activo' AND e.id=? ORDER BY c.id".($bloquear ? ' FOR UPDATE' : ''), [$factura['org_empresa_id']]);
        $out = [];
        foreach ($rows as $row) {
            if (strcasecmp(trim($row['rfc']), trim($factura['emisor_rfc'])) === 0) {
                $out[] = ['id'=>(int)$row['id'], 'descripcion'=>$row['descripcion']];
            }
        }
        return $out;
    }

    private function tiposCambio(array $factura, bool $bloquear = false): array
    {
        $rows = $this->filas("SELECT tc.id,tc.cat_sat_moneda_id AS moneda_id,m.codigo AS moneda,tc.fecha,tc.monto
            FROM com_tipo_cambio tc INNER JOIN cat_sat_moneda m ON m.id=tc.cat_sat_moneda_id
            WHERE tc.status='activo' AND m.status='activo' AND m.codigo<>'XXX'
              AND (m.id=? OR m.codigo='MXN' OR ?='MXN') ORDER BY tc.fecha DESC,tc.id DESC".($bloquear ? ' FOR UPDATE' : ''),
            [$factura['cat_sat_moneda_id'], $factura['moneda']]);
        $out = [];
        foreach ($rows as $row) {
            if (is_numeric($row['monto']) && is_finite((float)$row['monto']) && (float)$row['monto'] > 0) {
                $row['id'] = (int)$row['id']; $row['moneda_id'] = (int)$row['moneda_id']; $out[] = $row;
            }
        }
        return $out;
    }

    private function montoFactura(string $monto, array $factura, array $tipo): int
    {
        if ((int)$factura['cat_sat_moneda_id'] === $tipo['moneda_id']) { return $this->centavos($monto); }
        $this->exigir($factura['moneda'] === 'MXN' || $tipo['moneda'] === 'MXN', 'El pago entre dos divisas distintas de MXN no está soportado en este flujo.');
        $this->exigir((float)$factura['tipo_cambio_factura'] > 0, 'La factura no tiene un tipo de cambio válido.');
        $valor = $factura['moneda'] === 'MXN' ? (float)$monto * (float)$tipo['monto'] : (float)$monto / (float)$factura['tipo_cambio_factura'];
        $this->exigir(is_finite($valor) && $valor < 1000000000000, 'El importe convertido está fuera del rango admitido.');
        return $this->centavos(number_format($valor, 2, '.', ''));
    }

    private function cabecera(array $factura, int $csdId, string $codigo, string $huella): array
    {
        $moneda = $this->catalogo('cat_sat_moneda', 'XXX');
        $tc = $this->filas("SELECT id FROM com_tipo_cambio WHERE cat_sat_moneda_id=? AND status='activo' ORDER BY fecha DESC,id DESC LIMIT 1", [$moneda]);
        $this->exigir(count($tc) === 1, 'Falta el tipo de cambio interno para la cabecera del complemento.');
        return ['codigo'=>$codigo, 'codigo_bis'=>$huella, 'fc_csd_id'=>$csdId, 'com_sucursal_id'=>(int)$factura['com_sucursal_id'],
            'cat_sat_forma_pago_id'=>$this->catalogo('cat_sat_forma_pago', '99'),
            // Son referencias internas exigidas por el modelo; el serializador de CP omite Forma/Metodo de la cabecera.
            'cat_sat_metodo_pago_id'=>$this->catalogo('cat_sat_metodo_pago', 'PPD'),
            'cat_sat_moneda_id'=>$moneda, 'com_tipo_cambio_id'=>(int)$tc[0]['id'],
            'cat_sat_uso_cfdi_id'=>$this->catalogo('cat_sat_uso_cfdi', 'CP01'),
            'cat_sat_tipo_de_comprobante_id'=>$this->catalogo('cat_sat_tipo_de_comprobante', 'P'),
            // La cabecera de tipo P conserva importes en cero; el monto real pertenece al pago.
            'total_descuento'=>0, 'sub_total_base'=>0, 'sub_total'=>0,
            'total_traslados'=>0, 'total_retenciones'=>0, 'total'=>0,
            'monto_pago_nc'=>0, 'monto_pago_cp'=>0, 'saldo'=>0, 'monto_saldo_aplicado'=>0,
            'cantidad'=>0, 'valor_unitario'=>0, 'descuento'=>0,
            'exportacion'=>'01', 'version'=>'4.0'];
    }

    private function catalogo(string $tabla, string $codigo): int
    {
        $rows = $this->filas("SELECT id FROM $tabla WHERE codigo=? AND status='activo'", [$codigo]);
        $this->exigir(count($rows) === 1, 'Falta una referencia activa y única del catálogo requerida para el complemento.');
        return (int)$rows[0]['id'];
    }

    private function existente(string $codigo, string $huella): ?int
    {
        $rows = $this->filas('SELECT id,codigo_bis FROM fc_complemento_pago WHERE codigo=?', [$codigo]);
        $this->exigir(count($rows) <= 1, 'La solicitud tiene registros duplicados y requiere revisión.');
        if (!$rows) { return null; }
        $this->exigir(hash_equals($huella, (string)$rows[0]['codigo_bis']), 'La solicitud ya se utilizó con otros datos; abre un formulario nuevo.');
        return (int)$rows[0]['id'];
    }

    private function crearModelos(array $cabecera, array $pago, array $docto): int
    {
        $alta = (new fc_complemento_pago($this->link))->alta_registro($cabecera);
        $this->resultadoModelo($alta);
        $id = (int)$alta->registro_id;
        $pagos = $this->filas('SELECT id FROM fc_pago WHERE fc_complemento_pago_id=?', [$id]);
        $this->exigir(count($pagos) === 1, 'No se confirmó el contenedor de pagos del complemento.');
        $pago['fc_pago_id'] = (int)$pagos[0]['id'];
        $altaPago = (new fc_pago_pago($this->link))->alta_registro($pago);
        $this->resultadoModelo($altaPago);
        $docto['fc_pago_pago_id'] = (int)$altaPago->registro_id;
        $altaDocto = (new fc_docto_relacionado($this->link))->alta_registro($docto);
        $this->resultadoModelo($altaDocto);
        return $id;
    }

    private function resultadoModelo(mixed $resultado): void
    {
        $this->exigir(!errores::$error && is_object($resultado) && (int)($resultado->registro_id ?? 0) > 0,
            'No se pudo guardar el complemento completo. No se conservaron registros parciales.');
    }

    private function filas(string $sql, array $args = []): array
    {
        $q = $this->link->prepare($sql);
        $this->exigir($q !== false && $q->execute($args), 'No se pudo consultar la información de facturación.');
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    private function centavos(string $valor): int
    {
        $this->exigir(preg_match('/^\d{1,12}(?:\.\d{1,8})?$/D', $valor) === 1, 'Hay un importe no válido que requiere revisión.');
        [$entero, $decimales] = array_pad(explode('.', $valor, 2), 2, '');
        $decimales = str_pad($decimales, 3, '0');
        return (int)$entero * 100 + (int)substr($decimales, 0, 2) + ((int)$decimales[2] >= 5 ? 1 : 0);
    }

    private function decimal(int $centavos): string
    {
        return intdiv($centavos, 100).'.'.str_pad((string)($centavos % 100), 2, '0', STR_PAD_LEFT);
    }

    private function folio(array $factura): string
    {
        $serie = trim((string)$factura['serie']); $folio = trim((string)$factura['folio']);
        return $serie !== '' && !str_starts_with($folio, $serie.'-') ? $serie.'-'.$folio : $folio;
    }

    private function exigir(bool $condicion, string $mensaje): void
    {
        if (!$condicion) { throw new InvalidArgumentException($mensaje); }
    }
}
