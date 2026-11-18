<?php
declare(strict_types=1);
namespace gamboamartin\facturacion\models;

/** Contexto opt-in por solicitud; no se activa en otras operaciones documentales. */
final class contexto_archivos_csd {
    private static ?self $actual = null;
    private archivos_csd_operacion $journal;
    private string $temporales;
    private array $identidad_temporales;

    private function __construct(string $base) {
        $documentos = rtrim($base, '/') . '/archivos/doc_documento';
        $this->temporales = rtrim($base, '/') . '/archivos/temporales';
        $this->journal = new archivos_csd_operacion([$documentos, $this->temporales]);
        $this->identidad_temporales = @lstat($this->temporales);
    }
    public static function iniciar(string $path_base): self {
        if (!method_exists(\gamboamartin\documento\models\doc_documento::class, 'guardar_archivo_alta')) {
            throw new \RuntimeException('Dependencia documento sin soporte de escritura CSD privada');
        }
        if (self::$actual !== null) { throw new \RuntimeException('Contexto CSD ya activo'); }
        return self::$actual = new self($path_base);
    }
    public static function actual(): ?self { return self::$actual; }
    public function crear_documento(string $ruta, string $contenido): void {
        // Mantiene la normalización histórica del escritor documental.
        $this->journal->crear(strtolower($ruta), $contenido);
    }
    public function convertir_temporal(callable $generar): array {
        return $this->journal->generar_temporal($this->temporales, $generar);
    }
    public function revertir(): array { return $this->journal->revertir(); }
    public function confirmar(): array { return $this->journal->confirmar(); }
    /** Conserva evidencia privada, sin autorizar ninguna eliminación automática posterior. */
    public function preservar_recuperacion(string $estado): array {
        if (!in_array($estado, ['commit_incierto', 'rollback_incierto', 'rollback_confirmado', 'commit_confirmado'], true)) {
            return ['guardado' => false, 'id' => null, 'codigo' => 'estado_no_admitido'];
        }
        $id = bin2hex(random_bytes(16));
        $directorio = $this->temporales . '/recuperacion-csd-' . $id;
        $archivo = null;
        try {
            for ($padre = $this->temporales; ; $padre = dirname($padre)) {
                if (is_link($padre)) { throw new \RuntimeException('Directorio cambiado'); }
                if ($padre === dirname($padre)) { break; }
            }
            clearstatcache(true, $this->temporales);
            $actual = @lstat($this->temporales);
            if ($actual === false || $actual['dev'] !== $this->identidad_temporales['dev']
                || $actual['ino'] !== $this->identidad_temporales['ino']) {
                throw new \RuntimeException('Directorio cambiado');
            }
            // Este directorio es nuevo y privado; nunca se reutiliza un recibo existente.
            if (!@mkdir($directorio, 0700)) { throw new \RuntimeException('Reserva fallida'); }
            $archivo = @fopen($directorio . '/recibo.json', 'x+b');
            if ($archivo === false || !@chmod($directorio . '/recibo.json', 0600)
                || (@fstat($archivo)['mode'] & 0777) !== 0600) {
                throw new \RuntimeException('Protección fallida');
            }
            $datos = [
                'version' => 1, 'id' => $id, 'estado_sql' => $estado,
                'eliminacion_automatica_autorizada' => false,
                'evidencia' => $this->journal->evidencia_pendiente()
            ];
            $json = json_encode($datos, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
            if (@fwrite($archivo, $json) !== strlen($json) || !@fflush($archivo)) {
                throw new \RuntimeException('Registro incompleto');
            }
            @fclose($archivo);
            return ['guardado' => true, 'id' => $id, 'codigo' => 'recuperacion_privada_registrada'];
        } catch (\Throwable) {
            if (is_resource($archivo)) { @fclose($archivo); }
            return ['guardado' => false, 'id' => $id, 'codigo' => 'recuperacion_no_acreditada'];
        }
    }

    public function finalizar(): void {
        if (self::$actual === $this) { self::$actual = null; }
    }
}
