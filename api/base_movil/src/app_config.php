<?php
namespace base_movil\v1\src;

use PDO;

/**
 * Configuración general de la app (Prototipo #6).
 *
 * La app la pide al arrancar, antes del login, así que es PÚBLICA: aquí
 * nunca va nada sensible (credenciales, datos de usuarios, rutas internas).
 *
 * Forma de la respuesta (mismo idioma que el resto del JSON de la app):
 *   {
 *     version: 1,
 *     ttl: 300,                      // segundos que la app la usa sin volver a pedirla
 *     parameters: {
 *       initialScreenId: 'home',      // primera pantalla al abrir la app
 *       requestTimeoutSeconds: 15,    // espera máxima por petición (5 a 120)
 *       offlinePrecache: true         // descargar pantallas para usar sin conexión
 *     }
 *   }
 *
 * La app ignora claves que no conoce y, si un valor es inválido, usa su
 * valor por defecto. Agregar un parámetro = agregarlo aquí y en
 * src/config/appConfig.ts.
 */
class app_config
{
    /** Pantalla inicial. La usa también app_sesion::login para no repetirla. */
    public const PANTALLA_INICIAL = 'home';

    private const VERSION = 1;

    /** Con ttl corto, un cambio aquí llega a la app en máximo ese tiempo. */
    private const TTL = 300;

    public function __construct(PDO $link)
    {
        // El router construye todas las clases con la conexión; esta no la usa.
    }

    public function configuracion_app(): array
    {
        return array(
            'version' => self::VERSION,
            'ttl' => self::TTL,
            'parameters' => array(
                'initialScreenId' => self::PANTALLA_INICIAL,
                'requestTimeoutSeconds' => 15,
                'offlinePrecache' => true,
            ),
        );
    }
}
