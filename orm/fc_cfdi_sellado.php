<?php

namespace gamboamartin\facturacion\models;

use base\orm\_modelo_parent;
use gamboamartin\errores\errores;
use PDO;
use stdClass;

class fc_cfdi_sellado extends _modelo_parent
{
    public function __construct(PDO $link)
    {
        $tabla = 'fc_cfdi_sellado';
        $columnas = array($tabla => false, 'fc_factura' => $tabla);

        $campos_obligatorios = array();

        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas);

        $this->NAMESPACE = __NAMESPACE__;
        $this->etiqueta = 'CFDI Sellado';
    }

    public function alta_bd(array $keys_integra_ds = array('codigo', 'descripcion')): array|stdClass
    {

        $permite_transaccion = (new fc_factura(link: $this->link))->verifica_permite_transaccion(registro_id: $this->registro['fc_factura_id']);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error verificar transaccion', data: $permite_transaccion);
        }
        if (!isset($this->registro['codigo'])) {
            $this->registro['codigo'] = $this->registro['comprobante_no_certificado'];
        }

        $this->registro = $this->campos_base(data: $this->registro, modelo: $this);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al inicializar campos base', data: $this->registro);
        }

        $this->registro = $this->validaciones(data: $this->registro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar foraneas', data: $this->registro);
        }

        $r_alta_bd = parent::alta_bd();
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error registrar cfdi sellado', data: $r_alta_bd);
        }

        return $r_alta_bd;
    }

    final public function elimina_bd(int $id): array|stdClass
    {
        $fc_cfdi_sellado = $this->registro(registro_id: $id, retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener registro', data: $fc_cfdi_sellado);
        }

        $permite_transaccion = (new fc_factura(link: $this->link))->verifica_permite_transaccion(registro_id: $fc_cfdi_sellado->fc_factura_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error verificar transaccion', data: $permite_transaccion);
        }
        errores::$error = true;
        return $this->error->error(mensaje: 'Error este registro no puede ser eliminado',data: $id);

    }

    public function maqueta_datos(string $codigo, string $descripcion, int $fc_factura_id, string $comprobante_sello,
                                  string $comprobante_certificado, string $comprobante_no_certificado,
                                  string $complemento_tfd_sl, string $complemento_tfd_fecha_timbrado,
                                  string $complemento_tfd_no_certificado_sat, string $complemento_tfd_rfc_prov_certif,
                                  string $complemento_tfd_sello_cfd, string $complemento_tfd_sello_sat, string $uuid,
                                  string $complemento_tfd_tfd, string $cadena_complemento_sat): array
    {
        $data = array();
        $data['codigo'] = $codigo;
        $data['descripcion'] = $descripcion;
        $data['fc_factura_id'] = $fc_factura_id;
        $data['comprobante_sello'] = $comprobante_sello;
        $data['comprobante_certificado'] = $comprobante_certificado;
        $data['comprobante_no_certificado'] = $comprobante_no_certificado;
        $data['complemento_tfd_sl'] = $complemento_tfd_sl;
        $data['complemento_tfd_fecha_timbrado'] = $complemento_tfd_fecha_timbrado;
        $data['complemento_tfd_no_certificado_sat'] = $complemento_tfd_no_certificado_sat;
        $data['complemento_tfd_rfc_prov_certif'] = $complemento_tfd_rfc_prov_certif;
        $data['complemento_tfd_sello_cfd'] = $complemento_tfd_sello_cfd;
        $data['complemento_tfd_sello_sat'] = $complemento_tfd_sello_sat;
        $data['uuid'] = $uuid;
        $data['complemento_tfd_tfd'] = $complemento_tfd_tfd;
        $data['cadena_complemento_sat'] = $cadena_complemento_sat;

        return $data;
    }


    public function modifica_bd(array $registro, int $id, bool $reactiva = false,
                                array $keys_integra_ds = array('codigo', 'descripcion')): array|stdClass
    {
        $fc_cfdi_sellado = $this->registro(registro_id: $id, retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener registro', data: $fc_cfdi_sellado);
        }
        $permite_transaccion = (new fc_factura(link: $this->link))->verifica_permite_transaccion(registro_id: $fc_cfdi_sellado->fc_factura_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error verificar transaccion', data: $permite_transaccion);
        }
        errores::$error = true;
        return $this->error->error(mensaje: 'Error este registro no puede ser eliminado',data: $id);
    }

    final public function status(string $campo, int $registro_id): array|stdClass
    {
        $fc_cfdi_sellado = $this->registro(registro_id: $registro_id, retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener registro', data: $fc_cfdi_sellado);
        }
        $permite_transaccion = (new fc_factura(link: $this->link))->verifica_permite_transaccion(registro_id: $fc_cfdi_sellado->fc_factura_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error verificar transaccion', data: $permite_transaccion);
        }
        $r_status = parent::status($campo, $registro_id); // TODO: Change the autogenerated stub
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error verificar r_status', data: $permite_transaccion);
        }
        return $r_status;
    }

    private function validaciones(array $data): array
    {
        $keys = array('descripcion', 'codigo');
        $valida = $this->validacion->valida_existencia_keys(keys: $keys, registro: $data);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar campos', data: $valida);
        }

        $keys = array('fc_factura_id');
        $valida = $this->validacion->valida_ids(keys: $keys, registro: $data);
        if (errores::$error) {
            return $this->error->error(mensaje: "Error al validar foraneas", data: $valida);
        }

        return $data;
    }
}