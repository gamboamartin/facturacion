import subprocess,pathlib,sys,json
r=pathlib.Path(__file__).resolve().parents[2]
p=subprocess.run(['php','-n','-d','extension=pdo','-d','allow_url_fopen=0','-d','disable_functions=exec,shell_exec,system,passthru,proc_open,popen',str(r/'pruebas/csd_vista/probar.php')],capture_output=True,text=True,timeout=30)
s={'estado':'BLOQUEADO','exit':p.returncode,'salida':p.stdout,'errores':p.stderr}
if p.returncode==0 and not p.stderr:
 j=json.loads(p.stdout)
 if j['estado']=='PASS' and len(j['casos'])==12:s['estado']='PASS'
(r/'pruebas/resultados/csd_vista.json').write_text(json.dumps(s,indent=2))
print(json.dumps(s));sys.exit(0 if s['estado']=='PASS' else 1)
