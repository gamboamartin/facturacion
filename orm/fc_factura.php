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

        $fc_factura_uuid = "IFNULL($tabla.folio_fiscal,'SIN UUID')";

        $columnas_extra['fc_factura_uuid'] = $fc_factura_uuid;

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

        $regenera = (new _saldos_fc())->regenera_saldos(fc_factura_id: $r_alta_bd->registro_id,link: $this->link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al regenera',data:  $regenera);
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

        $filtro['fc_factura.id'] = $id;
        $del = (new fc_factura_automatica(link: $this->link))->elimina_con_filtro_and(filtro:$filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al eliminar',data:  $del);
        }

        $r_elimina_bd = parent::elimina_bd(id: $id); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al eliminar',data:  $r_elimina_bd);
        }
        return $r_elimina_bd;
    }


    public function modifica_bd(array $registro, int $id, bool $reactiva = false,
                                bool $verifica_permite_transaccion = true): array|stdClass
    {
        $this->modelo_etapa = new fc_factura_etapa(link: $this->link);

        $r_modifica_bd = parent::modifica_bd(registro: $registro,id:  $id,reactiva:  $reactiva,
            verifica_permite_transaccion: $verifica_permite_transaccion); // TODO: Change the autogenerated stub
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al modificar', data: $r_modifica_bd);
        }
        return $r_modifica_bd;
    }


}