<?php
namespace base_movil\v1\src;

use gamboamartin\errores\errores;

/**
 * Sigue la misma convención que app_cobranza.php de em3: los métodos
 * públicos devuelven arrays (nunca hacen echo directo), y es el
 * enrutador (index.php) quien decide cómo mostrar la respuesta.
 */
class app_screens
{
    private errores $errores;

    public function __construct()
    {
        $this->errores = new errores();
    }

    public function obten_pantalla(): array
    {
        if (!isset($_GET['screenId']) || trim($_GET['screenId']) === '') {
            return $this->errores->error(
                mensaje: 'Error $_GET[screenId] debe existir',
                data: $_GET
            );
        }

        $screenId = trim($_GET['screenId']);

        // Por ahora solo existe la pantalla "home" — cuando haya más,
        // esto se convierte en una consulta a base de datos o a un
        // arreglo de pantallas disponibles.
        if ($screenId !== 'home') {
            return $this->errores->error(
                mensaje: "No existe la pantalla '$screenId'",
                data: array('screenId' => $screenId)
            );
        }

        return $this->pantalla_home();
    }

    private function pantalla_home(): array
    {
        return array(
            'screenId' => 'home',
            'title' => 'Pantalla de prueba',
            'components' => array(
                array(
                    'type' => 'Container',
                    'id' => 'card-1',
                    'style' => array(
                        'padding' => 16,
                        'backgroundColor' => '#F5F5F5',
                        'borderRadius' => 8,
                        'gap' => 8,
                    ),
                    'children' => array(
                        array(
                            'type' => 'Text',
                            'id' => 'title-1',
                            'content' => '¡Prueba en vivo desde el backend!',
                            'style' => array('fontSize' => 20, 'fontWeight' => 'bold'),
                        ),
                        array(
                            'type' => 'Text',
                            'id' => 'subtitle-1',
                            'content' => 'Este texto y este botón vienen del JSON del backend, no están hardcodeados en la app.',
                            'style' => array('fontSize' => 14, 'color' => '#555555'),
                        ),
                        array(
                            'type' => 'Button',
                            'id' => 'btn-1',
                            'label' => 'Continuar',
                            'style' => array(
                                'backgroundColor' => '#22A45D',
                                'color' => '#FFFFFF',
                                'padding' => 10,
                                'borderRadius' => 6,
                            ),
                        ),
                    ),
                ),
            ),
        );
    }
}
