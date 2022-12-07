<?php
namespace gamboamartin\facturacion\models;
use gamboamartin\errores\errores;
use stdClass;


class _facturacion {
    private errores $error;

    public function __construct(){
        $this->error = new errores();
    }

    private function fc_partida_importe(): string
    {
        return "(fc_partida.cantidad * fc_partida.valor_unitario)";
    }

    private function fc_partida_importe_con_descuento(): string
    {

        $fc_partida_importe = (new _facturacion())->fc_partida_importe();
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar fc_partida_importe',data:  $fc_partida_importe);
        }
        return "($fc_partida_importe - fc_partida.descuento)";
    }

    public function fc_impuesto_importe(string $fc_partida_importe_con_descuento): string
    {
        return"$fc_partida_importe_con_descuento * cat_sat_factor.factor";
    }


    public function importes_base(): array|stdClass
    {
        $fc_partida_importe = $this->fc_partida_importe();
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar fc_partida_importe',data:  $fc_partida_importe);
        }
        $fc_partida_importe_con_descuento = $this->fc_partida_importe_con_descuento();
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar fc_partida_importe_con_descuento',data:  $fc_partida_importe_con_descuento);
        }

        $data = new stdClass();
        $data->fc_partida_importe = $fc_partida_importe;
        $data->fc_partida_importe_con_descuento = $fc_partida_importe_con_descuento;

        return $data;
    }





}
