#!/usr/bin/env python3
"""Regresión de geometría con renderizadores PDF reales y documentos sintéticos; sin BD/PAC."""
from pathlib import Path
import datetime
import gzip
import hashlib
import json
import os
import re
import shutil
import subprocess
import sys
import tempfile
import xml.etree.ElementTree as ET
from PIL import Image, ImageChops, ImageDraw

ROOT=Path(__file__).resolve().parents[2]
HERE=Path(__file__).resolve().parent
VENDOR=Path(os.environ.get('QA_CSD_DEPS',ROOT))/'vendor'

def exigir(condition, message):
    if not condition: raise RuntimeError(message)

def inventario(directory):
    return {str(p.relative_to(directory)):hashlib.sha256(p.read_bytes()).hexdigest()
            for p in sorted(directory.rglob('*')) if p.is_file()}

def run(args, **kwargs):
    p=subprocess.run(args,capture_output=True,text=True,timeout=90,**kwargs)
    exigir(p.returncode==0, 'comando_fallido:'+str(args[0])+':'+p.stderr[-1500:])
    return p

def geometry(pdf):
    p=run(['pdftotext','-bbox',str(pdf),'-'])
    tree=ET.fromstring(p.stdout)
    pages=[]
    for page in tree.iter():
        if page.tag.rsplit('}',1)[-1]!='page': continue
        words=[]
        for word in page.iter():
            if word.tag.rsplit('}',1)[-1]=='word':
                words.append((''.join(word.itertext()),*[round(float(word.get(k)),3) for k in ['xMin','yMin','xMax','yMax']]))
        words.sort(key=lambda w:(w[2],w[1],w[0],w[3],w[4]))
        pages.append((round(float(page.get('width')),3),round(float(page.get('height')),3),words))
    return pages

def images(pdf):
    result=[]
    for line in run(['pdfimages','-list',str(pdf)]).stdout.splitlines()[2:]:
        parts=line.split()
        if len(parts)>4 and parts[0].isdigit(): result.append((int(parts[3]),int(parts[4])))
    return result

def assert_aspect(pdf, size):
    matches=[]
    for line in run(['pdfimages','-list',str(pdf)]).stdout.splitlines()[2:]:
        parts=line.split()
        if len(parts)>13 and parts[0].isdigit() and (int(parts[3]),int(parts[4]))==size:
            x,y=float(parts[12]),float(parts[13]);matches.append((x,y))
    exigir(bool(matches),'imagen_sin_medicion_de_proporcion')
    exigir(all(abs(x-y)<=max(1,min(x,y)*0.02) for x,y in matches),'logo_deformado_en_pdf:'+pdf.stem)

def raster(pdf):
    prefix=pdf.with_suffix('')
    run(['pdftoppm','-r','72','-png',str(pdf),str(prefix)])
    return sorted(pdf.parent.glob(prefix.name+'-*.png'))

def compare(before, after, mode, has_logo, asset_size):
    a,b=geometry(before),geometry(after)
    exigir(a==b,'texto_o_coordenadas_cambiaron:'+after.stem)
    exigir(len(a)>=1,'sin_paginas')
    if mode in ['factura','nota_credito']: exigir(len(a)==2,'cabeceras_dos_paginas')
    if mode=='nomina_larga': exigir(len(a)>=2,'nomina_no_ejercita_paginacion')
    if mode=='nomina_cancelada': exigir('CANCELADO' in ''.join(run(['pdftotext','-raw',str(after),'-']).stdout.split()),'marca_cancelado_ausente')
    before_png,after_png=raster(before),raster(after)
    exigir(len(before_png)==len(after_png),'cantidad_renders')
    # Puntos PDF a 72 dpi. Las posiciones de TODOS los textos se comparan además arriba.
    box=(26,26,130,122) if mode in ['factura','nota_credito'] else ((0,0,140,150) if mode=='complemento' else (450,0,612,110))
    changed=0
    for old,new in zip(before_png,after_png):
        with Image.open(old).convert('RGB') as x, Image.open(new).convert('RGB') as y:
            exigir(x.size==y.size,'papel_raster_cambio')
            difference=ImageChops.difference(x,y)
            if difference.getbbox(): changed+=1
            outside=difference.copy();ImageDraw.Draw(outside).rectangle(box,fill=(0,0,0))
            exigir(outside.getbbox() is None,'diferencia_fuera_caja_logo:'+after.stem+':'+str(outside.getbbox()))
    if has_logo:
        exigir(changed>0,'logo_no_visible:'+after.stem)
        exigir(asset_size in images(after),'imagen_no_incorporada:'+after.stem)
        assert_aspect(after,asset_size)
    if not has_logo and mode!='complemento': exigir(changed==0,'sin_logo_altero_diseno:'+after.stem)
    return {'paginas':len(a),'palabras':sum(len(p[2]) for p in a),'paginas_con_diferencia':changed,'caja_diferencia_permitida':box}

def main():
    report={'estado':'BLOQUEADO','fecha_utc':datetime.datetime.now(datetime.timezone.utc).isoformat(),'casos':[]}
    vendor_original=None
    vendor_before=None
    try:
        exigir((VENDOR/'autoload.php').is_file(),'vendor_ausente')
        vendor_original=VENDOR.resolve(strict=True)
        vendor_before=inventario(vendor_original)
        for tool in ['php','pdftoppm','pdftotext','pdfimages']: exigir(shutil.which(tool) is not None,'herramienta_ausente:'+tool)
        registro=json.loads((HERE/'registro.json').read_text())
        manifest=json.loads((HERE/'baseline/registro.json').read_text())
        exigir(hashlib.sha256((ROOT/'plantillas/factura_finarq.pdf').read_bytes()).hexdigest()=='031f022d749ed6c6d7c3cce17b810c7111c0d7f96d5f4b4e07568e31f029d6a6','plantilla_finarq_distinta')
        base=Path(os.environ.get('QA_PDF_LOGO_OUTPUT',ROOT/'.git/qa/pdf-logo'))
        exigir(base.resolve()!=vendor_original and vendor_original not in base.resolve().parents,'evidencia_dentro_vendor_original')
        base.mkdir(parents=True,exist_ok=True)
        work=Path(tempfile.mkdtemp(prefix='render-',dir=base));report['artefactos']=str(work)
        vendor_copia=work/'vendor'
        # Dompdf puede crear métricas de fuentes. Ambos lados usan esta copia privada.
        shutil.copytree(vendor_original,vendor_copia,symlinks=False)
        exigir(inventario(vendor_copia)==vendor_before,'copia_vendor_distinta')
        manifest_vendor=json.dumps(vendor_before,sort_keys=True,separators=(',',':'))
        (work/'vendor-original-inventario.json').write_text(manifest_vendor)
        report['vendor_original_archivos']=len(vendor_before)
        report['vendor_original_inventario_sha256']=hashlib.sha256(manifest_vendor.encode()).hexdigest()
        sources={}
        hashes={}
        for phase in ['antes','despues']:
            source=work/phase;source.mkdir();sources[phase]=source
            for name in [*manifest['archivos'],'orm/_logo_pdf.php','templates/logo_principal_lector.php']:
                dest=source/name;dest.parent.mkdir(parents=True,exist_ok=True)
                if phase=='antes' and name in manifest['archivos']:
                    info=manifest['archivos'][name];data=gzip.decompress((HERE/'baseline'/info['archivo']).read_bytes())
                    exigir(hashlib.sha256(data).hexdigest()==info['sha256'],'baseline_modificado:'+name)
                    dest.write_bytes(data)
                else: shutil.copy2(ROOT/name,dest)
                if phase=='despues': hashes[name]=hashlib.sha256(dest.read_bytes()).hexdigest()
        proof_files=[p for p in HERE.rglob('*') if p.is_file() and '__pycache__' not in p.parts]
        test_hashes={str(p.relative_to(ROOT)):hashlib.sha256(p.read_bytes()).hexdigest() for p in proof_files}
        baseline={}
        modes=['factura','nota_credito','complemento','nomina','nomina_larga','nomina_cancelada']
        for mode in modes:
            for phase,variant in [('antes','sinlogo'),('despues','sinlogo'),('despues','png'),('despues','jpg'),('despues','alto')]:
                output=work/(phase+'-'+mode+'-'+variant+'.pdf')
                env={**os.environ,'PDF_SOURCE':str(sources[phase]),'PDF_VENDOR':str(vendor_copia),'PDF_OUTPUT':str(output),'PDF_TEMPLATE':str(ROOT/'plantillas/factura_finarq.pdf')}
                p=run(['php','-d','allow_url_fopen=0','-d','allow_url_include=0','-d','disable_functions=fsockopen,pfsockopen,stream_socket_client,curl_exec,curl_multi_exec',str(HERE/'renderizar.php'),mode,variant],env=env)
                exigir(p.stderr.strip()=='','stderr_render:'+output.name+':'+p.stderr[-1000:])
                result=json.loads(p.stdout);exigir(result['estado']=='PDF_SINTETICO_GENERADO','render_no_acreditado')
                if phase=='antes': baseline[mode]=output;continue
                case=mode+'_'+variant
                metric=compare(baseline[mode],output,mode,variant!='sinlogo',{'png':(160,80),'jpg':(135,115),'alto':(40,240)}.get(variant))
                if mode=='complemento':
                    html=output.with_suffix('.pdf.html').read_text()
                    cell=re.search(r'<td[^>]*width:\s*10%.*?</td>',html,re.S)
                    exigir(cell is not None,'celda_logo_ausente')
                    exigir('src=""' not in cell.group(0),'imagen_vacia_en_logo_complemento')
                    exigir(len(re.findall(r'<img\b',cell.group(0)))==(0 if variant=='sinlogo' else 1),'numero_imagenes_logo_complemento')
                report['casos'].append({'caso':case,**metric})
        exigir([c['caso'] for c in report['casos']]==registro['casos'] and len(report['casos'])==registro['casos_previstos'],'casos_incompletos')
        exigir(all(hashlib.sha256((ROOT/n).read_bytes()).hexdigest()==h for n,h in {**hashes,**test_hashes}.items()),'fuentes_o_pruebas_cambiaron_durante_ejecucion')
        report.update(estado='PASS_PDF_LOGO_RENDER',casos_ejecutados=24,fuentes=hashes,pruebas=test_hashes,baseline_commit=manifest['commit'],
                      alcance='Motores reales FPDI y Dompdf con datos y logos ficticios, PDO doble sin conexiones. Compara todos los textos y coordenadas, papel, páginas y diferencias raster fuera de cajas de logo. No certifica consulta SQL real, timbrado ni PDFs almacenados en producción.')
    except Exception as error:
        report['error']=str(error)
    finally:
        if vendor_before is not None:
            try:
                report['vendor_original_sin_cambios']=inventario(vendor_original)==vendor_before
                if not report['vendor_original_sin_cambios']:
                    report['estado']='BLOQUEADO'
                    report['error_vendor']='vendor_original_modificado_durante_prueba'
            except Exception as error:
                report['estado']='BLOQUEADO'
                report['vendor_original_sin_cambios']=False
                report['error_vendor']='no_se_pudo_verificar_vendor_original:'+type(error).__name__
    output=ROOT/'pruebas/resultados/pdf_logo_render.json';output.parent.mkdir(parents=True,exist_ok=True);output.write_text(json.dumps(report,indent=2,ensure_ascii=False)+'\n')
    print(json.dumps(report,ensure_ascii=False))
    return 0 if report['estado']=='PASS_PDF_LOGO_RENDER' else 1

if __name__=='__main__':sys.exit(main())
