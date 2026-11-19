#!/usr/bin/env python3
"""Gate obligatorio de integración CSD. No convierte recursos ausentes en omisiones."""
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
PARAMETROS = {
    '--engine-root': 'QA_CSD_ENGINE_ROOT',
    '--deps': 'QA_CSD_DEPS',
    '--documento': 'QA_CSD_DOCUMENTO',
    '--plugins': 'QA_CSD_PLUGINS',
    '--ddl': 'QA_CSD_DDL',
}
CASOS = {'exito': False, 'password_incorrecta': True, 'par_distinto': True}


def exigir(condicion: bool, codigo: str) -> None:
    if not condicion:
        raise RuntimeError(codigo)


def uno(registros: list[dict], clave: str) -> dict:
    encontrados = [registro for registro in registros if clave in registro]
    exigir(len(encontrados) == 1, 'evidencia_unica_requerida:' + clave)
    return encontrados[0]


def guardar_json(path: Path, datos: dict) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    with tempfile.NamedTemporaryFile('w', dir=path.parent, delete=False, encoding='utf-8') as temporal:
        json.dump(datos, temporal, ensure_ascii=False, indent=2)
        temporal.write('\n')
        nombre = temporal.name
    os.replace(nombre, path)


def comprobar(stdout: str, codigo: int, rutas: dict[str, Path]) -> dict:
    exigir(codigo == 0, 'infra_exit_no_cero')
    registros = [json.loads(linea) for linea in stdout.splitlines() if linea.strip()]
    hashes = uno(registros, 'fuentes_sha256_antes')
    motor = uno(registros, 'aislamiento_verificado')
    namespace = uno(registros, 'namespace_revisado')
    launcher = uno(registros, 'launcher_exit')
    exigir(hashes.get('fuentes_sin_cambios_durante_ejecucion') is True, 'fuentes_modificadas')
    exigir(bool(hashes['fuentes_sha256_antes']), 'sin_hashes')
    exigir(motor.get('estado') == 'PASS' and motor.get('pruebas_ejecutadas') is True, 'integracion_no_aprobada')
    for clave in ['aislamiento_verificado', 'shutdown_identidad_verificada', 'shutdown_enviado', 'motor_detenido']:
        exigir(motor.get(clave) is True, clave + '_no_acreditado')
    exigir(motor.get('shutdown_exit') == 0 and motor.get('motor_exit') == 0, 'shutdown_fallido')
    exigir(namespace.get('namespace_revisado') == motor.get('netns') and bool(motor.get('netns')), 'namespace_no_coincide')
    exigir(namespace.get('procesos_namespace_restantes_visibles') == [], 'procesos_namespace_restantes')
    exigir(launcher.get('procesos_grupo_restantes') == [] and launcher.get('launcher_exit') == 0, 'launcher_incompleto')
    exigir(launcher.get('host_sin_bind_escribible') is True, 'host_escribible')
    pasos = motor.get('pasos', [])
    exigir([p.get('nombre') for p in pasos] == ['esquema.php', 'precondiciones.php', 'escenario.php'], 'secuencia_incompleta')
    esperados = ['DDL_SINTETICO_PREPARADO', 'METADATOS_PRESENTES_NO_PRUEBA_ALTA', 'PASS']
    contenidos = []
    for paso, esperado in zip(pasos, esperados):
        exigir(paso.get('exit') == 0 and paso.get('stderr') == '', 'paso_con_error:' + paso['nombre'])
        contenido = json.loads(paso['salida'])
        exigir(contenido.get('estado') == esperado, 'estado_paso_incorrecto:' + paso['nombre'])
        contenidos.append(contenido)
    exigir(contenidos[0].get('fk_activas') == 1, 'fk_no_activas')
    escenario = contenidos[2]
    exigir(escenario.get('casos_ejecutados') == 3, 'cantidad_casos_incorrecta')
    casos = escenario.get('casos', [])
    exigir(len(casos) == 3 and {c.get('caso') for c in casos} == set(CASOS), 'casos_incompletos')
    for caso in casos:
        esperado = CASOS[caso['caso']]
        exigir(caso.get('rechazado') is esperado and caso.get('rechazo_esperado') is esperado, 'rechazo_incorrecto:' + caso['caso'])
        for clave in ['rollback_conteos', 'archivos_retirados', 'inventario_identico', 'sin_pem_nuevo_en_rechazo']:
            exigir(caso.get(clave) is True, clave + ':' + caso['caso'])
    exigir(escenario.get('fallo') is None and escenario.get('salida_interna_presente') is False, 'salida_o_fallo_interno')
    fuentes = escenario.get('fuentes', {})
    exigir(len(fuentes) >= 6, 'fuentes_reflection_incompletas')
    for fuente in fuentes.values():
        interno = fuente['archivo']
        prefijo = '/var/www/html/facturacion/'
        exigir(interno.startswith(prefijo), 'fuente_fuera_runtime')
        relativo = interno[len(prefijo):]
        if relativo == 'vendor/gamboa.martin/documento/orm/doc_documento.php':
            local = rutas['--documento'] / 'orm/doc_documento.php'
        elif relativo == 'vendor/gamboa.martin/plugins/src/ssl.php':
            local = rutas['--plugins'] / 'src/ssl.php'
        else:
            exigir(not relativo.startswith('vendor/') and '..' not in Path(relativo).parts, 'fuente_no_acreditada')
            local = ROOT / relativo
        digest = hashes['fuentes_sha256_antes'].get(str(local))
        exigir(digest is not None and digest == fuente['sha256'], 'hash_clase_cargada_no_coincide')
        exigir(hashlib.sha256(local.read_bytes()).hexdigest() == digest, 'fuente_cambio_tras_runner')
    return {
        'casos': casos,
        'casos_ejecutados': 3,
        'hashes_clases_cargadas': {nombre: valor['sha256'] for nombre, valor in fuentes.items()},
        'aislamiento_verificado': True,
        'shutdown_verificado': True,
        'procesos_namespace_visibles': [],
        'procesos_proc_no_legibles': namespace.get('procesos_no_legibles'),
        'alcance': escenario.get('alcance'),
    }


def main() -> int:
    ahora = datetime.datetime.now(datetime.timezone.utc).isoformat()
    resumen = {'suite': 'csd_integral', 'tipo': 'integracion', 'obligatoria': True, 'estado': 'BLOQUEADO', 'fecha_utc': ahora}
    proceso = None
    evidencia = None
    try:
        rutas = {}
        for argumento, variable in PARAMETROS.items():
            valor = os.environ.get(variable)
            exigir(bool(valor), 'recurso_no_configurado:' + variable)
            rutas[argumento] = Path(valor).resolve(strict=True)
        git = ROOT / '.git'
        exigir(git.is_dir() and not git.is_symlink(), 'directorio_evidencia_privado_no_disponible')
        historico = git / 'qa/csd-integral/ejecuciones'
        historico.mkdir(parents=True, exist_ok=True)
        evidencia = Path(tempfile.mkdtemp(prefix='integral-', dir=historico))
        comando = [sys.executable, str(ROOT / 'pruebas/csd_integral/infra/ejecutar.py'), '--repo', str(ROOT)]
        for argumento, ruta in rutas.items():
            comando += [argumento, str(ruta)]
        proceso = subprocess.Popen(comando, cwd=ROOT, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True, start_new_session=True)
        try:
            stdout, stderr = proceso.communicate(timeout=240)
        except subprocess.TimeoutExpired:
            os.killpg(proceso.pid, signal.SIGTERM)
            try:
                stdout, stderr = proceso.communicate(timeout=10)
            except subprocess.TimeoutExpired:
                os.killpg(proceso.pid, signal.SIGKILL)
                stdout, stderr = proceso.communicate(timeout=5)
            (evidencia / 'stdout.jsonl').write_text(stdout)
            (evidencia / 'stderr.txt').write_text(stderr)
            raise RuntimeError('timeout_integracion_cierre_no_acreditado')
        (evidencia / 'stdout.jsonl').write_text(stdout)
        (evidencia / 'stderr.txt').write_text(stderr)
        exigir(stderr.strip() == '', 'stderr_infra_presente')
        resumen.update(comprobar(stdout, proceso.returncode, rutas))
        resumen['estado'] = 'PASS'
        resumen['evidencia_sha256'] = hashlib.sha256(stdout.encode()).hexdigest()
    except Exception as error:
        resumen['bloqueo'] = str(error) if isinstance(error, RuntimeError) else type(error).__name__
    finally:
        if proceso is not None and proceso.poll() is None:
            os.killpg(proceso.pid, signal.SIGTERM)
            try:
                proceso.wait(timeout=5)
            except subprocess.TimeoutExpired:
                os.killpg(proceso.pid, signal.SIGKILL)
                proceso.wait(timeout=5)
        if evidencia:
            guardar_json(evidencia / 'resumen.json', resumen)
        guardar_json(ROOT / 'pruebas/resultados/csd_integral.json', resumen)
    print(json.dumps(resumen, ensure_ascii=False))
    return 0 if resumen['estado'] == 'PASS' else 1


if __name__ == '__main__':
    sys.exit(main())
