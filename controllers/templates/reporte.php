<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            border: 0;
            font: inherit;
            vertical-align: baseline;
        }

        html, body {
            margin: 0;
            padding: 0 30px 20px 30px;
            height: 100%;
            line-height: 1;
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #333;
        }

        table {
            border-collapse: collapse;
            border-spacing: 0;
        }

        .conteo {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            font-family: sans-serif;
            font-size: 12px;
            text-align: right;
        }

        .conteo p {
            font-weight: bold;
            margin-right: 5px;
            font-size: 15px;
            display: inline-block;
        }

        .conteo .folio {
            display: inline-block;
            background-color: #007b8e;
            color: #ffffff;
            padding: 15px 10px;
            border-radius: 0 0 12px 12px;
            font-weight: bold;
        }


    </style>
</head>
<body>

<div class="conteo">
    <p class="negrita" style="margin-bottom: 10px;">Complemento de Pago No.</p>
    <span class="folio"><?= $datos['folio'] ?></span>
</div>

<div class="section">
    <table style="width: 100%;">
        <tr>
            <td style="width: 10%; padding: 0 5px 0 0; vertical-align: top;">
                <img src="<?= $datos['logo'] ?>" style="width: 140px; height: 110px; margin: 0 auto;">
            </td>
            <td style="width: 50%; padding: 0 5px 0 0; vertical-align: top;">
                <div style="padding: 5px;">
                    <table style="width: 100%;">
                        <tr>
                            <td style="padding: 3px 5px 3px 0; font-weight: bold; width: 40px;">Emisor:</td>
                            <td style="padding: 3px 5px 3px 0;"><?= $datos['emisor']['emisor'] ?></td>
                        </tr>
                        <tr>
                            <td style=" padding: 3px 5px 3px 0; font-weight: bold; width: 40px;">Nombre:</td>
                            <td style="padding: 3px 5px 3px 0;"><?= $datos['emisor']['nombre'] ?></td>
                        </tr>
                        <tr>
                            <td style="padding: 3px 5px 3px 0; font-weight: bold; width: 40px;">RFC:</td>
                            <td style="padding: 3px 5px 3px 0;"><?= $datos['emisor']['rfc'] ?></td>
                        </tr>
                        <tr>
                            <td style="padding: 3px 5px 3px 0; font-weight: bold; width: 40px;">Régimen:</td>
                            <td style="padding: 3px 5px 3px 0;"><?= $datos['emisor']['regimen'] ?></td>
                        </tr>
                        <tr>
                            <td style="padding: 3px 5px 3px 0; font-weight: bold; width: 40px;">Domicilio Fiscal:</td>
                            <td style="padding: 3px 5px 3px 0;"><?= $datos['emisor']['direccion'] ?></td>
                        </tr>
                        <tr>
                            <td style="padding: 3px 5px 3px 0; font-weight: bold; width: 40px;">Teléfono:</td>
                            <td style="padding: 3px 5px 3px 0;"><?= $datos['emisor']['telefono'] ?></td>
                        </tr>
                    </table>
                </div>
            </td>
            <td style="width: 40%; padding: 0 0 0 5px;  vertical-align: top;">
                <div style="padding: 5px;">
                    <table style="width: 100%;">
                        <tr>
                            <td style="padding: 3px 5px 3px 0;">
                                <div style="font-weight: bold;">Fecha y hora de emisión:</div>
                                <div style="padding-top: 3px;"><?= $datos['fechas']['fecha_emision'] ?></div>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 3px 5px 3px 0; ">
                                <div style="font-weight: bold;">Fecha y hora de certificación:</div>
                                <div style="padding-top: 3px;"><?= $datos['fechas']['fecha_certificacion'] ?></div>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 3px 5px 3px 0; ">
                                <div style="font-weight: bold;">Código postal de expedición:</div>
                                <div style="padding-top: 3px;"><?= $datos['fechas']['cp_expedicion'] ?></div>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>
</div>

<div class="section" style="margin: 7px 0 0 0;">
    <table style="width: 100%;">
        <tr>
            <td style="width: 50%; padding: 0 5px 0 0; vertical-align: top;">
                <div style="border: 1px solid #007b8e; border-radius: 3px;">
                    <div style="background-color: #007b8e; color: white; padding: 5px; font-weight: bold;">
                        Receptor
                    </div>
                    <div style="padding: 5px;">
                        <table style="width: 100%;">
                            <tr>
                                <td style="padding: 3px 5px 3px 0; font-weight: bold; width: 90px;">Razón social:</td>
                                <td style="padding: 3px 5px 3px 0;"><?= $datos['receptor']['nombre'] ?></td>
                            </tr>
                            <tr>
                                <td style=" padding: 3px 5px 3px 0; font-weight: bold; width: 90px;">RFC:</td>
                                <td style="padding: 3px 5px 3px 0;"><?= $datos['receptor']['rfc'] ?></td>
                            </tr>
                            <tr>
                                <td style="padding: 3px 5px 3px 0; font-weight: bold; width: 90px;">Uso CFDI:</td>
                                <td style="padding: 3px 5px 3px 0;"><?= $datos['receptor']['uso_cfdi'] ?></td>
                            </tr>
                            <tr>
                                <td style="padding: 3px 5px 3px 0; font-weight: bold; width: 90px; height: 44px;">Domicilio fiscal:</td>
                                <td style="padding: 3px 5px 3px 0; height: 44px;"><?= $datos['receptor']['direccion'] ?></td>
                            </tr>
                            <tr>
                                <td style="padding: 3px 5px 3px 0; font-weight: bold; width: 90px;">Régimen Fiscal:</td>
                                <td style="padding: 3px 5px 3px 0;"><?= $datos['receptor']['regimen'] ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </td>
            <td style="width: 50%; padding: 0 0 0 5px;  vertical-align: top;">
                <div style="border: 1px solid #007b8e; border-radius: 3px;">
                    <div style="background-color: #007b8e; color: white; padding: 5px; font-weight: bold;">
                        Datos fiscales
                    </div>
                    <div style="padding: 5px;">
                        <table style="width: 100%;">
                            <tr>
                                <td style="padding: 3px 5px 3px 0;">
                                    <div style="font-weight: bold;">Folio SAT:</div>
                                    <div><?= $datos['fiscales']['folio_sat'] ?></div>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 3px 5px 3px 0; ">
                                    <div style="font-weight: bold;">Número de serie certificado emisor:</div>
                                    <div><?= $datos['fiscales']['certificado_emisor'] ?></div>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 3px 5px 3px 0; ">
                                    <div style="font-weight: bold;">Número de serie certificado SAT:</div>
                                    <div><?= $datos['fiscales']['certificado_sat'] ?></div>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 3px 5px 3px 0;">
                                    <span style="display: inline-block; width: 67px; font-weight: bold;">Leyenda:</span>
                                    <span><?= $datos['fiscales']['leyenda'] ?></span>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 3px 5px 3px 0;">
                                    <span style="width: 67px; font-weight: bold;">Exportación:</span>
                                    <span><?= $datos['fiscales']['exportacion'] ?></span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </td>
        </tr>
    </table>
</div>

<div class="section" style="margin: 7px 0 0 0;">
    <table style="width: 100%;">
        <thead style="background-color: #007b8e; color: white; font-weight: bold;">
        <tr>
            <th style="padding: 5px;">Cant</th>
            <th style="padding: 5px;">Unidad</th>
            <th style="padding: 5px;">Clave</th>
            <th style="padding: 5px; width: 300px;">Descripción</th>
            <th style="padding: 5px; width: 80px;">Obj impuesto</th>
            <th style="padding: 5px;">Valor Unitario</th>
            <th style="padding: 5px;">Importe</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($datos['productos'] as $p): ?>
            <tr>
                <td style="padding: 5px; text-align: center;"><?= $p['cantidad'] ?></td>
                <td style="padding: 5px; text-align: center;"><?= $p['unidad'] ?></td>
                <td style="padding: 5px; text-align: center;"><?= $p['clave'] ?></td>
                <td style="padding: 5px;"><?= $p['descripcion'] ?></td>
                <td style="padding: 5px; text-align: center;"><?= $p['obj_impuesto'] ?></td>
                <td style="padding: 5px; text-align: center;"><?= $p['valor_unitario'] ?></td>
                <td style="padding: 5px; text-align: center;"><?= $p['importe'] ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="section" style="margin: 7px 0 0 0;">
    <table style="width: 100%;">
        <tr>
            <td style="">
                <div style="border: 1px solid #007b8e; border-radius: 3px;">
                    <div style="background-color: #007b8e; color: white; padding: 5px; font-weight: bold;">
                        Información del Pago
                    </div>
                    <div style="padding: 5px;">
                        <table style="width: 100%;">
                            <tr>
                                <td style="padding: 3px 5px 3px 0; font-weight: bold; width: 90px;">Fecha de Pago:</td>
                                <td style="padding: 3px 5px 3px 0;"><?= $datos['informacion_pago']['fecha_pago'] ?></td>
                                <td style="padding: 3px 5px 3px 0; font-weight: bold; width: 90px;">Tipo de cambio:</td>
                                <td style="padding: 3px 5px 3px 0;"><?= $datos['informacion_pago']['tipo_cambio'] ?></td>
                            </tr>
                            <tr>
                                <td style="padding: 3px 5px 3px 0; font-weight: bold; width: 90px;">Forma de Pago:</td>
                                <td style="padding: 3px 5px 3px 0;"><?= $datos['informacion_pago']['forma_pago'] ?></td>
                                <td style="padding: 3px 5px 3px 0; font-weight: bold; width: 90px;">Monto:</td>
                                <td style="padding: 3px 5px 3px 0;"><?= $datos['informacion_pago']['monto'] ?></td>
                            </tr>
                            <tr>
                                <td style="padding: 3px 5px 3px 0; font-weight: bold; width: 90px;">Moneda:</td>
                                <td style="padding: 3px 5px 3px 0;"><?= $datos['informacion_pago']['moneda'] ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </td>
        </tr>
    </table>
</div>

<div class="section" style="margin: 7px 0 0 0;">
    <table style="width: 100%;">
        <tr>
            <td style="">
                <div style="border: 1px solid #007b8e; border-radius: 3px;">
                    <div style="background-color: #007b8e; color: white; padding: 5px; font-weight: bold;">
                        Información de impuestos
                    </div>
                    <div style="margin-top: 2px;">
                        <table style="width: 100%;">
                            <thead style="color: #007b8e; font-weight: bold;">
                            <tr>
                                <th>Tipo de impuesto</th>
                                <th>Base</th>
                                <th>Impuesto</th>
                                <th>Tipo Factor</th>
                                <th>Tasa o Cuota</th>
                                <th>Importe</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($datos['productos'] as $p): ?>
                                <tr>
                                    <td style="text-align: center"><?= $p['cantidad'] ?></td>
                                    <td style="text-align: center"><?= $p['unidad'] ?></td>
                                    <td style="text-align: center"><?= $p['clave'] ?></td>
                                    <td style="text-align: center"><?= $p['descripcion'] ?></td>
                                    <td style="text-align: center"><?= $p['obj_impuesto'] ?></td>
                                    <td style="text-align: center">$<?= $p['valor_unitario'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </td>
        </tr>
    </table>
</div>

<div class="section" style="margin: 7px 0 0 0;">
    <table style="width: 100%;">
        <thead style="background-color: #007b8e; color: white; font-weight: bold;">
        <tr>
            <th style="padding: 5px;">Folio Fiscal del CFDI relacionado</th>
            <th style="padding: 5px;">Serie</th>
            <th style="padding: 5px;">Folio</th>
            <th style="padding: 5px;">Moneda</th>
            <th style="padding: 5px;">Tipo de cambio o equivalencia</th>
            <th style="padding: 5px;">Número de parcialidad</th>
            <th style="padding: 5px;">Importe de saldo anterior</th>
            <th style="padding: 5px;">Importe pagado</th>
            <th style="padding: 5px;">Saldo Insoluto</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($datos['productos'] as $p): ?>
            <tr>
                <td style="padding: 5px; text-align: center;"><?= $p['cantidad'] ?></td>
                <td style="padding: 5px; text-align: center;"><?= $p['unidad'] ?></td>
                <td style="padding: 5px; text-align: center;"><?= $p['clave'] ?></td>
                <td style="padding: 5px; text-align: center;"><?= $p['clave'] ?></td>
                <td style="padding: 5px; text-align: center;"><?= $p['clave'] ?></td>
                <td style="padding: 5px; text-align: center;"><?= $p['clave'] ?></td>
                <td style="padding: 5px; text-align: center;">$<?= $p['valor_unitario'] ?></td>
                <td style="padding: 5px; text-align: center;">$<?= $p['valor_unitario'] ?></td>
                <td style="padding: 5px; text-align: center;">$<?= $p['valor_unitario'] ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="section" style="margin: 25px 0 0 0;">
    <table style="width: 100%;">
        <tr>
            <td style="width: 70%; padding: 0 5px 0 0; vertical-align: top;">
                <div style="padding: 5px;">
                    <table style="width: 100%;">
                        <tr>
                            <td style="padding: 3px 5px 3px 0; font-weight: bold;">Forma de pago:</td>
                            <td style="padding: 3px 5px 3px 0;"><?= $datos['pagos']['forma_pago'] ?></td>
                        </tr>
                        <tr>
                            <td style=" padding: 3px 5px 3px 0; font-weight: bold;">Método de pago:</td>
                            <td style="padding: 3px 5px 3px 0;"><?= $datos['pagos']['metodo_pago'] ?></td>
                        </tr>
                        <tr>
                            <td style="padding: 3px 5px 3px 0; font-weight: bold;">Tipo de comprobante:</td>
                            <td style="padding: 3px 5px 3px 0;"><?= $datos['pagos']['tipo_comprobante'] ?></td>
                        </tr>
                        <tr>
                            <td style="padding: 3px 5px 3px 0; font-weight: bold; width: 120px;">Condiciones de pago:
                            </td>
                            <td style="padding: 3px 5px 3px 0;"><?= $datos['pagos']['condiciones_pago'] ?></td>
                        </tr>
                        <tr>
                            <td style="padding: 3px 5px 3px 0; font-weight: bold;">Moneda:</td>
                            <td style="padding: 3px 5px 3px 0;"><?= $datos['pagos']['moneda'] ?></td>
                        </tr>
                    </table>
                </div>
            </td>
            <td style="width: 30%; padding: 0 0 0 5px;  vertical-align: top;">
                <div style="border: 1px solid #007b8e; border-radius: 3px; padding: 5px; background-color: #007b8e; color: white">
                    <table style="width: 100%;">
                        <tr>
                            <td style="padding: 4px 5px 4px 0; font-weight: bold; text-align: right; width: 80px;">Subtotal:</td>
                            <td style="padding: 4px 5px 4px 0; text-align: left; font-weight: bold;"> <?= $datos['totales']['subtotal'] ?> </td>
                        </tr>
                        <tr>
                            <td style="padding: 4px 5px 4px 0; font-weight: bold; text-align: right; width: 80px;">Descuento:</td>
                            <td style="padding: 4px 5px 4px 0; text-align: left; font-weight: bold;"> <?= $datos['totales']['descuento'] ?> </td>
                        </tr>
                        <tr>
                            <td style="padding: 4px 5px 4px 0; font-weight: bold; text-align: right; width: 80px;">IVA 16%:</td>
                            <td style="padding: 4px 5px 4px 0; text-align: left; font-weight: bold;"> <?= $datos['totales']['iva'] ?> </td>
                        </tr>
                        <tr>
                            <td style="padding: 4px 5px 4px 0; font-weight: bold; text-align: right; width: 80px;">ISR Retenido:</td>
                            <td style="padding: 4px 5px 4px 0; text-align: left; font-weight: bold;"> <?= $datos['totales']['isr_retenido'] ?> </td>
                        </tr>
                        <tr>
                            <td style="padding: 4px 5px 4px 0; font-weight: bold; text-align: right; width: 80px;">IVARetenido:</td>
                            <td style="padding: 4px 5px 4px 0; text-align: left; font-weight: bold;"> <?= $datos['totales']['iva_retenido'] ?> </td>
                        </tr>
                        <tr>
                            <td style="padding: 4px 5px 4px 0; font-weight: bold; text-align: right; width: 80px;">Total:</td>
                            <td style="padding: 4px 5px 4px 0; text-align: left; font-weight: bold;"> <?= $datos['totales']['total'] ?> </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>
    <div style="width: 100%; text-align: right; font-weight: bold; margin-top: 5px;"><?= $datos['totales']['letra'] ?></div>
</div>

<!--<div class="section" style="margin: 25px 0 0 0;">
    <table style="margin-left: auto;">
        <tr>
            <td style="width: 30%; padding: 0 5px 0 0; vertical-align: top;">
                <img src="<?php /*= $datos['qr'] */?>" style="margin: 0 auto;">
            </td>
            <td style="width: 70%; padding: 0 0 0 5px;  vertical-align: top;">
                <table style="width: 100%;">
                    <tr>
                        <td>
                            <div style="border: 1px solid #007b8e; border-radius: 3px;">
                                <div style="background-color: #007b8e; color: white; padding: 5px; font-weight: bold;">
                                    Cadena original del complemento de certificación digital del SAT:
                                </div>
                                <div style="padding: 5px;">
                                    <table style="width: 410%;">
                                        <tr>
                                            <td style="padding: 3px 5px 3px 0;">
                                                <div style="width: 500px; white-space: normal; overflow-wrap: break-word; word-break: break-word;">
                                                    <?php /*= $datos['sellos']['cadena_original'] */?>
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div style="border: 1px solid #007b8e; border-radius: 3px; margin: 5px 0;">
                                <div style="background-color: #007b8e; color: white; padding: 5px; font-weight: bold;">
                                    Sello digital del CFDI:
                                </div>
                                <div style="padding: 5px;">
                                    <table style="width: 410%;">
                                        <tr>
                                            <td style="padding: 3px 5px 3px 0;">
                                                <div style="width: 500px; white-space: normal; overflow-wrap: break-word; word-break: break-word;">
                                                    <?php /*= $datos['sellos']['sello_cfdi'] */?>
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div style="border: 1px solid #007b8e; border-radius: 3px;">
                                <div style="background-color: #007b8e; color: white; padding: 5px; font-weight: bold;">
                                    Sello del SAT:
                                </div>
                                <div style="padding: 5px;">
                                    <table style="width: 100%;">
                                        <tr>
                                            <td style="padding: 3px 5px 3px 0;">
                                                <div style="width: 500px; white-space: normal; overflow-wrap: break-word; word-break: break-word;">
                                                    <?php /*= $datos['sellos']['sello_sat'] */?>
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</div>-->

</body>
</html>