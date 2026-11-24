#!/usr/bin/env python3
"""Render real del complemento Aguaban con datos ficticios, sin BD, red ni PAC."""
from pathlib import Path
import datetime
import hashlib
import json
import os
import re
import shutil
import subprocess
import sys
import tempfile
import xml.etree.ElementTree as ET
from urllib.parse import urlsplit, parse_qs, urlencode
from PIL import Image, ImageDraw

ROOT = Path(__file__).resolve().parents[2]
HERE = Path(__file__).resolve().parent
SOURCE = ROOT / 'orm/_PDF/aguaban'
TEMPLATE = ROOT / 'plantillas/complemento_aguaban.pdf'


def require(condition, message):
    if not condition:
        raise RuntimeError(message)


def sha(path):
    return hashlib.sha256(path.read_bytes()).hexdigest()


def inventory(directory):
    return {str(p.relative_to(directory)): sha(p) for p in sorted(directory.rglob('*')) if p.is_file() and '__pycache__' not in p.parts and p.suffix != '.pyc'}


def run(args, **kwargs):
    result = subprocess.run(args, capture_output=True, text=True, timeout=120, **kwargs)
    require(result.returncode == 0, 'comando_fallido:' + str(args[0]) + ':' + result.stderr[-1600:])
    return result


def compact(text):
    return ''.join(text.replace('\u00ad','').replace('\u200b','').split())


def geometry(pdf):
    tree = ET.fromstring(run(['pdftotext', '-bbox', str(pdf), '-']).stdout)
    pages = []
    for page in tree.iter():
        if page.tag.rsplit('}', 1)[-1] != 'page':
            continue
        words = []
        for word in page.iter():
            if word.tag.rsplit('}', 1)[-1] == 'word':
                words.append((''.join(word.itertext()), *(float(word.get(k)) for k in ['xMin', 'yMin', 'xMax', 'yMax'])))
        pages.append((float(page.get('width')), float(page.get('height')), words))
    return pages


def images(pdf):
    found = []
    for line in run(['pdfimages', '-list', str(pdf)]).stdout.splitlines()[2:]:
        parts = line.split()
        if len(parts) > 4 and parts[0].isdigit():
            found.append((int(parts[3]), int(parts[4])))
    return found


def dynamic_words(words, background):
    remaining = list(words)
    for original in background:
        for index, candidate in enumerate(remaining):
            if candidate[0] == original[0] and max(abs(a-b) for a, b in zip(candidate[1:], original[1:])) < 0.9:
                remaining.pop(index)
                break
    return remaining


def check_geometry(pages, background):
    count = 0
    for index, (width, height, words) in enumerate(pages):
        require(abs(width-595.28) < 1 and abs(height-841.89) < 1, 'papel_no_a4')
        for word in words:
            require(word[1] >= 0 and word[2] >= 0 and word[3] <= width+0.5 and word[4] <= height+0.5,
                    'texto_fuera_papel:' + word[0][:60])
        dynamic = dynamic_words(words, background)
        for word in dynamic:
            require(word[4]-word[2] >= 4.8, 'texto_dinamico_demasiado_pequeno:' + word[0][:60])
        # Las cajas de palabras dinámicas no deben invadir otras líneas/columnas.
        ordered = sorted(dynamic, key=lambda w: w[2])
        for a_index, a in enumerate(ordered):
            for b in ordered[a_index+1:]:
                if b[2] >= a[4]:
                    break
                overlap_y = min(a[4], b[4])-max(a[2], b[2])
                overlap_x = min(a[3], b[3])-max(a[1], b[1])
                if overlap_x > 1 and overlap_y > min(a[4]-a[2], b[4]-b[2])*0.45:
                    raise RuntimeError('textos_dinamicos_superpuestos:' + a[0][:30] + '/' + b[0][:30])
        count += len(dynamic)
    return count


def base_fixture(qr):
    document = {
        'uuid': '11111111-1111-4111-8111-111111111111', 'serie': 'FACQA', 'folio': '9001',
        'moneda': 'MXN', 'equivalencia': '1', 'objeto_impuesto': '02', 'parcialidad': '2',
        'saldo_anterior': '1234.56', 'importe_pagado': '456.78', 'saldo_insoluto': '777.78',
        'impuestos': [{'tipo': 'Traslado', 'base': '393.775862', 'impuesto': '002', 'factor': 'Tasa',
                       'tasa': '0.160000', 'importe': '63.004138'}]
    }
    return {
        'folio': 'CPQA-0042', 'fecha_emision': '2026-11-24T09:30:00', 'estado': 'timbrado',
        'emisor': {'nombre': 'EMISOR FICTICIO QA - SIN VALIDEZ FISCAL', 'rfc': 'AAA010101AAA',
                   'regimen': '601', 'cp': '11560', 'direccion': 'CALLE FICTICIA 10, MÉXICO', 'telefono': '0000000000'},
        'receptor': {'nombre': 'CLIENTE FICTICIO MUÑOZ QA', 'rfc': 'BBB010101BBB', 'regimen': '601',
                     'uso_cfdi': 'CP01', 'cp': '45010', 'direccion': 'DOMICILIO FICTICIO 20, ZAPOPAN'},
        'pagos': [{'fecha': '2026-11-23T12:20:00', 'forma_pago': '03', 'moneda': 'MXN', 'tipo_cambio': '1',
                   'monto': '456.78', 'doctos': [document], 'impuestos': []}],
        'totales': {'MontoTotalPagos': '456.78', 'TotalTrasladosBaseIVA16': '393.78', 'TotalTrasladosImpuestoIVA16': '63.00'},
        'fiscales': {'uuid': '22222222-2222-4222-8222-222222222222', 'fecha_certificacion': '2026-11-25T10:35:00',
                     'certificado_emisor': '00001000000000000001', 'certificado_sat': '00001000000000000002', 'rfc_proveedor': 'CCC010101CCC'},
        'sellos': {'cfdi': 'SELLOCFDIQA' + 'Ab12Cd34Ef56Gh78'*8, 'sat': 'SELLOSATQA' + 'Ij90Kl12Mn34Op56'*8,
                   'cadena_original': '||1.1|22222222-2222-4222-8222-222222222222|2026-11-25T10:35:00|CCC010101CCC|CADENAQA|00001000000000000002||'},
        'relaciones': [{'tipo': '04', 'uuid': '33333333-3333-4333-8333-333333333333'}], 'qr': str(qr),
        'qr_url': 'https://verificacfdi.facturaelectronica.sat.gob.mx/default.aspx?' + urlencode({'id':'22222222-2222-4222-8222-222222222222','re':'AAA010101AAA','rr':'BBB010101BBB','tt':'0.000000','fe':('SELLOCFDIQA' + 'Ab12Cd34Ef56Gh78'*8)[-8:]})
    }


def make_case(name, qr):
    data = base_fixture(qr)
    if name == 'dos_pagos_24_documentos':
        first = data['pagos'][0]
        first['doctos'] = []
        for index in range(1, 25):
            row = dict(base_fixture(qr)['pagos'][0]['doctos'][0])
            row.update(uuid=f'{index:08d}-AAAA-4AAA-8AAA-{index:012d}', serie='LOTEQA', folio=f'{index:04d}', parcialidad=str(index),
                       saldo_anterior='200.00', importe_pagado='100.00', saldo_insoluto='100.00')
            row['impuestos'] = [{'tipo': 'Traslado', 'base': '86.206897', 'impuesto': '002', 'factor': 'Tasa', 'tasa': '0.160000', 'importe': '13.793103'},
                                {'tipo': 'Retencion', 'base': '', 'impuesto': '001', 'factor': 'Tasa', 'tasa': '0.100000', 'importe': '8.620690'}]
            first['doctos'].append(row)
        second = dict(first)
        second['doctos'] = first['doctos'][12:]
        second.update(fecha='2026-11-22T16:15:00', forma_pago='01', monto='1200.00')
        first['doctos'] = first['doctos'][:12]
        first.update(monto='1200.00')
        data['pagos'].append(second)
        data['totales']['MontoTotalPagos'] = '2400.00'
    elif name == 'sellos_largos_y_unicode':
        data['emisor']['nombre'] = 'COMPAÑÍA ÁLVARO MUÑOZ - CRÉDITO Y SERVICIOS Ж ' * 3
        data['receptor']['nombre'] = 'RECEPTOR PEÑA, ASOCIACIÓN Y ADMINISTRACIÓN ÑANDÚ ' * 3
        data['sellos']['cfdi'] = 'SELLOCFDIINICIO' + 'Ab12Cd34Ef56Gh78'*48 + 'SELLOCFDIFINAL'
        data['sellos']['sat'] = 'SELLOSATINICIO' + 'Ij90Kl12Mn34Op56'*48 + 'SELLOSATFINAL'
        data['sellos']['cadena_original'] = 'CADENAINICIO||' + 'Qr78St90Uv12Wx34|'*48 + '||CADENAFINAL'
    elif name == 'pendiente_sin_certificacion':
        data['estado'] = 'pendiente'
        data['fiscales'] = {key: '' for key in data['fiscales']}
        data['sellos'] = {key: '' for key in data['sellos']}
        data['relaciones'] = []
        # Entrada QR vieja no debe mostrarse en un comprobante pendiente.
    elif name == 'datos_parecidos_a_html':
        data['receptor']['nombre'] = 'CLIENTE <b>QA LITERAL</b> & ASOCIADOS'
        data['emisor']['direccion'] = 'CALLE <img src="http://127.0.0.1:9/NO_FETCH"> 20'
    elif name == 'xml_pagos20_a_pdf':
        data['_fixture_xml']=True
        data['folio']='CP-000042'
        data['fecha_emision']='2026-11-24T12:00:01'
        data['emisor'].update(nombre='EMISOR FICTICIO ÁRBOL',cp='45010',direccion='',telefono='')
        data['receptor'].update(nombre='RECEPTOR FICTICIO MUÑOZ',cp='64000',direccion='')
        data['pagos']=[{'fecha':'2026-11-20T08:30:00','forma_pago':'03','moneda':'MXN','tipo_cambio':'1','monto':'730.00','doctos':[
            {'uuid':'22222222-2222-4222-8222-222222222222','serie':'A','folio':'100','moneda':'MXN','equivalencia':'1','objeto_impuesto':'02','parcialidad':'2','saldo_anterior':'1160.00','importe_pagado':'580.00','saldo_insoluto':'580.00','impuestos':[]},
            {'uuid':'33333333-3333-4333-8333-333333333333','serie':'B','folio':'200','moneda':'MXN','equivalencia':'1','objeto_impuesto':'01','parcialidad':'1','saldo_anterior':'300.00','importe_pagado':'150.00','saldo_insoluto':'150.00','impuestos':[]}], 'impuestos':[]},
            {'fecha':'2026-11-21T09:45:00','forma_pago':'02','moneda':'USD','tipo_cambio':'17.000000','monto':'100.00','doctos':[
            {'uuid':'44444444-4444-4444-8444-444444444444','serie':'USD','folio':'300','moneda':'USD','equivalencia':'1','objeto_impuesto':'02','parcialidad':'3','saldo_anterior':'100.00','importe_pagado':'100.00','saldo_insoluto':'0.00','impuestos':[]}],'impuestos':[]}]
        data['fiscales']={'uuid':'11111111-1111-4111-8111-111111111111','fecha_certificacion':'2026-11-24T13:05:09','certificado_emisor':'00001000000599999999','certificado_sat':'00001000000500000000','rfc_proveedor':'AAA010101AAA'}
        data['sellos']={'cfdi':'SELLOFICTICIO+abc/=','sat':'SELLOSATFICTICIO','cadena_original':'||1.1|11111111-1111-4111-8111-111111111111|2026-11-24T13:05:09|AAA010101AAA|SELLOFICTICIO+abc/=|00001000000500000000||'}
        data['totales']={'MontoTotalPagos':'2430.00'}
        data['relaciones']=[{'tipo':'04','uuid':'99999999-9999-4999-8999-999999999999'}]
    if data['estado']=='timbrado':
        data['qr_url']='https://verificacfdi.facturaelectronica.sat.gob.mx/default.aspx?' + urlencode({'id':data['fiscales']['uuid'],'re':data['emisor']['rfc'],'rr':data['receptor']['rfc'],'tt':'0.000000','fe':data['sellos']['cfdi'][-8:]})
    return data


def assert_content(name, data, text, semantic, columns):
    clean = compact(text)
    require(compact(data['folio']) in clean, 'folio_cp_incompleto')
    for party in ['emisor', 'receptor']:
        require(compact(data[party]['nombre']) in columns[party], 'nombre_omitido_o_alterado:' + party)
        require(data[party]['rfc'] in clean, 'rfc_omitido:' + party)
    require(data['fecha_emision'][:10] in text or '/'.join(reversed(data['fecha_emision'][:10].split('-'))) in text, 'fecha_emision_ausente')
    for payment in data['pagos']:
        require(payment['fecha'][:10] in text or '/'.join(reversed(payment['fecha'][:10].split('-'))) in text,
                'fecha_pago_ausente')
        for document in payment['doctos']:
            require(document['uuid'] in clean, 'uuid_documento_omitido:' + document['uuid'])
            require(compact(document['serie']) in clean and compact(document['folio']) in clean, 'serie_folio_documento_omitido')
    if name == 'un_pago_un_documento':
        # Importes de la factura pagada, no el total cero de cabecera CP ni placeholders.
        for amount in ['1,234.56', '456.78', '777.78']:
            require(amount.replace(',', '') in clean.replace(',', ''), 'importe_documento_ausente:' + amount)
        require('84111506' not in clean, 'parcialidad_de_ejemplo')
    if data['estado'] == 'timbrado':
        for value in data['fiscales'].values():
            if value and value != data['fiscales']['fecha_certificacion']:
                require(compact(value) in clean, 'dato_fiscal_omitido:' + value)
        date=data['fiscales']['fecha_certificacion'][:10]
        require(date in text or '/'.join(reversed(date.split('-'))) in text, 'fecha_certificacion_ausente')
        for key, value in data['sellos'].items():
            require(compact(value) in semantic, 'sello_o_cadena_truncado:' + key)
    else:
        require('PENDIENTE' in text.upper() or 'SIN TIMBRAR' in text.upper(), 'pendiente_sin_marca')
        require('22222222-2222-4222-8222-222222222222' not in clean, 'uuid_fiscal_inventado_en_pendiente')
    if name == 'datos_parecidos_a_html':
        require('<b>QALITERAL</b>' in clean, 'html_no_escapado')
        require('<img' in clean and 'NO_FETCH' in clean, 'etiqueta_imagen_no_escapada')


def reading_order(words):
    rows=[]
    for word in sorted(words,key=lambda w:(w[2],w[1])):
        if rows and abs(rows[-1][0][2]-word[2])<1.2:
            rows[-1].append(word)
        else:
            rows.append([word])
    return [w for row in rows for w in sorted(row,key=lambda w:w[1])]


def party_columns(pages, background):
    words=dynamic_words(pages[0][2],background)
    return {party:compact(''.join(w[0] for w in reading_order([w for w in words if (w[1]<355)==(party=='emisor')])))
            for party in ['emisor','receptor']}


def semantic_content(pages, background):
    words=[]
    for _,_,page_words in pages:
        dynamic=dynamic_words(page_words, background)
        words.extend(w for w in reading_order(dynamic) if 95<w[2]<800)
    text=compact(''.join(w[0] for w in words))
    for label in ['QR FISCAL SAT','COMPLEMENTO DE PAGO - CONTINUACIÓN','SELLO DIGITAL DEL CFDI (CONTINUACIÓN)',
                  'SELLO DIGITAL DEL SAT (CONTINUACIÓN)','CADENA ORIGINAL DEL COMPLEMENTO DE CERTIFICACIÓN DIGITAL DEL SAT (CONTINUACIÓN)']:
        text=text.replace(compact(label),'')
    return text


def check_qr(data, pngs):
    library = os.environ.get('QA_ZXING_LIB')
    if library:
        sys.path.insert(0, library)
    try:
        import zxingcpp
    except ImportError as error:
        raise RuntimeError('Falta zxingcpp: instala requirements.txt en una carpeta aislada y define QA_ZXING_LIB.') from error
    decoded = []
    for png in pngs:
        with Image.open(png) as raster:
            decoded.extend(item.text for item in zxingcpp.read_barcodes(raster))
    if data['estado']=='pendiente':
        require(not decoded,'qr_en_pendiente')
        return 'PASS_SIN_QR'
    require(bool(decoded),'qr_no_decodificable')
    for url in decoded:
        parts=urlsplit(url)
        require(parts.scheme=='https' and parts.hostname=='verificacfdi.facturaelectronica.sat.gob.mx' and parts.path=='/default.aspx','qr_destino_no_sat')
        params=parse_qs(parts.query)
        expected={'id':data['fiscales']['uuid'],'re':data['emisor']['rfc'],'rr':data['receptor']['rfc'],'tt':'0.000000','fe':data['sellos']['cfdi'][-8:]}
        require(params=={k:[v] for k,v in expected.items()},'qr_datos_fiscales_incorrectos')
    return 'PASS_UUID_RFC_TOTAL_CERO_SELLO'


def main():
    report = {'estado': 'BLOQUEADO', 'fecha_utc': datetime.datetime.now(datetime.timezone.utc).isoformat(), 'casos': []}
    private_vendor = None
    try:
        registry = json.loads((HERE/'registro.json').read_text())
        source_hashes = inventory(SOURCE)
        source_hashes['plantilla'] = sha(TEMPLATE)
        source_hashes['proveedor_xml'] = sha(ROOT/'orm/_datos_pdf_complemento.php')
        source_hashes['fixture_xml'] = sha(ROOT/'pruebas/regresion/pdf_complemento/fixture.php')
        test_hashes = inventory(HERE)
        vendor = (Path(os.environ.get('QA_CSD_DEPS', ROOT))/'vendor').resolve(strict=True)
        require((vendor/'autoload.php').is_file(), 'vendor_ausente')
        parent = ROOT/'.git/qa/pdf-complemento-aguaban'
        parent.mkdir(parents=True, exist_ok=True)
        configured_output=os.environ.get('QA_PDF_COMPLEMENTO_OUTPUT')
        output=Path(configured_output) if configured_output else Path(tempfile.mkdtemp(prefix='render-', dir=parent))
        output.mkdir(mode=0o700, parents=True, exist_ok=True)
        private_vendor = output/'vendor'
        shutil.copytree(vendor, private_vendor, symlinks=True)
        qr = output/'imagen-qa.png'
        image = Image.new('RGB', (101, 101), 'white')
        draw = ImageDraw.Draw(image)
        for x in range(5, 96, 8):
            for y in range(5, 96, 8):
                if (x+y) % 3:
                    draw.rectangle((x, y, x+5, y+5), fill='black')
        image.save(qr)
        background = geometry(TEMPLATE)[0][2]
        for name in registry['casos']:
            data = make_case(name, qr)
            data_path = output/(name+'.json')
            data_path.write_text(json.dumps(data, ensure_ascii=False, indent=2))
            pdf = output/(name+'.pdf')
            cache = output/('cache-'+name)
            cache.mkdir(mode=0o700)
            env = {**os.environ, 'PDF_SOURCE': str(ROOT), 'PDF_VENDOR': str(private_vendor), 'PDF_TEMPLATE': str(TEMPLATE),
                   'PDF_CACHE': str(cache), 'PDF_DATA': str(data_path), 'PDF_OUTPUT': str(pdf)}
            result = run(['php', '-d', 'allow_url_fopen=0', '-d', 'allow_url_include=0', '-d',
                          'disable_functions=fsockopen,pfsockopen,stream_socket_client,curl_exec,curl_multi_exec,mysqli_connect,mysqli_real_connect',
                          str(HERE/'renderizar.php')], env=env)
            require(not result.stderr.strip(), 'stderr_render:' + result.stderr[-1000:])
            require(json.loads(result.stdout)['estado'] == 'PDF_SINTETICO_GENERADO', 'render_incompleto')
            text = run(['pdftotext', '-layout', str(pdf), '-']).stdout
            pages = geometry(pdf)
            semantic = semantic_content(pages, background)
            columns = party_columns(pages, background)
            assert_content(name, data, text, semantic, columns)
            words = check_geometry(pages, background)
            if name == 'un_pago_un_documento':
                require(len(pages) == 1, 'caso_sencillo_no_preserva_una_pagina')
            if name == 'dos_pagos_24_documentos':
                require(len(pages) >= 2, 'caso_largo_no_ejercita_continuacion')
            require(any(w==h and w>100 for w,h in images(pdf)) == (data['estado']=='timbrado'), 'imagen_qr_estado_incorrecto')
            run(['pdftoppm', '-r', '140', '-png', str(pdf), str(output/name)])
            decoded=check_qr(data, sorted(output.glob(name+'-*.png')))
            report['casos'].append({'caso': name, 'paginas': len(pages), 'palabras_dinamicas': words,
                                    'documentos': sum(len(p['doctos']) for p in data['pagos']),
                                    'contenido_y_limites': 'PASS', 'imagen_qr': 'PASS', 'qr_decodificacion': decoded, 'sha256_pdf': sha(pdf)})
        require(len(report['casos']) == registry['casos_previstos'], 'casos_incompletos')
        current = inventory(SOURCE)
        current['plantilla'] = sha(TEMPLATE)
        current['proveedor_xml'] = sha(ROOT/'orm/_datos_pdf_complemento.php')
        current['fixture_xml'] = sha(ROOT/'pruebas/regresion/pdf_complemento/fixture.php')
        require(current == source_hashes and inventory(HERE) == test_hashes, 'fuentes_o_pruebas_cambiaron')
        report.update(estado=registry['estado_esperado'], fuentes=source_hashes, pruebas=test_hashes,
                      salida_privada=str(output), datos='ficticios', bd='no_conectada', pac='no_ejecutado', red='bloqueada',
                      limites=registry['limites'])
    except Exception as error:
        report['error'] = str(error)
    finally:
        if private_vendor is not None and private_vendor.is_dir():
            shutil.rmtree(private_vendor)
    target = ROOT/'pruebas/resultados/pdf_complemento_aguaban.json'
    target.parent.mkdir(exist_ok=True)
    target.write_text(json.dumps(report, ensure_ascii=False, indent=2)+'\n')
    print(report['estado'])
    if report.get('error'):
        print(report['error'], file=sys.stderr)
    return 0 if report['estado'] == 'PASS_PDF_COMPLEMENTO_AGUABAN' else 1


if __name__ == '__main__':
    raise SystemExit(main())
