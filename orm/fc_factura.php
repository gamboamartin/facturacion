<?php

namespace gamboamartin\facturacion\models;

use gamboamartin\errores\errores;
use PDO;
use stdClass;

class fc_factura extends _transacciones_fc
{

    public function __construct(PDO $link)
    {
        $tabla = 'fc_factura';

        $fc_factura_uuid = "(SELECT IFNULL(fc_cfdi_sellado.uuid,'') FROM fc_cfdi_sellado WHERE fc_cfdi_sellado.fc_factura_id = fc_factura.id)";

        $fc_factura_etapa = "(SELECT pr_etapa.descripcion FROM pr_etapa 
            LEFT JOIN pr_etapa_proceso ON pr_etapa_proceso.pr_etapa_id = pr_etapa.id 
            LEFT JOIN fc_factura_etapa ON fc_factura_etapa.pr_etapa_proceso_id = pr_etapa_proceso.id
            WHERE fc_factura_etapa.fc_factura_id = fc_factura.id ORDER BY fc_factura_etapa.id DESC LIMIT 1)";


        $columnas_extra['fc_factura_uuid'] = "IFNULL($fc_factura_uuid,'SIN UUID')";
        $columnas_extra['fc_factura_etapa'] = "$fc_factura_etapa";


        parent::__construct(link: $link, tabla: $tabla, columnas_extra: $columnas_extra);

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'Factura';
        $this->key_fc_id = 'fc_factura_id';

    }

    public function alta_bd(): array|stdClass
    {
        $this->modelo_email = new fc_email(link: $this->link);
        $this->modelo_etapa = new fc_factura_etapa(link: $this->link);

        $this->registro['aplica_saldo'] = 'inactivo';

        $r_alta_bd = parent::alta_bd(); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar',data:  $r_alta_bd);
        }
        return $r_alta_bd;
    }

    public function elimina_bd(int $id): array|stdClass
    {

        $this->modelo_etapa = new fc_factura_etapa(link: $this->link);
        $this->modelo_partida = new fc_partida(link: $this->link);
        $this->modelo_documento = new fc_factura_documento(link: $this->link);
        $this->modelo_email = new fc_email(link: $this->link);
        $this->modelo_sello = new fc_cfdi_sellado(link: $this->link);
        $this->modelo_relacionada = new fc_factura_relacionada(link: $this->link);
        $this->modelo_notificacion = new fc_notificacion(link: $this->link);
        $this->modelo_relacion = new fc_relacion(link: $this->link);


        $r_elimina_bd = parent::elimina_bd(id: $id); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al eliminar',data:  $r_elimina_bd);
        }
        return $r_elimina_bd;
    }

    public function get_pagos_nc(int $fc_factura_id){
        if($fc_factura_id <= 0){
            return $this->error->error(mensaje: 'Error fc_factura_id debe ser mayor a 0',data:  $fc_factura_id);
        }
        $filtro['fc_factura.id'] = $fc_factura_id;
        $r_fc_nc_rel = (new fc_nc_rel(link: $this->link))->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener relaciones de notas de credito',data:  $r_fc_nc_rel);
        }
        $fc_nc_rels = $r_fc_nc_rel->registros;

        $total_pagos = 0.0;
        foreach ($fc_nc_rels as $fc_nc_rel){
            if($fc_nc_rel['fc_nota_credito_aplica_saldo'] === 'activo') {
                $total_pagos += round($fc_nc_rel['fc_nc_rel_monto_aplicado_factura'], 2);
            }
        }

        return $total_pagos;

    }

    public function modifica_bd(array $registro, int $id, bool $reactiva = false): array|stdClass
    {
        $this->modelo_etapa = new fc_factura_etapa(link: $this->link);

        $r_modifica_bd = parent::modifica_bd(registro: $registro,id:  $id,reactiva:  $reactiva); // TODO: Change the autogenerated stub
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al modificar', data: $r_modifica_bd);
        }
        return $r_modifica_bd;
    }

}