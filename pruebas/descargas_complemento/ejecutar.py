import json, os, subprocess, pathlib, sys
root=pathlib.Path(__file__).resolve().parents[2]
registro=json.loads(pathlib.Path(__file__).with_name('registro.json').read_text())
p=subprocess.run(['php',str(pathlib.Path(__file__).with_name('probar.php'))],cwd=root,capture_output=True,text=True,timeout=120)
if p.returncode:
    print(p.stderr,file=sys.stderr);sys.exit(1)
r=json.loads(p.stdout)
if r.get('estado')!='PASS_DESCARGAS_COMPLEMENTO' or r.get('casos')!=registro['casos']:
    sys.exit('Regresión de descarga incompleta')
print(json.dumps(r,ensure_ascii=False));print('PASS_DESCARGAS_COMPLEMENTO')
