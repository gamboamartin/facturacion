<?php
declare(strict_types=1);

namespace gamboamartin\facturacion\models;

/** Journal opt-in. No integrado todavía al controlador ni al almacén documental. */
class archivos_csd_operacion {
    private array $creados = [];
    private array $raices = [];
    private array $directorios = [];

    /** Raíces privadas elegidas por la aplicación, nunca por GET. */
    public function __construct(array $raices) {
        if ($raices === []) { throw new \RuntimeException('Faltan raíces autorizadas'); }
        foreach ($raices as $raiz) {
            $real = realpath($raiz);
            if ($real === false || !is_dir($real) || is_link($raiz)) {
                throw new \RuntimeException('Raíz no válida');
            }
            $this->raices[] = rtrim($real, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        }
    }

    private function ruta_segura(string $ruta): string {
        if ($ruta === '' || str_contains($ruta, "\0") || str_contains($ruta, '://') || in_array('..', explode(DIRECTORY_SEPARATOR, $ruta), true)) {
            throw new \RuntimeException('Ruta de archivo no válida');
        }
        $padre = dirname($ruta);
        for ($p = $padre; ; $p = dirname($p)) {
            if (is_link($p)) { throw new \RuntimeException('Directorio enlazado no permitido'); }
            if ($p === dirname($p)) { break; }
        }
        $real = realpath($padre);
        if ($real === false || !is_dir($real) || in_array(basename($ruta), ['.', '..'], true)) {
            throw new \RuntimeException('Directorio no válido');
        }
        $canonica = $real . DIRECTORY_SEPARATOR . basename($ruta);
        foreach ($this->raices as $raiz) {
            if (str_starts_with($canonica, $raiz)) { return $canonica; }
        }
        throw new \RuntimeException('Ruta fuera de las raíces autorizadas');
    }

    protected function escribir_contenido($archivo, string $contenido): void {
        $posicion = 0;
        while ($posicion < strlen($contenido)) {
            $n = @fwrite($archivo, substr($contenido, $posicion));
            if ($n === false || $n === 0) { throw new \RuntimeException('Escritura incompleta'); }
            $posicion += $n;
        }
    }

    /** Nunca reemplaza un archivo existente. Registra también escrituras parciales. */
    public function crear(string $ruta, string $contenido): void {
        $ruta = $this->ruta_segura($ruta);
        if (file_exists($ruta) || is_link($ruta)) { throw new \RuntimeException('Destino ya existente'); }
        $archivo = @fopen($ruta, 'x+b');
        if ($archivo === false) { throw new \RuntimeException('No se pudo reservar el archivo'); }
        $identidad = @fstat($archivo);
        $this->creados[$ruta] = ['stat' => $identidad, 'hash' => null, 'temporal' => false];
        try {
            if (!@chmod($ruta, 0600)) { throw new \RuntimeException('No se pudo proteger el archivo'); }
            $protegido = @fstat($archivo);
            if (($protegido['mode'] & 0777) !== 0600) {
                throw new \RuntimeException('Permisos efectivos no privados');
            }
            $this->escribir_contenido($archivo, $contenido);
            if (!@fflush($archivo)) { throw new \RuntimeException('No se pudo completar la escritura'); }
        } finally {
            @fflush($archivo);
            $stat = @fstat($archivo);
            @rewind($archivo);
            $hash = hash_init('sha256');
            @hash_update_stream($hash, $archivo);
            $this->creados[$ruta] = ['stat' => $stat, 'hash' => hash_final($hash), 'temporal' => false];
            @fclose($archivo);
        }
    }

    /** El callback escribe sólo en un directorio privado creado exclusivamente para esta operación. */
    public function generar_temporal(string $raiz, callable $generar): array {
        $directorio = $this->ruta_segura($raiz . '/csd-' . bin2hex(random_bytes(16)));
        if (!@mkdir($directorio, 0700)) { throw new \RuntimeException('No se pudo reservar temporal'); }
        $this->directorios[$directorio] = @lstat($directorio);
        $ruta = $directorio . '/convertido.pem';
        try {
            $resultado = $generar($ruta);
            return ['ruta' => $ruta, 'resultado' => $resultado];
        } finally {
            clearstatcache(true, $ruta);
            $actual = @lstat($ruta);
            if ($actual !== false && !is_link($ruta) && ($actual['mode'] & 0170000) === 0100000) {
                $this->creados[$ruta] = ['stat' => $actual, 'hash' => @hash_file('sha256', $ruta), 'temporal' => true];
            }
        }
    }

    protected function retirar_archivo(string $ruta): bool { return @unlink($ruta); }

    /** Reporta rechazo/fallo; no borra una identidad o contenido que haya cambiado. */
    public function revertir(bool $solo_temporales = false): array {
        $resultado = ['retirados' => 0, 'pendientes' => []];
        foreach ($this->creados as $ruta => $registro) {
            if ($solo_temporales && !$registro['temporal']) { continue; }
            clearstatcache(true, $ruta);
            try { $segura = $this->ruta_segura($ruta); }
            catch (\Throwable) { $resultado['pendientes'][] = 'directorio_cambiado'; continue; }
            $actual = @lstat($ruta);
            if ($actual === false && !file_exists($ruta) && !is_link($ruta)) {
                unset($this->creados[$ruta]);
                continue;
            }
            $antes = $registro['stat'];
            $valido = $segura === $ruta && $actual !== false && !is_link($ruta)
                && ($actual['mode'] & 0170000) === 0100000
                && $actual['dev'] === $antes['dev'] && $actual['ino'] === $antes['ino']
                && $actual['nlink'] === 1 && $actual['uid'] === $antes['uid']
                && $actual['size'] === $antes['size'] && $registro['hash'] !== null
                && @hash_file('sha256', $ruta) === $registro['hash'];
            if (!$valido) { $resultado['pendientes'][] = 'identidad_o_contenido_cambiado'; continue; }
            if (!$this->retirar_archivo($ruta)) { $resultado['pendientes'][] = 'retiro_fallido'; continue; }
            unset($this->creados[$ruta]);
            $resultado['retirados']++;
        }
        foreach ($this->directorios as $ruta => $antes) {
            clearstatcache(true, $ruta);
            $actual = @lstat($ruta);
            if ($actual === false && !is_link($ruta)) { unset($this->directorios[$ruta]); continue; }
            if ($actual === false || is_link($ruta) || $actual['ino'] !== $antes['ino']
                || $actual['dev'] !== $antes['dev'] || !@rmdir($ruta)) {
                $resultado['pendientes'][] = 'directorio_temporal_no_retirado';
                continue;
            }
            unset($this->directorios[$ruta]);
        }
        $resultado['completo'] = $resultado['pendientes'] === [];
        return $resultado;
    }

    /** Sólo metadatos para un recibo privado; nunca contenido ni contraseñas. */
    public function evidencia_pendiente(): array {
        $archivos = [];
        foreach ($this->creados as $ruta => $registro) {
            $archivos[] = [
                'ruta' => $ruta, 'identidad' => array_intersect_key($registro['stat'],
                    array_flip(['dev', 'ino', 'mode', 'nlink', 'uid', 'gid', 'size'])),
                'sha256' => $registro['hash'], 'temporal' => $registro['temporal']
            ];
        }
        $directorios = [];
        foreach ($this->directorios as $ruta => $stat) {
            $directorios[] = ['ruta' => $ruta, 'identidad' => array_intersect_key($stat,
                array_flip(['dev', 'ino', 'mode', 'uid', 'gid']))];
        }
        return ['archivos' => $archivos, 'directorios' => $directorios];
    }

    /** Después de commit deja de ser elegible para compensación. */
    public function confirmar(): array {
        $resultado = $this->revertir(solo_temporales: true);
        // El commit ya ocurrió: los documentos nunca deben compensarse después.
        $this->creados = array_filter($this->creados, static fn(array $r): bool => $r['temporal']);
        return $resultado;
    }
}
