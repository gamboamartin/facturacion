<?php

namespace gamboamartin\facturacion\models;

use gamboamartin\errores\errores;
use stdClass;

/** Aplica las reglas de baja de la aplicación sin modificar el paquete organigrama. */
class org_empresa extends \gamboamartin\organigrama\models\org_empresa
{
    public function elimina_bd(int $id): array|stdClass
    {
        $desactiva_dependientes = $this->desactiva_dependientes;
        $this->desactiva_dependientes = false;
        try {
            return (new referencias_baja_empresa($this->link))->eliminar($id, function () use ($id): stdClass {
                $resultado = parent::elimina_bd($id);
                if (errores::$error || !$resultado instanceof stdClass) {
                    throw new \RuntimeException('No se pudo completar la baja de la empresa.');
                }
                return $resultado;
            });
        } catch (\PDOException) {
            return $this->error->error(
                mensaje: 'No se pudo completar la baja de la empresa. Revise sus dependencias.',
                data: $id,
                es_final: true
            );
        } catch (\RuntimeException $error) {
            return $this->error->error(mensaje: $error->getMessage(), data: $id, es_final: true);
        } catch (\Throwable) {
            return $this->error->error(mensaje: 'No se pudo completar la baja de la empresa.', data: $id, es_final: true);
        } finally {
            $this->desactiva_dependientes = $desactiva_dependientes;
        }
    }
}
