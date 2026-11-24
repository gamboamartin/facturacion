<?php
declare(strict_types=1);

/** Esquema y datos exclusivamente sintéticos, para el namespace SQL efímero. */
function conexion_cp():PDO {
    return new PDO('mysql:unix_socket=/run/mysqld/mysqld.sock;dbname=qa_csd_integral;charset=utf8mb4',
        'qa_csd_integral','QA-EFIMERA-SIN-VALIDEZ',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false]);
}
function q_cp(string $sql,array $datos=[]):PDOStatement {global $pdo;$q=$pdo->prepare($sql);$q->execute($datos);return $q;}
function esquema_cp():array {
    global $pdo;
    $tablas=[
        'org_empresa'=>'id BIGINT PRIMARY KEY,rfc VARCHAR(30),razon_social VARCHAR(255),cat_sat_regimen_fiscal_id BIGINT',
        'org_sucursal'=>'id BIGINT PRIMARY KEY,org_empresa_id BIGINT,dp_calle_pertenece_id BIGINT',
        'fc_csd'=>'id BIGINT PRIMARY KEY,org_sucursal_id BIGINT,status VARCHAR(24),descripcion_select VARCHAR(255)',
        'com_cliente'=>'id BIGINT PRIMARY KEY,rfc VARCHAR(30),razon_social VARCHAR(255)',
        'com_sucursal'=>'id BIGINT PRIMARY KEY,com_cliente_id BIGINT',
        'cat_sat_moneda'=>'id BIGINT PRIMARY KEY,codigo VARCHAR(10),descripcion VARCHAR(100),status VARCHAR(24)',
        'cat_sat_metodo_pago'=>'id BIGINT PRIMARY KEY,codigo VARCHAR(10),status VARCHAR(24)',
        'cat_sat_forma_pago'=>'id BIGINT PRIMARY KEY,codigo VARCHAR(10),descripcion VARCHAR(100),status VARCHAR(24)',
        'cat_sat_uso_cfdi'=>'id BIGINT PRIMARY KEY,codigo VARCHAR(10),status VARCHAR(24)',
        'cat_sat_tipo_de_comprobante'=>'id BIGINT PRIMARY KEY,codigo VARCHAR(10),status VARCHAR(24)',
        'com_tipo_cambio'=>'id BIGINT PRIMARY KEY,cat_sat_moneda_id BIGINT,fecha DATE,monto DECIMAL(20,6),status VARCHAR(24)',
        'fc_factura'=>'id BIGINT PRIMARY KEY,folio VARCHAR(255),serie VARCHAR(255),folio_fiscal VARCHAR(64),etapa VARCHAR(40),status VARCHAR(24),fc_csd_id BIGINT,com_sucursal_id BIGINT,cat_sat_metodo_pago_id BIGINT,cat_sat_moneda_id BIGINT,com_tipo_cambio_id BIGINT,total DECIMAL(20,2),saldo DECIMAL(20,2),monto_pago_cp DECIMAL(20,2) DEFAULT 0,monto_pago_nc DECIMAL(20,2) DEFAULT 0,monto_saldo_aplicado DECIMAL(20,2) DEFAULT 0',
        'pr_etapa'=>'id BIGINT PRIMARY KEY,descripcion VARCHAR(100)',
        'pr_etapa_proceso'=>'id BIGINT PRIMARY KEY,pr_etapa_id BIGINT',
        'fc_factura_etapa'=>'id BIGINT AUTO_INCREMENT PRIMARY KEY,fc_factura_id BIGINT,pr_etapa_proceso_id BIGINT',
        'fc_cfdi_sellado'=>'id BIGINT AUTO_INCREMENT PRIMARY KEY,fc_factura_id BIGINT,uuid VARCHAR(64)',
        'fc_complemento_pago'=>'id BIGINT AUTO_INCREMENT PRIMARY KEY,codigo VARCHAR(200) UNIQUE,codigo_bis VARCHAR(200),fc_csd_id BIGINT,com_sucursal_id BIGINT,status VARCHAR(24),aplica_saldo VARCHAR(24)',
        'fc_pago'=>'id BIGINT AUTO_INCREMENT PRIMARY KEY,fc_complemento_pago_id BIGINT',
        'fc_pago_pago'=>'id BIGINT AUTO_INCREMENT PRIMARY KEY,fc_pago_id BIGINT,fecha_pago VARCHAR(40),cat_sat_forma_pago_id BIGINT,com_tipo_cambio_id BIGINT,cat_sat_moneda_id BIGINT,monto DECIMAL(20,2)',
        'fc_docto_relacionado'=>'id BIGINT AUTO_INCREMENT PRIMARY KEY,fc_factura_id BIGINT,fc_pago_pago_id BIGINT,imp_pagado DECIMAL(20,2),status VARCHAR(24)',
        'fc_nota_credito'=>'id BIGINT PRIMARY KEY,aplica_saldo VARCHAR(24)',
        'fc_relacion_nc'=>'id BIGINT PRIMARY KEY,fc_nota_credito_id BIGINT',
        'fc_nc_rel'=>'id BIGINT AUTO_INCREMENT PRIMARY KEY,fc_factura_id BIGINT,fc_relacion_nc_id BIGINT,monto_aplicado_factura DECIMAL(20,2)',
        'qa_callback'=>'id BIGINT AUTO_INCREMENT PRIMARY KEY,cabecera TEXT,pago TEXT,docto TEXT',
        'adm_grupo'=>'id BIGINT PRIMARY KEY,status VARCHAR(24)',
        'adm_usuario'=>'id BIGINT PRIMARY KEY,adm_grupo_id BIGINT,status VARCHAR(24)',
        'adm_seccion'=>'id BIGINT PRIMARY KEY,descripcion VARCHAR(100),status VARCHAR(24)',
        'adm_accion'=>"id BIGINT AUTO_INCREMENT PRIMARY KEY,adm_seccion_id BIGINT,descripcion VARCHAR(100),etiqueta_label VARCHAR(100) DEFAULT '',titulo VARCHAR(100) DEFAULT '',visible VARCHAR(24) DEFAULT 'activo',status VARCHAR(24) DEFAULT 'activo',seguridad VARCHAR(24) DEFAULT 'activo',es_view VARCHAR(24) DEFAULT 'activo',es_modal VARCHAR(24) DEFAULT 'inactivo',es_status VARCHAR(24) DEFAULT 'inactivo',es_lista VARCHAR(24) DEFAULT 'activo',inicio VARCHAR(24) DEFAULT 'inactivo',lista VARCHAR(24) DEFAULT 'activo',icono VARCHAR(100) DEFAULT '',codigo VARCHAR(200) UNIQUE,codigo_bis VARCHAR(200) UNIQUE,descripcion_select VARCHAR(200) DEFAULT '',alias VARCHAR(100) DEFAULT '',target VARCHAR(100) DEFAULT '',usuario_alta_id BIGINT DEFAULT 1,usuario_update_id BIGINT DEFAULT 1,fecha_alta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,fecha_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
        'adm_accion_grupo'=>"id BIGINT AUTO_INCREMENT PRIMARY KEY,adm_accion_id BIGINT,adm_grupo_id BIGINT,status VARCHAR(24),usuario_alta_id BIGINT DEFAULT 1,usuario_update_id BIGINT DEFAULT 1,codigo VARCHAR(200) UNIQUE,codigo_bis VARCHAR(200) UNIQUE,descripcion VARCHAR(200) DEFAULT '',descripcion_select VARCHAR(200) DEFAULT '',predeterminado VARCHAR(24) DEFAULT 'inactivo'",
    ];
    foreach($tablas as $t=>$campos)$pdo->exec('CREATE TABLE '.$t.' ('.$campos.') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    q_cp("INSERT INTO org_empresa VALUES(1,'AAA010101AAA','EMISOR FICTICIO UNO',1),(2,'BBB010101BBB','EMISOR FICTICIO DOS',1)");
    q_cp('INSERT INTO org_sucursal VALUES(1,1,1),(2,2,2)');q_cp("INSERT INTO fc_csd VALUES(1,1,'activo','CSD FICTICIO UNO'),(2,2,'activo','CSD FICTICIO DOS'),(3,1,'activo','CSD RENOVADO MISMO EMISOR')");
    q_cp("INSERT INTO com_cliente VALUES(1,'CCC010101CCC','CLIENTE FICTICIO UNO'),(2,'DDD010101DDD','CLIENTE FICTICIO DOS')");q_cp('INSERT INTO com_sucursal VALUES(1,1),(2,2)');
    q_cp("INSERT INTO cat_sat_moneda VALUES(1,'MXN','Peso ficticio','activo'),(2,'USD','Dólar ficticio','activo'),(3,'XXX','Sin moneda','activo')");
    q_cp("INSERT INTO cat_sat_metodo_pago VALUES(1,'PPD','activo'),(2,'PUE','activo')");
    q_cp("INSERT INTO cat_sat_forma_pago VALUES(1,'03','Transferencia ficticia','activo'),(2,'99','Por definir','activo'),(3,'01','Efectivo ficticio','inactivo')");
    q_cp("INSERT INTO cat_sat_uso_cfdi VALUES(1,'CP01','activo')");q_cp("INSERT INTO cat_sat_tipo_de_comprobante VALUES(1,'P','activo')");
    $hoy=date('Y-m-d');q_cp("INSERT INTO com_tipo_cambio VALUES(1,1,?,1,'activo'),(2,2,?,20,'activo'),(3,3,?,1,'activo')",[$hoy,$hoy,$hoy]);
    q_cp("INSERT INTO adm_grupo VALUES(1,'activo'),(2,'activo'),(3,'inactivo'),(4,'activo')");q_cp("INSERT INTO adm_usuario VALUES(1,1,'activo'),(2,2,'activo')");
    q_cp("INSERT INTO adm_seccion VALUES(1,'fc_factura','activo'),(2,'fc_complemento_pago','activo')");
    q_cp("INSERT INTO adm_accion(id,adm_seccion_id,descripcion,codigo,codigo_bis) VALUES(1,1,'lista','lista.fc','lista.fc'),(2,1,'modifica','modifica.fc','modifica.fc'),(3,2,'alta_bd','alta.cp','alta.cp'),(4,2,'modifica','modifica.cp','modifica.cp')");
    foreach([1,2,3,4] as $g)foreach([1,3,4] as $accion){if($g===2&&$accion===4)continue;$status=$g===4&&$accion===4?'inactivo':'activo';$codigo='QA.'.$g.'.'.$accion;q_cp('INSERT INTO adm_accion_grupo(adm_accion_id,adm_grupo_id,status,codigo,codigo_bis) VALUES(?,?,?,?,?)',[$accion,$g,$status,$codigo,$codigo]);}
    return array_keys($tablas);
}
function factura_cp(int $id=1,array $cambios=[]):void {
    $fila=array_replace(['id'=>$id,'folio'=>'QA-'.sprintf('%06d',$id),'serie'=>'QA','folio_fiscal'=>sprintf('00000000-0000-4000-8000-%012d',$id),'etapa'=>'TIMBRADO','status'=>'activo','fc_csd_id'=>1,'com_sucursal_id'=>1,'cat_sat_metodo_pago_id'=>1,'cat_sat_moneda_id'=>1,'com_tipo_cambio_id'=>1,'total'=>'116.00','saldo'=>'116.00'],$cambios);
    q_cp('INSERT INTO fc_factura('.implode(',',array_keys($fila)).') VALUES('.implode(',',array_fill(0,count($fila),'?')).')',array_values($fila));
    q_cp('INSERT INTO fc_cfdi_sellado(fc_factura_id,uuid) VALUES(?,?)',[$id,$fila['folio_fiscal']]);
}
function reiniciar_cp():void {global $llamadas_cp,$permitido_cp;$llamadas_cp=0;$permitido_cp=true;foreach(['fc_factura_etapa','pr_etapa_proceso','pr_etapa','qa_callback','fc_docto_relacionado','fc_pago_pago','fc_pago','fc_complemento_pago','fc_nc_rel','fc_relacion_nc','fc_nota_credito','fc_cfdi_sellado','fc_factura'] as $t)q_cp('DELETE FROM '.$t);}
function estructura_cp(array $cabecera,array $pago,array $docto):int {
    global $pdo,$llamadas_cp;$llamadas_cp++;
    q_cp('INSERT INTO qa_callback(cabecera,pago,docto) VALUES(?,?,?)',array_map(static fn(array $v):string=>json_encode($v,JSON_THROW_ON_ERROR),[$cabecera,$pago,$docto]));
    q_cp('INSERT INTO fc_complemento_pago(codigo,codigo_bis,fc_csd_id,com_sucursal_id,status,aplica_saldo) VALUES(?,?,?,?,\'activo\',\'activo\')',[$cabecera['codigo'],$cabecera['codigo_bis'],$cabecera['fc_csd_id'],$cabecera['com_sucursal_id']]);$cp=(int)$pdo->lastInsertId();
    q_cp('INSERT INTO fc_pago(fc_complemento_pago_id) VALUES(?)',[$cp]);$p=(int)$pdo->lastInsertId();
    q_cp('INSERT INTO fc_pago_pago(fc_pago_id,fecha_pago,cat_sat_forma_pago_id,com_tipo_cambio_id,cat_sat_moneda_id,monto) VALUES(?,?,?,?,?,?)',[$p,$pago['fecha_pago'],$pago['cat_sat_forma_pago_id'],$pago['com_tipo_cambio_id'],$pago['cat_sat_moneda_id'],$pago['monto']]);$pp=(int)$pdo->lastInsertId();
    q_cp("INSERT INTO fc_docto_relacionado(fc_factura_id,fc_pago_pago_id,imp_pagado,status) VALUES(?,?,?,'activo')",[$docto['fc_factura_id'],$pp,$docto['imp_pagado']]);
    return $cp;
}
