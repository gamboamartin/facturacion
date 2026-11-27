<?php
declare(strict_types=1);
namespace gamboamartin\errores {
    class errores {
        public static bool $error = false;
        public function error(string $mensaje, mixed $data): array {
            self::$error = true; return ['error'=>true,'mensaje'=>$mensaje,'data'=>$data];
        }
    }
}
namespace base\orm {
    class modelo {
        public \PDO $link; public string $tabla; public string $key_id; public array $registro=[];
        public \gamboamartin\errores\errores $error;
        public function __construct(\PDO $link, string $tabla) {
            $this->link=$link; $this->tabla=$tabla; $this->key_id=$tabla.'_id';
            $this->error=new \gamboamartin\errores\errores();
        }
        public function registro(int $registro_id, bool $retorno_obj=true): \stdClass|array {
            $q=$this->link->prepare('SELECT folio FROM `'.$this->tabla.'` WHERE id=?'); $q->execute([$registro_id]);
            return (object)[$this->tabla.'_folio'=>$q->fetchColumn()];
        }
        public function modifica_bd(array $registro,int $id,bool $verifica_permite_transaccion=true): \stdClass|array {
            $q=$this->link->prepare('UPDATE `'.$this->tabla.'` SET etapa=? WHERE id=?'); $q->execute([$registro['etapa'],$id]);
            return (object)['registro_id'=>$id];
        }
    }
    class _modelo_parent_sin_codigo extends modelo {
        public function alta_bd(array $keys_integra_ds=['descripcion']): array|\stdClass {
            $r=$this->registro; if ($GLOBALS['qa_forzar_fk']??false) $r['pr_etapa_proceso_id']=999; $r['codigo']??=$r['descripcion']; $r['codigo_bis']??=$r['codigo'];
            $r['descripcion_select']??=$r['descripcion'];
            try {
                $q=$this->link->prepare('INSERT INTO `'.$this->tabla.'` (`'.implode('`,`',array_keys($r)).'`) VALUES ('.implode(',',array_fill(0,count($r),'?')).')');
                $q->execute(array_values($r)); return (object)['registro_id'=>(int)$this->link->lastInsertId(),'registro'=>$r];
            } catch (\PDOException $e) { return $this->error->error('Fallo SQL de fixture', ['sqlstate'=>$e->getCode()]); }
        }
    }
}
namespace gamboamartin\proceso\models {
    class pr_etapa_proceso {
        public function __construct(private \PDO $link) {}
        public function registro(int $registro_id,bool $retorno_obj): \stdClass|array {
            $q=$this->link->prepare('SELECT t.descripcion AS pr_etapa_descripcion FROM pr_etapa_proceso p JOIN pr_etapa t ON t.id=p.pr_etapa_id WHERE p.id=?');
            $q->execute([$registro_id]); $r=$q->fetch(\PDO::FETCH_ASSOC);
            if (!$r) return (new \gamboamartin\errores\errores())->error('Etapa inexistente de fixture', []);
            return (object)(['pr_proceso_descripcion'=>'PROCESO QA']+$r);
        }
    }
    class pr_proceso {
        public function __construct(\PDO $link) {}
        public function inserta_etapa(string $adm_accion,string $fecha,\base\orm\modelo $modelo,
            \base\orm\modelo $modelo_etapa,int $registro_id): array|\stdClass {
            if ($adm_accion !== 'cancela_bd') throw new \RuntimeException('Acción de proceso incompatible.');
            $modelo_etapa->registro=[$modelo->key_id=>$registro_id,'pr_etapa_proceso_id'=>9,'fecha'=>'2026-11-26'];
            if (isset($GLOBALS['qa_etapa_codigo'])) $modelo_etapa->registro['codigo']=$GLOBALS['qa_etapa_codigo'];
            return $modelo_etapa->alta_bd();
        }
    }
}
namespace gamboamartin\facturacion\models {
    require dirname(__DIR__,2).'/orm/_transacciones_fc.php';
    require dirname(__DIR__,2).'/orm/_etapa.php';
    // Sustituye exclusivamente adaptador PAC y persistencia de solicitud: nunca carga certificados ni red.
    class _cancelacion extends \base\orm\modelo {
        public bool $fallar=false;
        public function alta_registro(array $registro): array|\stdClass {
            // Evidencia externa a la transacción SQL: un rollback no borra un envío al PAC.
            if (file_put_contents(getenv('QA_CANCELACION_PAC_LOG'), "envio\n", FILE_APPEND|LOCK_EX)!==6) {
                throw new \RuntimeException('No se pudo registrar llamada PAC ficticia.');
            }
            if ($this->fallar) return $this->error->error('Rechazo PAC ficticio', []);
            if (getenv('QA_CANCELACION_WORKER')) usleep(300000);
            $q=$this->link->prepare('INSERT INTO `'.$this->tabla.'` (`'.array_key_first($registro).'`) VALUES (?)');
            $q->execute([array_values($registro)[0]]);
            return (object)['registro_id'=>(int)$this->link->lastInsertId()];
        }
    }
    class DocumentoQA extends _transacciones_fc {
        public function __construct(\PDO $link,string $tabla) { \base\orm\modelo::__construct($link,$tabla); }
        public function modifica_bd(array $registro,int $id,bool $reactiva=false,bool $verifica_permite_transaccion=true): array|\stdClass {
            return \base\orm\modelo::modifica_bd($registro,$id,$verifica_permite_transaccion);
        }
    }
    class EtapaQA extends _etapa {
        public function __construct(\PDO $link,DocumentoQA $documento) {
            parent::__construct($link,$documento->tabla.'_etapa'); $this->modelo_entidad=$documento;
        }
    }
}
namespace {
    function conexion_qa(): PDO {
        $p=new PDO(getenv('QA_CANCELACION_DSN'),getenv('QA_CANCELACION_DB_USER'),getenv('QA_CANCELACION_DB_PASSWORD'),
            [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false]);
        $db=(string)$p->query('SELECT DATABASE()')->fetchColumn();
        if (!str_starts_with($db,'qa_')) throw new RuntimeException('Requiere base exclusiva de QA.');
        return $p;
    }
    function modelos_qa(PDO $p,string $tabla='fc_factura'): array {
        $d=new \gamboamartin\facturacion\models\DocumentoQA($p,$tabla);
        return [$d,new \gamboamartin\facturacion\models\EtapaQA($p,$d),
            new \gamboamartin\facturacion\models\_cancelacion($p,$tabla.'_cancelacion')];
    }
}
