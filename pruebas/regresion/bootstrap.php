<?php
declare(strict_types=1);
// Sin autoload ni configuración de la aplicación. Solo raíces de fuente bajo prueba.
if (!defined('RAIZ_REPOSITORIO_PRUEBAS')) { define('RAIZ_REPOSITORIO_PRUEBAS', dirname(__DIR__, 2)); }
putenv('RAIZ_REPO='.RAIZ_REPOSITORIO_PRUEBAS);
putenv('FUENTE_FACTURACION='.RAIZ_REPOSITORIO_PRUEBAS);
