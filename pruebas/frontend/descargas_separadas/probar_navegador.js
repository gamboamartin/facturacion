'use strict';
// Sólo HTML y documentos sintéticos interceptados; nunca abre una aplicación o servicio fiscal.
const fs = require('fs');
const assert = require('assert').strict;
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || '/home/em3/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
const entrada = JSON.parse(fs.readFileSync(0, 'utf8'));
const casos = [];
let externas = 0;
const XML = Buffer.from('<?xml version="1.0" encoding="UTF-8"?><cfdi:Comprobante xmlns:cfdi="http://www.sat.gob.mx/cfd/4" Version="4.0" Serie="QA" Folio="7"/>');
const PDF = Buffer.from('%PDF-1.4\n% Documento sintético QA, no es comprobante fiscal\n%%EOF\n');
const pausa = ms => new Promise(resolve => setTimeout(resolve, ms));

async function fixture(browser, seccion, opts = {}) {
    const context = await browser.newContext({ acceptDownloads: true });
    const page = await context.newPage();
    page.setDefaultTimeout(15000);
    const estado = { solicitudes: [], descargas: [], errores: [], xmlCompleto: false, pdfPosteriorXML: true };
    page.on('pageerror', error => estado.errores.push(error.message));
    page.on('download', download => estado.descargas.push(download));
    if (opts.bloqueoAutomatico) {
        await page.addInitScript(() => {
            const click = HTMLAnchorElement.prototype.click;
            HTMLAnchorElement.prototype.click = function () {
                if (this.href.startsWith('blob:')) { window.__bloqueadas = (window.__bloqueadas || 0) + 1; return; }
                return click.call(this);
            };
        });
    }
    await context.route('**/*', async route => {
        const url = new URL(route.request().url());
        if (url.origin !== 'http://facturacion.test') { externas++; await route.abort(); return; }
        if (url.pathname.endsWith('/js/descargas/por_separado.js')) {
            await route.fulfill({ contentType: 'application/javascript', body: entrada.js }); return;
        }
        const accion = url.searchParams.get('accion');
        if (accion === 'descargar_por_separado') {
            let html = entrada.vistas[seccion];
            if (opts.mutarURL) html += '<script>document.querySelector("[data-descarga=xml]").href="https://ajeno.test/robo";</script>';
            await route.fulfill({ contentType: 'text/html', body: '<!doctype html><html lang="es"><meta charset="utf-8"><title>QA</title><body>' + html + '</body></html>' }); return;
        }
        if (accion === 'descarga_xml' || accion === 'genera_pdf') {
            const tipo = accion === 'descarga_xml' ? 'xml' : 'pdf';
            assert.equal(url.searchParams.get('seccion'), seccion);
            assert.equal(url.searchParams.get('registro_id'), '7');
            estado.solicitudes.push(tipo);
            if (tipo === 'pdf' && !opts.mutarURL && !estado.xmlCompleto) estado.pdfPosteriorXML = false;
            if (tipo === 'xml' && opts.lento) await pausa(6500);
            if (tipo === 'xml' && opts.redireccion) {
                estado.xmlCompleto = true;
                await route.fulfill({ status: 302, headers: { Location: 'https://ajeno.test/login' } }); return;
            }
            const fallo = opts.fallo === tipo;
            const mime = fallo ? (opts.mimeFallo || 'text/html') : tipo === 'xml' ? 'application/xml' : 'application/pdf';
            let cuerpo = fallo ? Buffer.from(opts.cuerpoFallo || '<html><form>Iniciar sesión</form></html>') : tipo === 'xml' ? XML : PDF;
            const nombre = opts.nombreEspecial ? "attachment; filename*=UTF-8''Factura%20%C3%81GUA%20QA." + tipo : (tipo === 'pdf' ? 'inline' : 'attachment') + '; filename="QA-000007.' + tipo + '"';
            if (tipo === 'xml') estado.xmlCompleto = true;
            await route.fulfill({ status: fallo ? opts.statusFallo || 200 : 200,
                headers: { 'Content-Type': mime, 'Content-Disposition': nombre }, body: cuerpo }); return;
        }
        throw new Error('Solicitud QA inesperada: ' + url.href);
    });
    await page.goto('http://facturacion.test/facturacion/index.php?seccion=' + seccion + '&accion=descargar_por_separado&registro_id=7');
    await page.waitForFunction(() => document.querySelector('#descargas-por-separado').dataset.finalizada === '1');
    assert.equal(page.isClosed(), false);
    assert.deepEqual(estado.errores, []);
    return { page, context, estado };
}

async function comprobarDescargas(f, tipos, nombres) {
    assert.equal(f.estado.descargas.length, tipos.length);
    for (let i = 0; i < tipos.length; i++) {
        const d = f.estado.descargas[i];
        assert.equal(d.suggestedFilename(), nombres ? nombres[i] : 'QA-000007.' + tipos[i]);
        assert.equal(await d.failure(), null);
        assert.deepEqual(fs.readFileSync(await d.path()), tipos[i] === 'xml' ? XML : PDF);
    }
}

(async () => {
    const launch = { headless: true };
    if (process.env.CHROME_BIN) launch.executablePath = process.env.CHROME_BIN;
    else if (!fs.existsSync(chromium.executablePath()) && fs.existsSync('/opt/google/chrome/chrome')) launch.executablePath = '/opt/google/chrome/chrome';
    const browser = await chromium.launch(launch);
    try {
        for (const seccion of ['fc_factura', 'fc_nota_credito', 'fc_complemento_pago']) {
            const f = await fixture(browser, seccion);
            assert.deepEqual(f.estado.solicitudes, ['xml', 'pdf']);
            assert.equal(f.estado.pdfPosteriorXML, true);
            await comprobarDescargas(f, ['xml', 'pdf']);
            assert.match(await f.page.locator('#descargas-resultado').innerText(), /panel de descargas/);
            await f.context.close(); casos.push(seccion + '_xml_pdf_contenido_nombre_orden');
        }
        {
            const f = await fixture(browser, 'fc_factura', { lento: true });
            assert.equal(f.estado.pdfPosteriorXML, true);
            await comprobarDescargas(f, ['xml', 'pdf']);
            await f.context.close(); casos.push('xml_lento_mas_de_seis_segundos_no_cancela_ni_adelanta_pdf');
        }
        for (const [nombre, opts] of [
            ['xml_html_200', { fallo: 'xml' }],
            ['xml_html_con_mime_xml', { fallo: 'xml', mimeFallo: 'application/xml' }],
            ['xml_invalido', { fallo: 'xml', mimeFallo: 'application/xml', cuerpoFallo: '<malformado>' }],
            ['xml_http_403', { fallo: 'xml', statusFallo: 403 }],
            ['pdf_html_200', { fallo: 'pdf' }],
            ['pdf_firma_invalida', { fallo: 'pdf', mimeFallo: 'application/pdf' }]
        ]) {
            const f = await fixture(browser, 'fc_complemento_pago', opts);
            await comprobarDescargas(f, [opts.fallo === 'xml' ? 'pdf' : 'xml']);
            assert.equal(await f.page.locator('[data-estado=' + opts.fallo + ']').getAttribute('data-resultado'), 'error');
            assert.match(await f.page.locator('#descargas-resultado').innerText(), /enlaces individuales/);
            assert.equal(await f.page.locator('[data-descarga=' + opts.fallo + ']').count(), 1);
            await f.context.close(); casos.push(nombre + '_error_claro_conserva_otro_documento');
        }
        {
            const f = await fixture(browser, 'fc_factura', { redireccion: true });
            await comprobarDescargas(f, ['pdf']);
            assert.equal(await f.page.locator('[data-estado=xml]').getAttribute('data-resultado'), 'error');
            await f.context.close(); casos.push('redireccion_no_sigue_url_ajena_ni_descarga_login');
        }
        {
            const f = await fixture(browser, 'fc_nota_credito', { nombreEspecial: true });
            await comprobarDescargas(f, ['xml', 'pdf'], ['Factura ÁGUA QA.xml', 'Factura ÁGUA QA.pdf']);
            await f.context.close(); casos.push('filename_utf8_content_disposition');
        }
        {
            const f = await fixture(browser, 'fc_factura', { mutarURL: true });
            assert.deepEqual(f.estado.solicitudes, ['pdf']);
            await comprobarDescargas(f, ['pdf']);
            assert.equal(await f.page.locator('[data-estado=xml]').getAttribute('data-resultado'), 'error');
            await f.context.close(); casos.push('url_externa_mutada_no_se_solicita');
        }
        {
            const f = await fixture(browser, 'fc_factura', { bloqueoAutomatico: true });
            assert.equal(await f.page.evaluate(() => window.__bloqueadas), 2);
            assert.equal(f.estado.descargas.length, 0);
            const xmlDownload = f.page.waitForEvent('download');
            await f.page.locator('[data-descarga=xml]').click();
            const xml = await xmlDownload;
            assert.equal(xml.suggestedFilename(), 'QA-000007.xml');
            assert.deepEqual(fs.readFileSync(await xml.path()), XML);
            // PDF inline se puede abrir mediante su enlace aunque se bloquee la descarga automática.
            const paginaPdf = f.context.waitForEvent('page');
            await f.page.locator('[data-descarga=pdf]').click();
            const pdf = await paginaPdf;
            await pdf.waitForLoadState('domcontentloaded');
            assert.equal(new URL(pdf.url()).searchParams.get('accion'), 'genera_pdf');
            assert.match(await f.page.locator('#descargas-resultado').innerText(), /si falta un archivo/);
            await f.context.close(); casos.push('bloqueo_multiple_mantiene_enlaces_manuales_operables');
        }
        console.log(JSON.stringify({ estado: 'PASS_DESCARGAS_SEPARADAS_UI', casos, solicitudes_externas: externas }));
    } finally { await browser.close(); }
})().catch(error => { console.error(error.stack); process.exit(1); });
