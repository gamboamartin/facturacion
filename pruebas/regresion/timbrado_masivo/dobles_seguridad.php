<?php
declare(strict_types=1);

final class SeguridadMasivaStatement extends PDOStatement
{
    public function __construct(private SeguridadMasivaPDO $pdo) {}
    public function execute(?array $params = null): bool {$this->pdo->parametros=$params;return true;}
    public function fetchColumn(int $column = 0): mixed {return $this->pdo->permisos;}
    public function fetchAll(int $mode = PDO::FETCH_DEFAULT, mixed ...$args): array {return [];}
}
final class SeguridadMasivaPDO extends PDO
{
    public int $permisos=0;
    public array $consultas=[];
    public array $parametros=[];
    public function __construct() {}
    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        $this->consultas[]=$query;
        return new SeguridadMasivaStatement($this);
    }
    public function query(string $query, ?int $fetchMode = null, mixed ...$args): PDOStatement|false
    {
        if (!str_contains($query,'FROM org_empresa')) {throw new RuntimeException('SQL no autorizado en el doble.');}
        $this->consultas[]=$query;
        return new SeguridadMasivaStatement($this);
    }
}
final class SeguridadMasivaLinks
{
    public function link_sin_id(string $accion, PDO $link, string $seccion): string {return '/index.php?seccion='.$seccion.'&accion='.$accion;}
}
final class SeguridadMasivaControlador
{
    use \gamboamartin\facturacion\controllers\_timbrado_masivo {permiso_timbrado_masivo as public permiso;}
    public SeguridadMasivaLinks $obj_link;
    public function __construct(public PDO $link) {$this->obj_link=new SeguridadMasivaLinks();}
}
final class SeguridadMasivaErrores {public static bool $error=false;public static array $out=[];}
if (!class_exists(\gamboamartin\errores\errores::class,false)) {
    class_alias(SeguridadMasivaErrores::class,\gamboamartin\errores\errores::class);
}
