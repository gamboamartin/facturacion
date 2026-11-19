<?php

/** Lee únicamente imágenes del almacén canónico, nunca URLs ni rutas de la petición. */
final class LectorLogoPrincipal
{
    public static function leer(string $directorio, string $rutaRelativa): ?string
    {
        $prefijo = 'archivos/doc_documento/';
        if (!str_starts_with($rutaRelativa, $prefijo)) {
            return null;
        }
        $nombre = substr($rutaRelativa, strlen($prefijo));
        if ($nombre === '' || str_starts_with($nombre, '.') || basename($nombre) !== $nombre
            || str_contains($nombre, '..') || str_contains($nombre, "\0")
            || str_contains($nombre, '\\') || str_contains($nombre, ':')) {
            return null;
        }

        // También rechaza enlaces en los directorios padre del almacén.
        $raiz = realpath($directorio);
        if ($raiz === false || $raiz !== rtrim($directorio, DIRECTORY_SEPARATOR)) {
            return null;
        }
        $ruta = $raiz . DIRECTORY_SEPARATOR . $nombre;
        clearstatcache(true, $ruta);
        if (is_link($ruta) || realpath($ruta) !== $ruta || !is_file($ruta)) {
            return null;
        }
        $antes = lstat($ruta);
        $archivo = @fopen($ruta, 'rb');
        if ($antes === false || $archivo === false) {
            return null;
        }
        try {
            $abierto = fstat($archivo);
            $limite = 2 * 1024 * 1024;
            if ($abierto === false || $antes['ino'] !== $abierto['ino']
                || $antes['dev'] !== $abierto['dev']
                || ($abierto['mode'] & 0170000) !== 0100000
                || $abierto['size'] <= 0 || $abierto['size'] > $limite) {
                return null;
            }
            $contenido = stream_get_contents($archivo, $limite + 1);
            if ($contenido === false || strlen($contenido) > $limite) {
                return null;
            }
            $mime = (new finfo(FILEINFO_MIME_TYPE))->buffer($contenido);
            $dimensiones = @getimagesizefromstring($contenido);
            if (!in_array($mime, ['image/png', 'image/jpeg'], true)
                || $dimensiones === false || ($dimensiones['mime'] ?? '') !== $mime) {
                return null;
            }
            // La cabecera no demuestra que la imagen esté completa ni decodifique.
            $ancho = (int)$dimensiones[0];
            $alto = (int)$dimensiones[1];
            if ($ancho <= 0 || $alto <= 0 || $ancho > 8192 || $alto > 8192
                || $ancho * $alto > 16 * 1024 * 1024
                || !function_exists('imagecreatefromstring')) {
                return null;
            }
            $imagen = @imagecreatefromstring($contenido);
            if ($imagen === false) {
                return null;
            }
            try {
                if (imagesx($imagen) !== $ancho || imagesy($imagen) !== $alto) {
                    return null;
                }
            } finally {
                imagedestroy($imagen);
            }
            return 'data:' . $mime . ';base64,' . base64_encode($contenido);
        } finally {
            fclose($archivo);
        }
    }
}
