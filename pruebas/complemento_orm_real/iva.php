<?php
declare(strict_types=1);

use gamboamartin\facturacion\models\_complemento_desde_factura;

/** Factura ficticia de 100 + IVA 16; pago de la mitad, con traslado proporcional. */
function probar_iva_orm(PDO $link, Closure $insertar): void
{
    $link->exec("UPDATE cat_sat_tipo_impuesto SET codigo='002',descripcion='IVA' WHERE id=1");
    $link->exec("UPDATE cat_sat_tipo_factor SET codigo='Tasa',descripcion='Tasa' WHERE id=1");
    $link->exec("UPDATE cat_sat_factor SET factor=0.16 WHERE id=1");
    $insertar('cat_sat_obj_imp', ['id'=>2, 'codigo'=>'02', 'codigo_bis'=>'02',
        'alias'=>'02', 'descripcion_select'=>'02']);
    $insertar('com_producto', ['id'=>2, 'codigo'=>'QA-PRODUCTO-IVA', 'codigo_bis'=>'QA-PRODUCTO-IVA',
        'alias'=>'QA-PRODUCTO-IVA', 'descripcion_select'=>'QA-PRODUCTO-IVA',
        'cat_sat_obj_imp_id'=>2, 'precio'=>'100.00']);
    $insertar('fc_factura', ['id'=>2, 'codigo'=>'QA-FACTURA-IVA', 'codigo_bis'=>'QA-FACTURA-IVA',
        'alias'=>'QA-FACTURA-IVA', 'descripcion_select'=>'QA-FACTURA-IVA',
        'folio'=>'QA-000002', 'serie'=>'QA', 'etapa'=>'TIMBRADO', 'version'=>'4.0',
        'folio_fiscal'=>'00000000-0000-4000-8000-000000000002',
        'total'=>'116.00', 'sub_total'=>'100.00', 'sub_total_base'=>'100.00',
        'total_traslados'=>'16.00', 'saldo'=>'116.00', 'aplica_saldo'=>'activo', 'es_plantilla'=>'inactivo']);
    $insertar('fc_partida', ['id'=>2, 'codigo'=>'QA-PARTIDA-IVA', 'codigo_bis'=>'QA-PARTIDA-IVA',
        'alias'=>'QA-PARTIDA-IVA', 'descripcion_select'=>'QA-PARTIDA-IVA',
        'fc_factura_id'=>2, 'com_producto_id'=>2, 'cantidad'=>'1.00', 'valor_unitario'=>'100.00',
        'sub_total_base'=>'100.00', 'sub_total'=>'100.00', 'total_traslados'=>'16.00', 'total'=>'116.00']);
    $insertar('fc_cfdi_sellado', ['id'=>2, 'fc_factura_id'=>2, 'codigo'=>'QA-SELLO-IVA',
        'codigo_bis'=>'QA-SELLO-IVA', 'alias'=>'QA-SELLO-IVA', 'descripcion_select'=>'QA-SELLO-IVA',
        'uuid'=>'00000000-0000-4000-8000-000000000002']);
    $insertar('fc_traslado', ['fc_partida_id'=>2, 'total'=>'16.00']);

    $servicio = new _complemento_desde_factura($link, 1, static fn(): bool => true);
    $respuesta = $servicio->crear(2, ['clave_solicitud'=>str_repeat('d', 32), 'monto'=>'58.00',
        'fecha_pago'=>'2026-11-24T12:00', 'fc_csd_id'=>1, 'cat_sat_forma_pago_id'=>2, 'com_tipo_cambio_id'=>1]);
    exigir_orm($respuesta['reutilizado'] === false, 'alta_iva_real');
    $doc = $link->query('SELECT id,imp_pagado,imp_saldo_insoluto,fc_pago_pago_id FROM fc_docto_relacionado WHERE fc_factura_id=2')->fetch(PDO::FETCH_ASSOC);
    exigir_orm($doc !== false && (float)$doc['imp_pagado'] === 58.0
        && (float)$doc['imp_saldo_insoluto'] === 58.0, 'saldo_con_iva');
    $traslado = $link->query('SELECT base_dr,importe_dr FROM fc_traslado_dr_part p JOIN fc_traslado_dr t ON t.id=p.fc_traslado_dr_id JOIN fc_impuesto_dr i ON i.id=t.fc_impuesto_dr_id WHERE i.fc_docto_relacionado_id='.(int)$doc['id'])->fetchAll(PDO::FETCH_ASSOC);
    exigir_orm(count($traslado) === 1 && (float)$traslado[0]['base_dr'] === 50.0
        && (float)$traslado[0]['importe_dr'] === 8.0, 'traslado_documento_proporcional');
    $totales = $link->query('SELECT t.* FROM fc_pago_total t JOIN fc_pago p ON p.id=t.fc_pago_id WHERE p.fc_complemento_pago_id='.(int)$respuesta['fc_complemento_pago_id'])->fetch(PDO::FETCH_ASSOC);
    exigir_orm((float)$totales['monto_total_pagos'] === 58.0, 'monto_total_pago_iva');
    exigir_orm((float)$totales['total_traslados_base_iva_16'] === 50.0
        && (float)$totales['total_traslados_impuesto_iva_16'] === 8.0, 'totales_iva_16');
}
