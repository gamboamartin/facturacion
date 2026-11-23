<?php
declare(strict_types=1);

namespace config { class generales {} }

namespace gamboamartin\facturacion\models {
    class _doc {} class _etapa {} class _partida {} class _cuenta_predial {} class _relacion {}
    class _relacionada {} class _data_impuestos {} class _sellado {} class _uuid_ext {}

    #[\AllowDynamicProperties]
    class _transacciones_fc
    {
        public static array $llamadas = [];
        public static array $fases = [];
        public static string $modo = 'exito';
        public \gamboamartin\errores\errores $error;
        public function __construct(public \PDO $link, string $tabla, array $columnas_extra, bool $valida_atributos_criticos)
        {
            $this->error = new \gamboamartin\errores\errores();
        }

        public function timbra_xml(_doc $modelo_documento, _etapa $modelo_etapa, _partida $modelo_partida,
                                   _cuenta_predial $modelo_predial, _relacion $modelo_relacion,
                                   _relacionada $modelo_relacionada, _data_impuestos $modelo_retencion,
                                   _sellado $modelo_sello, _data_impuestos $modelo_traslado, _uuid_ext $modelo_uuid_ext,
                                   int $registro_id): array|\stdClass
        {
            self::$llamadas[] = func_get_args();
            self::$fases[] = $this->link->intentos[$registro_id]['estado'];
            $this->antes_timbrar_pac($registro_id);
            self::$fases[] = $this->link->intentos[$registro_id]['estado'];
            if (self::$modo === 'timeout') { throw new \RuntimeException('SECRETO_FICTICIO_DE_PAC'); }
            $uuid = '00000000-0000-0000-0000-000000000001';
            $xml = '<cfdi:Comprobante xmlns:cfdi="http://www.sat.gob.mx/cfd/4"><cfdi:Complemento>'
                .'<tfd:TimbreFiscalDigital xmlns:tfd="http://www.sat.gob.mx/TimbreFiscalDigital" UUID="'.$uuid.'"/>'
                .'</cfdi:Complemento></cfdi:Comprobante>';
            $this->respuesta_timbrado_pac($registro_id, (object)['xml_sellado' => $xml, 'uuid' => $uuid], false);
            self::$fases[] = $this->link->intentos[$registro_id]['estado'];
            if (self::$modo === 'error_local') {
                \gamboamartin\errores\errores::$error = true;
                return ['error' => true, 'mensaje' => 'Fallo local ficticio'];
            }
            $this->link->facturas[$registro_id]['folio_fiscal'] = $uuid;
            $this->link->sellos[$registro_id] = [$uuid];
            return ['uuid' => $uuid];
        }
    }
}
