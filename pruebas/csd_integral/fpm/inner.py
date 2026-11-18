import os,sys,json,pathlib,subprocess,time,hashlib,signal,xml.etree.ElementTree as ET
report={'estado':'BLOQUEADO','etapa':'aislamiento','pruebas_ejecutadas':False}
server=None
fpm=None
fpm_workers=[]
instance_id=str(int.from_bytes(os.urandom(4),'big')%999999998+1)
def detener(signum, frame):
    raise KeyboardInterrupt()
signal.signal(signal.SIGTERM, detener)
try:
    assert os.getuid()==1000
    assert len(os.sched_getaffinity(0))<=2
    assert os.readlink('/proc/self/ns/net') != os.environ['QA_OUTER_NET']
    assert not (pathlib.Path('/home').exists() and any(pathlib.Path('/home').iterdir())) and not pathlib.Path('/etc/mysql').exists()
    assert len(pathlib.Path('/proc/net/route').read_text().splitlines()) <= 1
    assert 'CapEff:\t0000000000000000' in pathlib.Path('/proc/self/status').read_text()
    mounts={line.split()[4]:line for line in pathlib.Path('/proc/self/mountinfo').read_text().splitlines()}
    for path in ['/var/lib/mysql','/run/mysqld','/var/log/mysql','/tmp','/var/www/html/facturacion/archivos']:
        assert ' - tmpfs tmpfs ' in mounts[path]
    for path in ['/','/dev','/var/www/html/facturacion/config','/var/www/html/facturacion/vendor']:
        assert 'ro' in mounts[path].split()[5].split(',')
    assert all(not list(pathlib.Path(p).iterdir()) for p in ['/var/lib/mysql','/run/mysqld','/var/log/mysql','/tmp'])
    for p in ['/var/lib/mysql','/run/mysqld','/var/log/mysql']:
        os.chmod(p,0o700)
    pathlib.Path('/tmp/mysql-temp').mkdir(mode=0o700)
    pathlib.Path('/var/lib/mysql/disabled-export').mkdir(mode=0o000)
    root=pathlib.Path('/var/www/html/facturacion')
    for nombre in ['doc_documento','temporales']:
        (root/'archivos'/nombre).mkdir(mode=0o700)
    pathlib.Path('/tmp/qa-instance-id').write_text(os.urandom(16).hex())
    credenciales={'base':'qa_csd_integral','usuario':'qa_csd_integral','password':'QA-EFIMERA-SIN-VALIDEZ'}
    pathlib.Path('/tmp/qa-credenciales-ficticias.json').write_text(json.dumps(credenciales))
    os.chmod('/tmp/qa-credenciales-ficticias.json',0o600)
    report['aislamiento_verificado']=True
    report['netns']=os.readlink('/proc/self/ns/net')
    common=['/usr/sbin/mysqld','--no-defaults','--datadir=/var/lib/mysql','--socket=/run/mysqld/mysqld.sock','--pid-file=/run/mysqld/mysqld.pid','--tmpdir=/tmp/mysql-temp','--skip-networking','--skip-log-bin','--event-scheduler=OFF','--performance-schema=OFF','--local-infile=OFF','--secure-file-priv=/var/lib/mysql/disabled-export/','--innodb-use-native-aio=OFF','--max-connections=20','--open-files-limit=256','--table-open-cache=32','--table-open-cache-instances=1','--innodb-open-files=64','--innodb-buffer-pool-size=67108864','--key-buffer-size=16777216','--aria-pagecache-buffer-size=16777216','--innodb-read-io-threads=1','--innodb-write-io-threads=2','--innodb-log-buffer-size=8388608','--innodb-log-file-size=67108864','--innodb-temp-data-file-path=ibtmp1:12M:autoextend:max:64M','--basedir=/usr','--lc-messages-dir=/usr/share/mysql','--plugin-dir=/usr/lib/mysql/plugin','--server-id='+instance_id]
    sql='CREATE DATABASE mysql;\nUSE mysql;\nSET @auth_root_socket=NULL;\n'+'\n'.join(pathlib.Path('/usr/share/mysql/'+p).read_text() for p in ['mysql_system_tables.sql','mysql_performance_tables.sql','mysql_system_tables_data.sql','fill_help_tables.sql','maria_add_gis_sp_bootstrap.sql','mysql_sys_schema.sql'])
    report['etapa']='inicializar_motor'
    engine_env={'PATH':'/usr/bin:/usr/sbin','HOME':'/tmp','LANG':'C','MALLOC_ARENA_MAX':'2'}
    init=subprocess.run(common+['--bootstrap'],input=sql,text=True,capture_output=True,timeout=75,env=engine_env)
    report['inicializador_exit']=init.returncode
    if init.returncode:
        report['diagnostico_inicializador']=init.stderr[-5000:]
        raise RuntimeError('Inicialización fallida')
    log=open('/var/log/mysql/qa.log','wb')
    server=subprocess.Popen(common,stdout=log,stderr=log,env=engine_env)
    client=['/usr/bin/mysql','--no-defaults','--protocol=SOCKET','--socket=/run/mysqld/mysqld.sock','--user=root','--batch','--skip-column-names']
    end=time.monotonic()+15
    while time.monotonic()<end:
        if server.poll() is not None: raise RuntimeError('Motor terminó')
        if pathlib.Path('/run/mysqld/mysqld.sock').exists():
            ping=subprocess.run(client+['-e','SELECT 1'],capture_output=True,text=True,timeout=2)
            if ping.returncode==0: break
        time.sleep(.2)
    else: raise RuntimeError('Socket no disponible')
    report['etapa']='base_nueva'
    q=subprocess.run(client+['-e',"CREATE DATABASE qa_csd_integral CHARACTER SET utf8mb4; CREATE USER 'qa_csd_integral'@'localhost' IDENTIFIED BY 'QA-EFIMERA-SIN-VALIDEZ'; GRANT ALL PRIVILEGES ON qa_csd_integral.* TO 'qa_csd_integral'@'localhost'; SELECT @@skip_networking,@@event_scheduler,@@local_infile; SHOW DATABASES;"],capture_output=True,text=True,timeout=5)
    assert q.returncode==0
    report['verificacion_base']=q.stdout.splitlines()
    report['etapa']='phpunit'
    report['estado_motor_antes']=[x for x in pathlib.Path('/proc/'+str(server.pid)+'/status').read_text().splitlines() if x.startswith(('VmSize:','VmRSS:','Threads:'))]
    php=['/usr/bin/php8.3','-n']
    for ext in ['mysqlnd','pdo','pdo_mysql','mbstring','dom','xml','xmlwriter','xmlreader','zip','iconv','fileinfo','tokenizer','ctype','simplexml']:
        php+=['-d','extension='+ext]
    php+=['-d','pdo_mysql.default_socket=/run/mysqld/mysqld.sock','-d','allow_url_fopen=0','-d','allow_url_include=0','-d','memory_limit=256M','-d','disable_functions=shell_exec,exec,system,passthru,proc_open,popen,fsockopen,pfsockopen,stream_socket_client,curl_exec,curl_multi_exec,socket_create,socket_connect,ftp_connect','-d','auto_prepend_file=/qa/bootstrap.php']
    env={'PATH':'/usr/bin','HOME':'/tmp','QA_DOCUMENTO_NETNS':'1','QA_HOST_NET_NS':os.environ['QA_OUTER_NET'],'OPENSSL_CONF':'/qa/openssl.cnf'}
    report['pasos']=[]
    guiones=['esquema.php','fpm/esquema_administrativo.php','precondiciones.php']
    for nombre in guiones:
        report['etapa']=nombre
        script=root/'pruebas/csd_integral'/nombre
        if not script.is_file(): raise RuntimeError('Falta guion integral '+nombre)
        paso=subprocess.run(php+[str(script)],cwd=root,env=env,capture_output=True,text=True,timeout=60)
        datos={'nombre':nombre,'exit':paso.returncode,'salida':paso.stdout,'stderr':paso.stderr}
        report['pasos'].append(datos)
        if paso.returncode != 0: raise RuntimeError('Guion integral bloqueado: '+nombre)
        salida=json.loads(paso.stdout)
        estados={'esquema.php':'DDL_SINTETICO_PREPARADO','fpm/esquema_administrativo.php':'DDL_ADMINISTRATIVO_FPM_PREPARADO','precondiciones.php':'METADATOS_PRESENTES_NO_PRUEBA_ALTA','escenario.php':'PASS'}
        assert salida.get('estado')==estados[nombre], 'Estado de guion no acreditado: '+nombre
        if nombre=='escenario.php':
            assert salida.get('estado')=='PASS', 'Escenario sin PASS explícito'
            assert salida.get('casos_ejecutados')==3, 'Se requieren los tres escenarios originales'
            report['pruebas_ejecutadas']=True
    # Prueba de infraestructura FPM únicamente. El controlador se integra en siguiente fase.
    os.chmod('/run/php-fpm',0o700)
    for carpeta in ['/tmp/fpm-qa','/tmp/fpm-qa/uploads','/tmp/fpm-qa/sessions']:
        pathlib.Path(carpeta).mkdir(mode=0o700)
    fpm_env={'PATH':'/usr/bin:/usr/sbin','HOME':'/tmp','LANG':'C','PHP_INI_SCAN_DIR':'',
             'QA_HOST_NET_NS':os.environ['QA_OUTER_NET'],'OPENSSL_CONF':'/qa/openssl.cnf','MALLOC_ARENA_MAX':'2'}
    fpm_log=open('/tmp/fpm-qa/proceso.log','wb')
    fpm=subprocess.Popen(['/usr/sbin/php-fpm8.3','--nodaemonize','--fpm-config','/qa-fpm/php-fpm.conf',
                          '--php-ini','/qa-fpm/php.ini'],env=fpm_env,stdout=fpm_log,stderr=fpm_log)
    fin=time.monotonic()+10
    while time.monotonic()<fin:
        if fpm.poll() is not None: raise RuntimeError('FPM terminó durante arranque')
        if pathlib.Path('/run/php-fpm/qa.sock').is_socket(): break
        time.sleep(.1)
    else: raise RuntimeError('Socket FPM no disponible')
    assert pathlib.Path('/run/php-fpm/qa.pid').read_text().strip()==str(fpm.pid)
    assert os.readlink('/proc/'+str(fpm.pid)+'/ns/net')==os.readlink('/proc/self/ns/net')
    assert hashlib.sha256(pathlib.Path('/proc/'+str(fpm.pid)+'/exe').read_bytes()).hexdigest()==hashlib.sha256(pathlib.Path('/usr/sbin/php-fpm8.3').read_bytes()).hexdigest()
    report['fpm_identidad_verificada']=True
    inicio_fpm=pathlib.Path('/proc/'+str(fpm.pid)+'/stat').read_text().split(') ',1)[1].split()[19]
    fpm_workers=pathlib.Path('/proc/'+str(fpm.pid)+'/task/'+str(fpm.pid)+'/children').read_text().split()
    respuesta=subprocess.run(['/usr/bin/python3','-B','/qa-fpm/cliente_fcgi.py','--script','sonda.php'],
        env={'PATH':'/usr/bin','QA_HOST_NET_NS':os.environ['QA_OUTER_NET']},capture_output=True,timeout=45)
    report['sonda_exit']=respuesta.returncode
    assert respuesta.returncode==0 and not respuesta.stderr, 'Cliente FastCGI falló'
    cabecera,contenido=respuesta.stdout.split(b'\r\n\r\n',1)
    assert b'Content-Type: application/json' in cabecera or b'Content-type: application/json' in cabecera
    sonda=json.loads(contenido)
    assert sonda['estado']=='SAPI_AISLADO_NO_PRUEBA_ALTA' and sonda['sapi']=='fpm-fcgi'
    assert sonda['comandos_externos_deshabilitados'] is True and sonda['url_fopen_deshabilitado'] is True
    report['sonda']=sonda
    report['estado']='FPM_SONDA_PASS_NO_PRUEBA_CONTROLADOR'
    if os.environ.get('QA_FPM_ALTA')=='1':
        report['etapa']='preparar_peticion_fpm'
        preparacion=subprocess.run(php+[str(root/'pruebas/csd_integral/fpm/preparar_peticion.php')],
            cwd=root,env=env,capture_output=True,text=True,timeout=40)
        assert preparacion.returncode==0 and not preparacion.stderr, 'Preparación de petición falló'
        peticion=json.loads(preparacion.stdout)
        assert peticion.get('estado')=='PETICION_SINTETICA_PREPARADA'
        assert peticion.get('body')=='/tmp/fpm-qa/peticion.body'
        assert pathlib.Path(peticion['body']).stat().st_size==peticion['bytes']
        report['peticion_sintetica_bytes']=peticion['bytes']
        report['etapa']='alta_controlador_fastcgi'
        respuesta=subprocess.run(['/usr/bin/python3','-B','/qa-fpm/cliente_fcgi.py','--script','alta_controlador.php',
            '--body',peticion['body'],'--content-type',peticion['content_type']],
            env={'PATH':'/usr/bin','QA_HOST_NET_NS':os.environ['QA_OUTER_NET']},capture_output=True,timeout=45)
        report['alta_fcgi_exit']=respuesta.returncode
        assert respuesta.returncode==0 and not respuesta.stderr, 'Cliente FastCGI de alta falló'
        cabecera,contenido=respuesta.stdout.split(b'\r\n\r\n',1)
        assert b'Content-Type: application/json' in cabecera or b'Content-type: application/json' in cabecera
        resultado=json.loads(contenido)
        report['alta_controlador']=resultado
        assert resultado.get('estado')=='PASS', 'Controlador no acreditó éxito'
        report['pruebas_ejecutadas']=True
        report['estado']='FPM_ALTA_CONTROLADOR_PASS_UN_CASO'


except Exception as e:
    report['estado']='BLOQUEADO'
    report['error']=str(e)
finally:
    if fpm is not None:
        try:
            if fpm.poll() is None:
                assert report.get('fpm_identidad_verificada') is True
                assert pathlib.Path('/proc/'+str(fpm.pid)+'/stat').read_text().split(') ',1)[1].split()[19]==inicio_fpm
                os.kill(fpm.pid, signal.SIGQUIT)
                fpm.wait(timeout=10)
            report['fpm_exit']=fpm.returncode
            report['fpm_workers_restantes']=[pid for pid in fpm_workers if pathlib.Path('/proc/'+pid).exists()]
            report['fpm_socket_retirado']=not pathlib.Path('/run/php-fpm/qa.sock').exists()
            assert fpm.returncode==0 and report['fpm_workers_restantes']==[] and report['fpm_socket_retirado']
        except Exception as error:
            report['estado']='BLOQUEADO'
            report['fpm_cleanup_error']=type(error).__name__
        for nombre in ['proceso.log','error.log','php-error.log']:
            p=pathlib.Path('/tmp/fpm-qa')/nombre
            if p.exists(): report['fpm_'+nombre]=p.read_text(errors='replace')[-4000:]
    if server is not None:
        try:
            if server.poll() is None:
                assert os.readlink('/proc/'+str(server.pid)+'/ns/net') == os.readlink('/proc/self/ns/net')
                assert pathlib.Path('/run/mysqld/mysqld.pid').read_text().strip() == str(server.pid)
                expected=json.loads(pathlib.Path('/qa/engine-pins.json').read_text())['usr/sbin/mariadbd']
                assert hashlib.sha256(pathlib.Path('/proc/'+str(server.pid)+'/exe').read_bytes()).hexdigest()==expected
                shutdown_php="""$p=new PDO('mysql:unix_socket=/run/mysqld/mysqld.sock','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$r=$p->query('SELECT CAST(@@server_id AS CHAR),@@datadir,@@socket')->fetch(PDO::FETCH_NUM);
if($r!==[$argv[1],'/var/lib/mysql/','/run/mysqld/mysqld.sock']){exit(3);}
echo 'identidad_verificada'.PHP_EOL;
$p->exec('SHUTDOWN');
echo 'shutdown_enviado'.PHP_EOL;"""
                shutdown=subprocess.run(['/usr/bin/php8.3','-n','-d','extension=mysqlnd','-d','extension=pdo','-d','extension=pdo_mysql','-r',shutdown_php,instance_id],capture_output=True,text=True,timeout=15)
                report['shutdown_exit']=shutdown.returncode
                report['shutdown_identidad_verificada']='identidad_verificada' in shutdown.stdout
                report['shutdown_enviado']='shutdown_enviado' in shutdown.stdout
                assert shutdown.returncode==0 and report['shutdown_identidad_verificada'] and report['shutdown_enviado']
                server.wait(timeout=15)
            report['motor_detenido']=server.poll() is not None
            report['motor_exit']=server.returncode
        except Exception as e:
            report['estado']='BLOQUEADO'
            report['cleanup_error']=type(e).__name__
            report['motor_detenido']=server.poll() is not None
            # No señales al proceso confinado. La destrucción del namespace es la frontera final.
    if pathlib.Path('/var/log/mysql/qa.log').is_file():
        report['log_motor_final']=pathlib.Path('/var/log/mysql/qa.log').read_text(errors='replace')[-4000:]
    print(json.dumps(report,ensure_ascii=False))
    sys.exit(0 if report['estado'] in ['FPM_SONDA_PASS_NO_PRUEBA_CONTROLADOR','FPM_ALTA_CONTROLADOR_PASS_UN_CASO'] else 1)
