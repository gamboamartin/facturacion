#!/usr/bin/env python3
"""Control de regresión PHPUnit: ejecutar desde cualquier directorio, sin operaciones reales."""
from pathlib import Path
import datetime, hashlib, json, subprocess, sys, xml.etree.ElementTree as ET
base=Path(__file__).resolve().parent;raiz=base.parent.parent
salida=raiz/'pruebas/resultados';salida.mkdir(exist_ok=True)
registro=json.loads((base/'registro.json').read_text());informe={'fecha_utc':datetime.datetime.now(datetime.timezone.utc).isoformat(),'fuentes':{},'pruebas':{},'estado':'BLOQUEADO'}
for suite in registro['suites']:
    for nombre in suite['fuentes']:
        informe['fuentes'][nombre]=hashlib.sha256((raiz/nombre).read_bytes()).hexdigest()
for archivo in sorted(base.rglob('*.php')):
    informe['pruebas'][str(archivo.relative_to(raiz))]=hashlib.sha256(archivo.read_bytes()).hexdigest()
try:
    proceso=subprocess.run(['php',str(raiz/'pruebas/herramientas/vendor/bin/phpunit'),'-c',str(raiz/'phpunit.xml'),'--log-junit',str(salida/'phpunit.xml')],capture_output=True,text=True,timeout=180)
    informe.update(codigo=proceso.returncode,salida=proceso.stdout,errores=proceso.stderr)
    if proceso.returncode==0:
        arbol=ET.parse(salida/'phpunit.xml');casos=arbol.findall('.//testcase');esperados=sum(x['comprobaciones_esperadas'] for x in registro['suites'])
        informe.update(casos_ejecutados=len(casos),casos_esperados=esperados)
        identidades={(c.get('classname'),c.get('name')) for c in casos}
        registro_ids={(Path(suite['clase_phpunit']).stem,nombre) for suite in registro['suites'] for nombre in suite['casos']}
        informe['registro_coincide']=identidades==registro_ids
        if len(casos)==esperados and identidades==registro_ids and not arbol.findall('.//skipped') and not arbol.findall('.//failure') and not arbol.findall('.//error'):
            informe['estado']='EXITO_UNITARIO'
        else: informe['motivo']='Cantidad de casos distinta al registro, omisión o fallo; discutir antes de commit.'
except Exception as error:informe['errores']=str(error)
(salida/'resultado.json').write_text(json.dumps(informe,ensure_ascii=False,indent=2)+'\n')
print(informe.get('salida',''));print(informe['estado']);sys.exit(0 if informe['estado']=='EXITO_UNITARIO' else 1)
