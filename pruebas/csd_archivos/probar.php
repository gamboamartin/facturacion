<?php
require dirname(__DIR__, 2) . '/orm/archivos_csd_operacion.php';
use gamboamartin\facturacion\models\archivos_csd_operacion as Journal;
final class Parcial extends Journal {
    protected function escribir_contenido($archivo, string $contenido): void {
        fwrite($archivo, substr($contenido, 0, 2));
        throw new RuntimeException('Fallo sintético');
    }
}
final class RetiroFallido extends Journal {
    protected function retirar_archivo(string $ruta): bool { return false; }
}
function verificar(bool $valor, string $caso): void {
    if (!$valor) { throw new RuntimeException($caso); }
    echo "PASS $caso\n";
}
function rechaza(callable $accion): bool {
    try { $accion(); } catch (RuntimeException) { return true; }
    return false;
}
$d = sys_get_temp_dir() . '/qa-journal-' . bin2hex(random_bytes(8));
mkdir($d, 0700);
try {
    $j = new Journal([$d]); $j->crear("$d/nuevo", 'FICTICIO');
    verificar((fileperms("$d/nuevo") & 0777) === 0600 && $j->revertir()['completo'] && !file_exists("$d/nuevo"), 'nuevo_privado_retirado');
    file_put_contents("$d/previo", 'PREEXISTENTE');
    verificar(rechaza(fn() => (new Journal([$d]))->crear("$d/previo", 'OTRO')) && file_get_contents("$d/previo") === 'PREEXISTENTE', 'preexistente_intacto');
    symlink("$d/previo", "$d/enlace");
    verificar(rechaza(fn() => (new Journal([$d]))->crear("$d/enlace", 'OTRO')), 'symlink_hoja_rechazado');
    mkdir("$d/padre"); symlink("$d/padre", "$d/alias");
    verificar(rechaza(fn() => (new Journal([$d]))->crear("$d/alias/nuevo", 'OTRO')), 'symlink_padre_rechazado');
    $j = new Journal([$d]); $j->crear("$d/cambiado", 'ANTES'); file_put_contents("$d/cambiado", 'OTROS');
    verificar(!$j->revertir()['completo'] && file_get_contents("$d/cambiado") === 'OTROS', 'contenido_cambiado_no_retirado');
    $j = new Journal([$d]); $j->crear("$d/inodo", 'IGUAL'); rename("$d/inodo", "$d/inodo_anterior"); file_put_contents("$d/inodo", 'IGUAL');
    verificar(!$j->revertir()['completo'] && file_exists("$d/inodo"), 'inodo_cambiado_no_retirado');
    $j = new Journal([$d]); $j->crear("$d/hard", 'IGUAL'); link("$d/hard", "$d/hard_otro");
    verificar(!$j->revertir()['completo'] && file_exists("$d/hard"), 'hardlink_no_retirado');
    $j = new Parcial([$d]); verificar(rechaza(fn() => $j->crear("$d/parcial", 'FICTICIO')) && $j->revertir()['completo'] && !file_exists("$d/parcial"), 'parcial_registrado_y_retirado');
    $j = new RetiroFallido([$d]); $j->crear("$d/fallo", 'FICTICIO'); $r = $j->revertir();
    verificar(!$r['completo'] && $r['pendientes'] === ['retiro_fallido'] && file_exists("$d/fallo"), 'fallo_retiro_explicito');
    verificar(rechaza(fn() => (new Journal([$d]))->crear("$d/../escape", 'FICTICIO')), 'traversal_rechazado');
    $hermano = $d . '-hermano'; mkdir($hermano, 0700);
    verificar(rechaza(fn() => (new Journal([$d]))->crear("$hermano/archivo", 'FICTICIO')), 'prefijo_hermano_rechazado');
    rmdir($hermano);
    $j = new Journal([$d]); $j->crear("$d/ausente", 'FICTICIO'); unlink("$d/ausente");
    verificar($j->revertir()['completo'] && $j->revertir()['completo'], 'reversion_idempotente_ausente');
    $j = new Journal([$d]); $j->crear("$d/confirmado", 'FICTICIO'); $j->confirmar();
    verificar($j->revertir()['completo'] && file_exists("$d/confirmado"), 'commit_conserva_archivo');
} finally {
    foreach (glob("$d/*") as $f) { if (is_link($f) || is_file($f)) { unlink($f); } else { rmdir($f); } }
    if (!rmdir($d) || file_exists($d)) {
        throw new RuntimeException('Limpieza no acreditada');
    }
}
echo "LIMPIEZA_VERIFICADA\n";
