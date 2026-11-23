#!/usr/bin/env python3
"""Integración de información global en el laboratorio efímero y sin red."""
from __future__ import annotations

import datetime
import hashlib
import json
import os
from pathlib import Path
import signal
import subprocess
import sys
import tempfile

ROOT = Path(__file__).resolve().parents[2]
PARAMETROS = {'--engine-root':'QA_CSD_ENGINE_ROOT', '--deps':'QA_CSD_DEPS',
              '--documento':'QA_CSD_DOCUMENTO', '--plugins':'QA_CSD_PLUGINS', '--ddl':'QA_CSD_DDL'}

def exigir(condicion: bool, codigo: str) -> None:
    if not condicion:
        raise RuntimeError(codigo)

def uno(registros: list[dict], clave: str) -> dict:
    encontrados = [r for r in registros if clave in r]
    exigir(len(encontrados) == 1, 'evidencia_unica:' + clave)
    return encontrados[0]

def guardar(path: Path, datos: dict) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    with tempfile.NamedTemporaryFile('w',dir=path.parent,delete=False,encoding='utf-8') as archivo:
        json.dump(datos,archivo,ensure_ascii=False,indent=2)
        nombre=archivo.name
    os.replace(nombre,path)

def comprobar(stdout: str, codigo: int, rutas: dict[str,Path], registro: dict) -> dict:
    exigir(codigo == 0, 'infra_exit_no_cero')
    lineas=[json.loads(linea) for linea in stdout.splitlines() if linea.strip()]
    hashes=uno(lineas,'fuentes_sha256_antes')
    motor=uno(lineas,'aislamiento_verificado')
    namespace=uno(lineas,'namespace_revisado')
    launcher=uno(lineas,'launcher_exit')
    exigir(hashes.get('fuentes_sin_cambios_durante_ejecucion') is True,'fuentes_modificadas')
    exigir(motor.get('estado') == 'PASS' and motor.get('pruebas_ejecutadas') is True,'motor_sin_pass')
    for clave in ['aislamiento_verificado','shutdown_identidad_verificada','shutdown_enviado','motor_detenido']:
        exigir(motor.get(clave) is True,'falta_'+clave)
    exigir(motor.get('shutdown_exit') == 0 and motor.get('motor_exit') == 0,'shutdown_fallido')
    exigir(bool(motor.get('netns')) and namespace.get('namespace_revisado') == motor['netns'],'namespace_distinto')
    exigir(namespace.get('procesos_namespace_restantes_visibles') == [],'procesos_namespace_restantes')
    exigir(launcher.get('launcher_exit') == 0 and launcher.get('procesos_grupo_restantes') == [],'launcher_incompleto')
    exigir(launcher.get('host_sin_bind_escribible') is True,'host_escribible')
    pasos=motor.get('pasos',[])
    exigir(len(pasos) == 1 and pasos[0].get('nombre') == 'informacion_global/integracion_bd.php','secuencia_incorrecta')
    paso=pasos[0]
    exigir(paso.get('exit') == 0 and paso.get('stderr') == '','guion_con_error')
    resultado=json.loads(paso['salida'])
    exigir(resultado.get('estado') == 'PASS_INFORMACION_GLOBAL_BD','integracion_sin_pass')
    exigir(resultado.get('casos') == registro['casos'] and resultado.get('casos_ejecutados') == len(registro['casos']),'identidades_incorrectas')
    exigir(resultado.get('rollback_confirmado') is True and resultado.get('filas_restantes') == 0,'rollback_incompleto')
    exigir(resultado.get('fallo') is None and resultado.get('salida_interna_presente') is False,'salida_o_fallo_interno')
    fuentes=resultado.get('fuentes',{})
    exigir(len(fuentes) == 6,'clases_reales_incompletas')
    for fuente in fuentes.values():
        prefijo='/var/www/html/facturacion/'
        exigir(fuente['archivo'].startswith(prefijo),'fuente_fuera_runtime')
        relativo=Path(fuente['archivo'][len(prefijo):])
        exigir('..' not in relativo.parts,'ruta_invalida')
        local=(rutas['--deps'] if relativo.parts[0] == 'vendor' else ROOT)/relativo
        digest=hashes['fuentes_sha256_antes'].get(str(local))
        exigir(digest is not None and digest == fuente['sha256'],'fuente_reflection_distinta')
        exigir(hashlib.sha256(local.read_bytes()).hexdigest() == digest,'fuente_cambio_despues')
    for relativo in ['pruebas/informacion_global/integracion_bd.php','pruebas/informacion_global/ejecutar.py',
                     'pruebas/informacion_global/registro.json','tools/migrations/sql/fc_factura_informacion_global.sql',
                     'pruebas/csd_integral/infra/ejecutar.py','pruebas/csd_integral/infra/inner.py']:
        path=ROOT/relativo
        exigir(hashes['fuentes_sha256_antes'].get(str(path)) == hashlib.sha256(path.read_bytes()).hexdigest(),'fuente_harness_no_acreditada')
    return {'casos':resultado['casos'],'casos_ejecutados':resultado['casos_ejecutados'],
            'aserciones':resultado['aserciones'],'hashes_clases_cargadas':fuentes,
            'aislamiento_verificado':True,'shutdown_verificado':True,'rollback_verificado':True,
            'procesos_namespace_visibles':[],'procesos_proc_no_legibles':namespace.get('procesos_no_legibles'),
            'alcance':resultado['alcance']}

def main() -> int:
    resumen={'suite':'informacion_global_integracion','tipo':'integracion','obligatoria':True,
             'estado':'BLOQUEADO','fecha_utc':datetime.datetime.now(datetime.timezone.utc).isoformat()}
    proceso=None
    evidencia=None
    try:
        registro=json.loads((ROOT/'pruebas/informacion_global/registro.json').read_text())
        rutas={}
        for argumento,variable in PARAMETROS.items():
            exigir(bool(os.environ.get(variable)),'recurso_no_configurado:'+variable)
            rutas[argumento]=Path(os.environ[variable]).resolve(strict=True)
        exigir((ROOT/'.git').is_dir() and not (ROOT/'.git').is_symlink(),'directorio_evidencia_privado_ausente')
        historico=ROOT/'.git/qa/informacion-global/ejecuciones'
        historico.mkdir(parents=True,exist_ok=True)
        evidencia=Path(tempfile.mkdtemp(prefix='integracion-',dir=historico))
        comando=[sys.executable,str(ROOT/'pruebas/csd_integral/infra/ejecutar.py'),'--repo',str(ROOT),'--informacion-global']
        for argumento,ruta in rutas.items(): comando += [argumento,str(ruta)]
        proceso=subprocess.Popen(comando,cwd=ROOT,stdout=subprocess.PIPE,stderr=subprocess.PIPE,text=True,start_new_session=True)
        try:
            stdout,stderr=proceso.communicate(timeout=240)
        except subprocess.TimeoutExpired:
            os.killpg(proceso.pid,signal.SIGTERM)
            try: stdout,stderr=proceso.communicate(timeout=10)
            except subprocess.TimeoutExpired:
                os.killpg(proceso.pid,signal.SIGKILL); stdout,stderr=proceso.communicate(timeout=5)
            (evidencia/'stdout.jsonl').write_text(stdout); (evidencia/'stderr.txt').write_text(stderr)
            raise RuntimeError('timeout_cierre_no_acreditado')
        (evidencia/'stdout.jsonl').write_text(stdout); (evidencia/'stderr.txt').write_text(stderr)
        exigir(stderr.strip() == '','stderr_infra')
        resumen.update(comprobar(stdout,proceso.returncode,rutas,registro))
        resumen['estado']=registro['estado_esperado']
        resumen['evidencia_sha256']=hashlib.sha256(stdout.encode()).hexdigest()
    except Exception as error:
        resumen['bloqueo']=str(error) if isinstance(error,RuntimeError) else type(error).__name__
    finally:
        if proceso is not None and proceso.poll() is None:
            os.killpg(proceso.pid,signal.SIGTERM)
            try: proceso.wait(timeout=5)
            except subprocess.TimeoutExpired:
                os.killpg(proceso.pid,signal.SIGKILL); proceso.wait(timeout=5)
        if evidencia: guardar(evidencia/'resumen.json',resumen)
        guardar(ROOT/'pruebas/resultados/informacion_global_integracion.json',resumen)
    print(json.dumps(resumen,ensure_ascii=False))
    return 0 if resumen['estado'] == 'PASS_INFORMACION_GLOBAL_INTEGRACION' else 1

if __name__ == '__main__':
    sys.exit(main())
