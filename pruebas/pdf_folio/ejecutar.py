#!/usr/bin/env python3
"""FPDI real: folios completos y visibles en FINARQ, sin BD, red ni PAC."""
from pathlib import Path
import gzip, hashlib, importlib.util, json, os, shutil, sys, tempfile
from PIL import Image, ImageChops, ImageDraw
ROOT=Path(__file__).resolve().parents[2]
HERE=Path(__file__).resolve().parent
spec=importlib.util.spec_from_file_location('pdf_geometry',ROOT/'pruebas/pdf_logo/ejecutar.py')
engine=importlib.util.module_from_spec(spec);spec.loader.exec_module(engine)
require=engine.exigir
sha=lambda p:hashlib.sha256(p.read_bytes()).hexdigest()
# Puntos PDF (72 dpi): cuadro FINARQ x=179..199mm, y=0..10mm.
BOX=(179*72/25.4,0,199*72/25.4,10*72/25.4)
PIXELS=(507,0,565,29)

def render(source,vendor,output,mode,folio):
    env={**os.environ,'PDF_SOURCE':str(source),'PDF_VENDOR':str(vendor),'PDF_OUTPUT':str(output),
         'PDF_TEMPLATE':str(ROOT/'plantillas/factura_finarq.pdf'),'PDF_FOLIO':folio}
    result=engine.run(['php','-d','allow_url_fopen=0','-d','allow_url_include=0','-d',
        'disable_functions=fsockopen,pfsockopen,stream_socket_client,curl_exec,curl_multi_exec,mysqli_connect,mysqli_real_connect',
        str(ROOT/'pruebas/pdf_logo/renderizar.php'),mode,'sinlogo'],env=env)
    require(not result.stderr.strip(),'stderr_render:'+result.stderr[-1000:])
    require(json.loads(result.stdout)['estado']=='PDF_SINTETICO_GENERADO','render_incompleto')
    return engine.geometry(output),engine.raster(output)

def outside_words(page,blank):
    # Eliminar por multiconjunto todos los textos preimpresos/ajenos al número.
    words=list(page[2])
    for word in blank[2]:
        require(word in words,'texto_ajeno_al_folio_cambio:'+word[0]);words.remove(word)
    return words

def pixel_comparison(reference,candidate,mask=False):
    require(len(reference)==len(candidate),'paginas_raster_distintas')
    changes=[]
    for old,new in zip(reference,candidate):
        with Image.open(old).convert('RGB') as a,Image.open(new).convert('RGB') as b:
            require(a.size==b.size,'papel_distinto')
            diff=ImageChops.difference(a,b)
            changes.append(diff.getbbox() is not None)
            if mask:ImageDraw.Draw(diff).rectangle(PIXELS,fill=(0,0,0))
            require(diff.getbbox() is None,'raster_fuera_folio' if mask else 'raster_cambio_inesperado')
    return changes

def main():
    report={'estado':'BLOQUEADO','casos':[]};vendor=None;original=None
    try:
        registry=json.loads((HERE/'registro.json').read_text())
        vendor=(Path(os.environ.get('QA_CSD_DEPS',ROOT))/'vendor').resolve(strict=True)
        require((vendor/'autoload.php').is_file(),'dependencias_ausentes')
        original=engine.inventario(vendor)
        base=Path(os.environ.get('QA_PDF_FOLIO_OUTPUT',ROOT/'.git/qa/pdf-folio')).resolve()
        require(base!=vendor and vendor not in base.parents,'evidencia_dentro_vendor')
        base.mkdir(parents=True,exist_ok=True);work=Path(tempfile.mkdtemp(prefix='render-',dir=base))
        report['artefactos']=str(work)
        mirror=work/'vendor';shutil.copytree(vendor,mirror,symlinks=False)
        require(engine.inventario(mirror)==original,'dependencias_copia_distintas')
        manifest=json.loads((ROOT/'pruebas/pdf_logo/baseline/registro.json').read_text())
        item=manifest['archivos']['orm/_pdf.php']
        before=gzip.decompress((ROOT/'pruebas/pdf_logo/baseline'/item['archivo']).read_bytes())
        require(hashlib.sha256(before).hexdigest()==item['sha256'],'baseline_modificado')
        template_sha=sha(ROOT/'plantillas/factura_finarq.pdf')
        require(template_sha=='031f022d749ed6c6d7c3cce17b810c7111c0d7f96d5f4b4e07568e31f029d6a6','plantilla_distinta')
        watched=['orm/_pdf.php','orm/_logo_pdf.php','templates/logo_principal_lector.php','pruebas/pdf_logo/renderizar.php',
                 'pruebas/pdf_logo/ejecutar.py','pruebas/pdf_folio/ejecutar.py','pruebas/pdf_folio/registro.json']
        hashes={p:sha(ROOT/p) for p in watched}
        sources={}
        for phase in ['antes','despues']:
            source=work/phase;sources[phase]=source
            for name in ['orm/_pdf.php','orm/_logo_pdf.php','templates/logo_principal_lector.php']:
                dest=source/name;dest.parent.mkdir(parents=True,exist_ok=True)
                dest.write_bytes(before if phase=='antes' and name=='orm/_pdf.php' else (ROOT/name).read_bytes())
        for mode in ['factura','nota_credito']:
            blanks={phase:render(source,mirror,work/f'{phase}-{mode}-vacio.pdf',mode,'') for phase,source in sources.items()}
            require(blanks['antes'][0]==blanks['despues'][0],'geometria_sin_folio_cambio')
            pixel_comparison(blanks['antes'][1],blanks['despues'][1])
            for variant,folio in registry['folios'].items():
                samples={phase:render(source,mirror,work/f'{phase}-{mode}-{variant}.pdf',mode,folio) for phase,source in sources.items()}
                current,rasters=samples['despues'];blank=blanks['despues'][0]
                require(len(current)==len(blank)==2,'no_ejercita_dos_paginas')
                visible=pixel_comparison(blanks['despues'][1],rasters,mask=True)
                require(all(visible),'folio_no_visible_en_todas_paginas')
                extra_before=[]
                for idx,(page,empty) in enumerate(zip(current,blank)):
                    require(page[:2]==empty[:2],'dimensiones_cambiaron')
                    words=outside_words(page,empty)
                    require(bool(words),'folio_sin_texto')
                    require(''.join(w[0] for w in words)==folio,'folio_recortado_o_reordenado:'+str(words))
                    require(all(w[1]>=BOX[0] and w[2]>=BOX[1] and w[3]<=BOX[2] and w[4]<=BOX[3] for w in words),'folio_fuera_recuadro:'+str(words))
                    extra_before.append(outside_words(samples['antes'][0][idx],blanks['antes'][0][idx]))
                if variant=='corto':
                    require(samples['antes'][0]==current,'folio_corto_altero_geometria')
                    pixel_comparison(samples['antes'][1],rasters)
                else:
                    require(any(any(w[1]<BOX[0] or w[2]<BOX[1] or w[3]>BOX[2] or w[4]>BOX[3] for w in ws) for ws in extra_before),'baseline_no_reproduce_desbordamiento')
                report['casos'].append({'caso':mode+'_'+variant,'paginas':2,'folio':folio,'completo_dentro_recuadro':True,
                    'visible_en_ambas_paginas':True,'texto_y_raster_externo_identicos':True,'corto_identico_al_baseline':variant=='corto'})
        require([c['caso'] for c in report['casos']]==registry['casos'],'identidades_incompletas')
        require(len(report['casos'])==registry['casos_previstos'],'conteo_incorrecto')
        require(all(sha(ROOT/p)==h for p,h in hashes.items()),'fuentes_cambiaron')
        require(engine.inventario(mirror)==original,'renderer_modifico_dependencias')
        report.update(estado=registry['estado_esperado'],casos_ejecutados=len(report['casos']),fuentes=hashes,
                      baseline_commit=manifest['commit'],baseline_sha256=item['sha256'],plantilla_sha256=template_sha,
                      caja_puntos=BOX,caja_pixeles_72dpi=PIXELS,vendor_original_archivos=len(original))
    except Exception as error:report['error']=str(error)
    finally:
        if original is not None:
            report['vendor_original_sin_cambios']=engine.inventario(vendor)==original
            if not report['vendor_original_sin_cambios']:report['estado']='BLOQUEADO';report['error_vendor']='dependencias_originales_modificadas'
    output=ROOT/'pruebas/resultados/pdf_folio_render.json';output.parent.mkdir(parents=True,exist_ok=True)
    output.write_text(json.dumps(report,indent=2,ensure_ascii=False)+'\n');print(json.dumps(report,ensure_ascii=False))
    return 0 if report['estado']=='PASS_PDF_FOLIO_RENDER' else 1
if __name__=='__main__':sys.exit(main())
