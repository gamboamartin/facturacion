<?php
declare(strict_types=1);
namespace gamboamartin\facturacion\models;
use gamboamartin\documento\models\doc_documento;
use gamboamartin\facturacion\models\contexto_archivos_csd;

/** Sólo seleccionada por _cert cuando existe contexto de alta CSD. */
class doc_documento_csd extends doc_documento {
    protected function guardar_archivo_alta(string $contenido, string $ruta): string|array {
        $contexto = contexto_archivos_csd::actual();
        if ($contexto === null) { return parent::guardar_archivo_alta($contenido, $ruta); }
        try {
            $contexto->crear_documento($ruta, $contenido);
            return strtolower($ruta);
        } catch (\Throwable) {
            return $this->error->error(mensaje: 'No se pudo guardar documento CSD privado', data: []);
        }
    }
}
