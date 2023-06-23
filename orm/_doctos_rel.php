<?php
namespace gamboamartin\facturacion\models;


use gamboamartin\errores\errores;
use PDO;
use stdClass;

class _doctos_rel{

    private errores $error;

    public function __construct(){
        $this->error = new errores();
    }

    private function asigna_dato_monto(stdClass $data_monto, array $fc_factura, int $fc_factura_id, int $fc_pago_pago_id): stdClass
    {
        $monto = $fc_factura[$fc_factura_id][$fc_pago_pago_id];
        $data_monto->monto = $monto;
        $data_monto->existe_monto = true;
        return $data_monto;
    }

    private function asigna_data_monto_pago(stdClass $data_monto, array $fc_factura, int $fc_factura_id, int $fc_pago_pago_id): array|stdClass
    {

        if((float)trim($fc_factura[$fc_factura_id][$fc_pago_pago_id]) > 0.0){

            $data_monto = $this->asigna_dato_monto(data_monto: $data_monto, fc_factura: $fc_factura,
                fc_factura_id: $fc_factura_id,fc_pago_pago_id:  $fc_pago_pago_id);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al asignar datos de monto', data: $data_monto);
            }
        }
        return $data_monto;
    }

    private function asigna_data_monto_pago_existente(stdClass $data_monto, array $fc_factura, int $fc_factura_id, int $fc_pago_pago_id): array|stdClass
    {
        if(isset($fc_factura[$fc_factura_id][$fc_pago_pago_id])){
            $data_monto = $this->asigna_data_monto_pago_vacio(data_monto: $data_monto,
                fc_factura:  $fc_factura,fc_factura_id:  $fc_factura_id, fc_pago_pago_id: $fc_pago_pago_id);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al asignar datos de monto', data: $data_monto);
            }
        }
        return $data_monto;
    }

    private function asigna_data_monto_pago_factura(stdClass $data_monto, array $fc_factura, int $fc_factura_id, int $fc_pago_pago_id): array|stdClass
    {
        if(isset($fc_factura[$fc_factura_id])){
            $data_monto = $this->asigna_data_monto_pago_existente(data_monto: $data_monto,
                fc_factura:  $fc_factura,fc_factura_id:  $fc_factura_id,fc_pago_pago_id:  $fc_pago_pago_id);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al asignar datos de monto', data: $data_monto);
            }
        }
        return $data_monto;
    }

    private function asigna_data_monto_pago_vacio(stdClass $data_monto, array $fc_factura, int $fc_factura_id, int $fc_pago_pago_id): array|stdClass
    {
        if(trim($fc_factura[$fc_factura_id][$fc_pago_pago_id])!==''){
            $data_monto = $this->asigna_data_monto_pago(data_monto: $data_monto,
                fc_factura:  $fc_factura,fc_factura_id:  $fc_factura_id,fc_pago_pago_id:  $fc_pago_pago_id);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al asignar datos de monto', data: $data_monto);
            }
        }
        return $data_monto;
    }

    private function data_docto_rel(array $fc_factura): array|stdClass
    {
        $ids = $this->ids_docto(fc_factura: $fc_factura);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al asignar ids', data: $ids);
        }
        $data_monto = $this->data_monto(fc_factura: $fc_factura,fc_factura_id:  $ids->fc_factura_id, fc_pago_pago_id: $ids->fc_pago_pago_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al asignar datos de monto', data: $data_monto);
        }
        $data_monto->fc_factura_id = $ids->fc_factura_id;
        $data_monto->fc_pago_pago_id = $ids->fc_pago_pago_id;
        return $data_monto;
    }

    private function data_monto(array $fc_factura, int $fc_factura_id, int $fc_pago_pago_id): array|stdClass
    {
        $data_monto = new stdClass();
        $data_monto->existe_monto = false;
        $data_monto->monto = 0;

        $data_monto = $this->asigna_data_monto_pago_factura(data_monto: $data_monto,fc_factura:  $fc_factura,fc_factura_id:  $fc_factura_id,fc_pago_pago_id:  $fc_pago_pago_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al asignar datos de monto', data: $data_monto);
        }
        return $data_monto;
    }

    private function fc_docto_relacionado_ins(stdClass $data_monto): array
    {
        $fc_docto_relacionado_ins['fc_factura_id'] = $data_monto->fc_factura_id;
        $fc_docto_relacionado_ins['imp_pagado'] = $data_monto->monto;
        $fc_docto_relacionado_ins['fc_pago_pago_id'] = $data_monto->fc_pago_pago_id;
        return $fc_docto_relacionado_ins;
    }

    /**
     * Obtiene los ids para insertar un docto relacionado
     * @param array $fc_factura Factura en proceso
     * @return stdClass
     */
    private function ids_docto(array $fc_factura): stdClass
    {
        $fc_factura_id = key($fc_factura);
        $fc_pago_pago_id = key($fc_factura[$fc_factura_id]);

        $data = new stdClass();
        $data->fc_factura_id = $fc_factura_id;
        $data->fc_pago_pago_id = $fc_pago_pago_id;

        return $data;

    }

    private function inserta_docto_existente(array $altas, array $fc_factura, PDO $link): array
    {
        $data_monto = $this->data_docto_rel(fc_factura: $fc_factura);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al asignar datos de monto', data: $data_monto);
        }

        if($data_monto->existe_monto) {
            $alta_bd = $this->inserta_docto_relacionado(data_monto: $data_monto, link: $link);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al insertar', data: $alta_bd);
            }
            $altas[] = $alta_bd;
        }
        return $altas;
    }

    private function inserta_docto_relacionado(stdClass $data_monto, PDO $link): array|stdClass
    {
        $fc_docto_relacionado_ins = $this->fc_docto_relacionado_ins(data_monto: $data_monto);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al generar docto row', data: $fc_docto_relacionado_ins);
        }
        $alta_bd = (new fc_docto_relacionado(link: $link))->alta_registro(registro: $fc_docto_relacionado_ins);
        if (errores::$error) {

            return $this->error->error(mensaje: 'Error al insertar', data: $alta_bd);
        }
        return $alta_bd;
    }

    final public function inserta_doctos_relacionados(PDO $link, array $montos): array
    {
        $altas = array();
        foreach ($montos as $fc_factura){

            $altas = $this->inserta_docto_existente(altas: $altas,fc_factura:  $fc_factura, link: $link);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al insertar', data: $altas);
            }
        }
        return $altas;
    }

}
