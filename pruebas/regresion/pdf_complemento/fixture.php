<?php
declare(strict_types=1);

/** Datos fiscales totalmente ficticios; no son un CFDI firmado ni válido para timbrar. */
function xmlComplementoPrueba(bool $timbrado = true): string
{
    $timbre = $timbrado ? '<t:TimbreFiscalDigital Version="1.1" UUID="11111111-1111-4111-8111-111111111111" FechaTimbrado="2026-11-24T13:05:09" RfcProvCertif="AAA010101AAA" NoCertificadoSAT="00001000000500000000" SelloCFD="SELLOFICTICIO+abc/=" SelloSAT="SELLOSATFICTICIO"/>' : '';
    return '<?xml version="1.0" encoding="UTF-8"?>'.<<<XML
<c:Comprobante xmlns:c="http://www.sat.gob.mx/cfd/4" xmlns:p="http://www.sat.gob.mx/Pagos20" xmlns:t="http://www.sat.gob.mx/TimbreFiscalDigital" Version="4.0" TipoDeComprobante="P" Serie="CP" Folio="000042" Fecha="2026-11-24T12:00:01" LugarExpedicion="45010" NoCertificado="00001000000599999999" Sello="SELLOFICTICIO+abc/=" Total="0" Moneda="XXX">
<c:Emisor Rfc="AAA010101AAA" Nombre="EMISOR FICTICIO ÁRBOL" RegimenFiscal="601"/>
<c:Receptor Rfc="BBB010101BBB" Nombre="RECEPTOR FICTICIO MUÑOZ" RegimenFiscalReceptor="601" UsoCFDI="CP01" DomicilioFiscalReceptor="64000"/>
<c:CfdiRelacionados TipoRelacion="04"><c:CfdiRelacionado UUID="99999999-9999-4999-8999-999999999999"/></c:CfdiRelacionados>
<c:Complemento><p:Pagos Version="2.0"><p:Totales MontoTotalPagos="2430.00" TotalTrasladosBaseIVA16="500.00" TotalTrasladosImpuestoIVA16="80.00" TotalTrasladosBaseIVAExento="1700.00"/>
<p:Pago FechaPago="2026-11-20T08:30:00" FormaDePagoP="03" MonedaP="MXN" TipoCambioP="1" Monto="730.00">
<p:DoctoRelacionado IdDocumento="22222222-2222-4222-8222-222222222222" Serie="A" Folio="100" MonedaDR="MXN" EquivalenciaDR="1" ObjetoImpDR="02" NumParcialidad="2" ImpSaldoAnt="1160.00" ImpPagado="580.00" ImpSaldoInsoluto="580.00"><p:ImpuestosDR><p:TrasladosDR><p:TrasladoDR BaseDR="500.00" ImpuestoDR="002" TipoFactorDR="Tasa" TasaOCuotaDR="0.160000" ImporteDR="80.00"/></p:TrasladosDR><p:RetencionesDR><p:RetencionDR BaseDR="500.00" ImpuestoDR="001" TipoFactorDR="Tasa" TasaOCuotaDR="0.020000" ImporteDR="10.00"/></p:RetencionesDR></p:ImpuestosDR></p:DoctoRelacionado>
<p:DoctoRelacionado IdDocumento="33333333-3333-4333-8333-333333333333" Serie="B" Folio="200" MonedaDR="MXN" EquivalenciaDR="1" ObjetoImpDR="01" NumParcialidad="1" ImpSaldoAnt="300.00" ImpPagado="150.00" ImpSaldoInsoluto="150.00"/>
<p:ImpuestosP><p:TrasladosP><p:TrasladoP BaseP="500.00" ImpuestoP="002" TipoFactorP="Tasa" TasaOCuotaP="0.160000" ImporteP="80.00"/></p:TrasladosP><p:RetencionesP><p:RetencionP ImpuestoP="001" ImporteP="10.00"/></p:RetencionesP></p:ImpuestosP></p:Pago>
<p:Pago FechaPago="2026-11-21T09:45:00" FormaDePagoP="02" MonedaP="USD" TipoCambioP="17.000000" Monto="100.00"><p:DoctoRelacionado IdDocumento="44444444-4444-4444-8444-444444444444" Serie="USD" Folio="300" MonedaDR="USD" EquivalenciaDR="1" ObjetoImpDR="02" NumParcialidad="3" ImpSaldoAnt="100.00" ImpPagado="100.00" ImpSaldoInsoluto="0.00"><p:ImpuestosDR><p:TrasladosDR><p:TrasladoDR BaseDR="100.00" ImpuestoDR="002" TipoFactorDR="Exento"/></p:TrasladosDR></p:ImpuestosDR></p:DoctoRelacionado></p:Pago>
</p:Pagos>$timbre</c:Complemento></c:Comprobante>
XML;
}
