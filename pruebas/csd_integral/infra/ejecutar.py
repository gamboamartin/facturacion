import os,pathlib,subprocess,json,hashlib,sys,resource,signal,time,argparse
qa=pathlib.Path(__file__).resolve().parent
parser=argparse.ArgumentParser(description='Alta CSD sintética con MariaDB efímera sin red')
parser.add_argument('--engine-root', required=True, type=pathlib.Path)
parser.add_argument('--repo', type=pathlib.Path, default=qa.parents[2])
parser.add_argument('--deps', required=True, type=pathlib.Path)
parser.add_argument('--documento', required=True, type=pathlib.Path)
parser.add_argument('--plugins', required=True, type=pathlib.Path)
parser.add_argument('--ddl', required=True, type=pathlib.Path)
modo=parser.add_mutually_exclusive_group()
modo.add_argument('--solo-esquema', action='store_true')
modo.add_argument('--informacion-global', action='store_true')
modo.add_argument('--timbrado-masivo', action='store_true')
args=parser.parse_args()
deps=args.deps.resolve(strict=True)
documento=args.documento.resolve(strict=True)
plugins=args.plugins.resolve(strict=True)
ddl=args.ddl.resolve(strict=True)
repo=args.repo.resolve(strict=True)
engine=args.engine_root.resolve(strict=True)
for name,h in json.loads((qa/'engine-pins.json').read_text()).items():
    assert hashlib.sha256((engine/name).read_bytes()).hexdigest()==h
fuentes = [repo/'controllers/controlador_fc_csd.php', documento/'orm/doc_documento.php', plugins/'src/ssl.php', plugins/'src/Importador.php', ddl]
fuentes += sorted((repo/'orm').glob('*.php'))
fuentes += sorted((repo/'pruebas/csd_integral').rglob('*.php'))
if args.informacion_global:
    fuentes += [repo/'pruebas/informacion_global'/n for n in ['integracion_bd.php','ejecutar.py','registro.json']]
    fuentes += [repo/'tools/migrations/sql/fc_factura_informacion_global.sql', qa/'ejecutar.py', qa/'inner.py']
    fuentes += sorted((deps/'vendor/gamboa.martin/xml_cfdi_4/src').glob('*.php'))
    fuentes += sorted((deps/'vendor/gamboa.martin/administrador/base/orm').glob('*.php'))
if args.timbrado_masivo:
    fuentes += [p for p in sorted((repo/'pruebas/timbrado_masivo').glob('*')) if p.is_file()]
    fuentes += [repo/'tools/migrations/sql/fc_timbrado_masivo.sql', repo/'tools/migrations/_esquema_timbrado_masivo.php', qa/'ejecutar.py', qa/'inner.py']
hashes_previos = {str(f):hashlib.sha256(f.read_bytes()).hexdigest() for f in fuentes}
cmd=['/usr/bin/taskset','-c',','.join(map(str,sorted(os.sched_getaffinity(0))[:2])),'/usr/bin/timeout','--signal=TERM','--kill-after=5s','180s','/usr/bin/unshare','--user','--map-current-user','--net','--','/usr/bin/bwrap','--unshare-pid','--unshare-ipc','--unshare-uts','--die-with-parent','--new-session','--cap-drop','ALL','--ro-bind','/usr','/usr','--symlink','usr/lib','/lib','--symlink','usr/lib64','/lib64','--proc','/proc','--dev','/dev','--dir','/etc','--dir','/home','--dir','/var/lib','--dir','/var/log','--dir','/run','--size',str(1948*1024**2),'--tmpfs','/var/lib/mysql','--size',str(4*1024**2),'--tmpfs','/run/mysqld','--size',str(64*1024**2),'--tmpfs','/tmp','--size',str(32*1024**2),'--tmpfs','/var/log/mysql','--ro-bind',str(engine/'usr/sbin/mariadbd'),'/usr/sbin/mysqld','--ro-bind',str(engine/'usr/share/mysql'),'/usr/share/mysql','--ro-bind',str(qa/'empty-plugins'),'/usr/lib/mysql/plugin','--dir','/var/www/html/facturacion','--ro-bind',str(qa/'config'),'/var/www/html/facturacion/config','--size',str(64*1024**2),'--tmpfs','/var/www/html/facturacion/archivos','--ro-bind',str(qa),'/qa','--remount-ro','/dev','--remount-ro','/','/usr/bin/python3','-B','/qa/inner.py']
at=cmd.index('--remount-ro')
mounts=[]
for item in sorted(repo.iterdir()):
    if item.name in ['.git','config','archivos','vendor'] or item.name.startswith('.'):
        continue
    assert not item.is_symlink()
    mounts+=['--ro-bind',str(item),'/var/www/html/facturacion/'+item.name]
mounts += ['--ro-bind', str(deps/'vendor'), '/var/www/html/facturacion/vendor',
           '--ro-bind', str(documento/'orm/doc_documento.php'), '/var/www/html/facturacion/vendor/gamboa.martin/documento/orm/doc_documento.php',
           '--ro-bind', str(plugins/'src/ssl.php'), '/var/www/html/facturacion/vendor/gamboa.martin/plugins/src/ssl.php',
           '--ro-bind', str(plugins/'src/Importador.php'), '/var/www/html/facturacion/vendor/gamboa.martin/plugins/src/Importador.php',
           '--ro-bind', str(ddl), '/qa/esquema-csd-sanitizado.json',
           '--symlink', '/tmp/qa-instance-id', '/qa-instance-id',
           '--symlink', '/tmp/qa-credenciales-ficticias.json', '/qa-credenciales-ficticias.json']
cmd[at:at]=mounts

def limites():
    resource.setrlimit(resource.RLIMIT_AS,(2*1024**3,2*1024**3))
    resource.setrlimit(resource.RLIMIT_CPU,(150,150))
    resource.setrlimit(resource.RLIMIT_NOFILE,(512,512))
    resource.setrlimit(resource.RLIMIT_CORE,(0,0))
env={'PATH':'/usr/bin:/usr/sbin','HOME':'/tmp','LANG':'C','QA_OUTER_NET':os.readlink('/proc/self/ns/net'),'QA_SOLO_ESQUEMA':'1' if args.solo_esquema else '0','QA_INFORMACION_GLOBAL':'1' if args.informacion_global else '0','QA_TIMBRADO_MASIVO':'1' if args.timbrado_masivo else '0'}
p=subprocess.Popen(cmd,env=env,stdout=subprocess.PIPE,stderr=subprocess.PIPE,text=True,preexec_fn=limites,start_new_session=True)
stdout=stderr=''
try:
    stdout,stderr=p.communicate(timeout=190)
except subprocess.TimeoutExpired:
    os.killpg(p.pid,signal.SIGTERM)
    try: stdout,stderr=p.communicate(timeout=5)
    except subprocess.TimeoutExpired:
        os.killpg(p.pid,signal.SIGKILL)
        stdout,stderr=p.communicate(timeout=5)
finally:
    try: os.killpg(p.pid,signal.SIGTERM)
    except ProcessLookupError: pass
    if p.poll() is None:
        try: p.wait(timeout=3)
        except subprocess.TimeoutExpired:
            os.killpg(p.pid,signal.SIGKILL);p.wait(timeout=3)
remaining=[]
for proc in pathlib.Path('/proc').iterdir():
    if not proc.name.isdigit(): continue
    try:
        stat=(proc/'stat').read_text().split(') ',1)[1].split()
        if int(stat[2])==p.pid: remaining.append(int(proc.name))
    except (FileNotFoundError,ProcessLookupError,PermissionError): pass
netns=None
for line in stdout.splitlines():
    try:
        data=json.loads(line)
        if isinstance(data,dict): netns=data.get('netns',netns)
    except json.JSONDecodeError: pass
namespace_remaining=[]
namespace_unreadable=0
if netns:
    for proc in pathlib.Path('/proc').iterdir():
        if not proc.name.isdigit(): continue
        try:
            if os.readlink(proc/'ns/net')==netns: namespace_remaining.append(int(proc.name))
        except (FileNotFoundError,ProcessLookupError): pass
        except PermissionError: namespace_unreadable+=1
hashes_posteriores = {str(f):hashlib.sha256(f.read_bytes()).hexdigest() for f in fuentes}
drift = hashes_previos != hashes_posteriores
print(json.dumps({'fuentes_sha256_antes':hashes_previos, 'fuentes_sin_cambios_durante_ejecucion':not drift}))
print(stdout)
print(stderr,file=sys.stderr)
print(json.dumps({'namespace_revisado':netns,'procesos_namespace_restantes_visibles':namespace_remaining,'procesos_no_legibles':namespace_unreadable}))
print(json.dumps({'launcher_exit':p.returncode,'procesos_grupo_restantes':remaining,'host_sin_bind_escribible':True}))
sys.exit(p.returncode if not remaining and not drift else 2)
