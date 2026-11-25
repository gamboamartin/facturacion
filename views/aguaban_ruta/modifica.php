<?php
$ruta_titulo = 'Editar ruta';
$ruta_modo = 'modifica';
require __DIR__.'/_inicio.php';
if ((int)($ruta_registro['id'] ?? 0) > 0) {
    require __DIR__.'/_formulario.php';
} else {
    echo '<p class="rutas-tarjeta">No se puede editar la ruta solicitada. Vuelve a la lista y selecciona una ruta existente.</p>';
}
?>
</section>
