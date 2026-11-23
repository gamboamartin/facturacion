'use strict';
// Chromium real, con todas las respuestas HTTP interceptadas. No utiliza datos ni servicios de aplicación.
const fs = require('fs');
const path = require('path');
const assert = require('assert').strict;
const entradas = JSON.parse(fs.readFileSync(0, 'utf8'));
const modulo = process.env.PLAYWRIGHT_MODULE ||
    '/home/em3/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright';
const { chromium } = require(modulo);
const TOKEN = 'a'.repeat(32);
const URL_BASE = 'http://facturacion.test/index.php?seccion=fc_factura&accion=timbrado_masivo';
const casos = [];
const capturas = [];
let solicitudesExternas = 0;

async function capturar(page, nombre) {
    if (!process.env.UI_CAPTURE_DIR) return;
    const directorio = path.resolve(process.env.UI_CAPTURE_DIR);
    fs.mkdirSync(directorio, { recursive: true });
    // La captura añade el CSS compartido real; no carga menús, recursos remotos ni la aplicación.
    const estilo = await page.addStyleTag({
        content: fs.readFileSync(path.resolve(__dirname, '../../../css/overrides.css'), 'utf8')
    });
    const destino = path.join(directorio, nombre + '.png');
    await page.screenshot({ path: destino, fullPage: true, animations: 'disabled' });
    await estilo.evaluate(function (elemento) { elemento.remove(); });
    capturas.push(destino);
}

function filas(cantidad) {
    return Array.from({ length: cantidad }, function (_, i) {
        return {
            id: i + 1, folio: String(i + 1), serie: 'F', cliente: 'Cliente ficticio ' + (i + 1),
            rfc: 'XAXX010101000', emisor: 'Emisor ficticio', fecha: '2026-11-23',
            total: '116.00', moneda: 'MXN', etapa: 'Sin timbrar'
        };
    });
}
function lote(ids) {
    return {
        lote_id: TOKEN, estado: 'pendiente',
        resultados: ids.map(function (id) {
            return { factura_id: Number(id), folio: String(id), estado: 'pendiente', mensaje: '', uuid: '' };
        })
    };
}
function resumen(loteActual) {
    const r = { total: loteActual.resultados.length, pendientes: 0, procesando: 0, timbradas: 0, omitidas: 0, rechazadas: 0, revision_requerida: 0 };
    const mapa = { pendiente: 'pendientes', procesando: 'procesando', timbrada: 'timbradas', omitida: 'omitidas', rechazada: 'rechazadas', revision_requerida: 'revision_requerida' };
    loteActual.resultados.forEach(function (fila) { r[mapa[fila.estado]]++; });
    return Object.assign({}, loteActual, { resumen: r });
}

async function fixture(browser, opciones) {
    const opts = opciones || {};
    const context = await browser.newContext({ viewport: { width: opts.ancho || 1280, height: 900 } });
    const page = await context.newPage();
    page.setDefaultTimeout(6000);
    const errores = [];
    page.on('pageerror', function (error) { errores.push(error.message); });
    const datos = filas(opts.cantidad || 3);
    if (opts.inyeccion) datos[0].cliente = '<img src=x onerror="window.inyectado=1">';
    if (opts.foliosCompuestos) {
        datos[0].serie = ' SERIE ';
        datos[0].folio = ' SERIE-000002 ';
        datos[1].serie = ' SERIE ';
        datos[1].folio = ' SERIE 000003 ';
        datos[2].serie = ' SERIE ';
        datos[2].folio = ' SERIE ';
    }
    const estado = {
        llamadas: [], lote: opts.lote || null, procesadas: [], falladas: new Set(),
        peticionesEnCurso: 0, maxEnCurso: 0, claves: [], falloLista: Boolean(opts.falloLista)
    };
    await page.route('**/*', async function (route) {
        const request = route.request();
        const url = new URL(request.url());
        if (url.origin !== 'http://facturacion.test') {
            solicitudesExternas++;
            await route.abort();
            return;
        }
        if (request.method() === 'GET' && url.pathname === '/index.php') {
            const config = JSON.parse(entradas.html.match(/<script type="application\/json" id="tm-config">([\s\S]*?)<\/script>/)[1]);
            config.lote_id = url.searchParams.get('lote_id') || '';
            if (opts.endpointExterno) config.endpoint = 'https://otro.test/timbrar';
            const html = entradas.html.replace(/(<script type="application\/json" id="tm-config">)[\s\S]*?(<\/script>)/,
                function (_, inicio, fin) { return inicio + JSON.stringify(config) + fin; });
            await route.fulfill({
                contentType: 'text/html',
                body: '<!doctype html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head><body>' +
                    html + '<script src="/js/fc_factura/timbrado_masivo.js"></script></body></html>'
            });
            return;
        }
        if (url.pathname === '/js/fc_factura/timbrado_masivo.js') {
            await route.fulfill({ contentType: 'text/javascript', body: entradas.js });
            return;
        }
        if (url.pathname === '/css/fc_factura/timbrado_masivo.css') {
            await route.fulfill({ contentType: 'text/css', body: entradas.css });
            return;
        }
        if (request.method() !== 'POST' || url.pathname !== '/index.php') {
            await route.fulfill({ status: 404, body: '' });
            return;
        }
        const p = Object.fromEntries(new URLSearchParams(request.postData()));
        estado.llamadas.push(p);
        assert.equal(p.csrf, 'csrf-ficticio');
        const respuesta = async function (data, status) {
            await route.fulfill({ status: status || 200, contentType: 'application/json', body: JSON.stringify(data) });
        };
        if (p.operacion === 'listar') {
            if (estado.falloLista) {
                estado.falloLista = false;
                await respuesta({ ok: false, mensaje: 'No disponible <script>window.inyectado=1</script>' }, 503);
                return;
            }
            const filtros = JSON.parse(p.filtros);
            const filtradas = filtros.rfc === 'filtro' ? datos.slice(0, 2) : datos;
            const pagina = Number(p.pagina);
            await respuesta({ ok: true, datos: {
                filas: filtradas.slice((pagina - 1) * 25, pagina * 25),
                total: filtradas.length, pagina: pagina, paginas: Math.ceil(filtradas.length / 25), maximo: 100,
                seleccion_ids: filtradas.length <= 100 ? filtradas.map(function (fila) { return fila.id; }) : []
            } });
        } else if (p.operacion === 'crear') {
            estado.claves.push(p.clave_solicitud);
            assert.match(p.clave_solicitud, /^[a-f0-9]{32}$/);
            const ids = JSON.parse(p.factura_ids);
            if (opts.cambioSeleccion && !estado.falladas.has('seleccion')) {
                estado.falladas.add('seleccion');
                const nueva = filas(datos.length + 1).at(-1);
                datos.push(nueva);
            }
            if (p.modo === 'todas') {
                const filtros = JSON.parse(p.filtros);
                const filtradas = filtros.rfc === 'filtro' ? datos.slice(0, 2) : datos;
                const esperados = filtradas.map(function (fila) { return String(fila.id); }).sort();
                const recibidos = ids.map(String).sort();
                if (JSON.stringify(esperados) !== JSON.stringify(recibidos)) {
                    await respuesta({ ok: false, mensaje: 'La selección cambió. Aplica los filtros de nuevo y revisa las facturas.' }, 422);
                    return;
                }
            }
            if (ids.length > 100) {
                await respuesta({ ok: false, mensaje: 'Supera 100 facturas.' }, 422);
                return;
            }
            if (!estado.lote) estado.lote = lote(ids);
            if (opts.falloCrear && !estado.falladas.has('crear')) {
                estado.falladas.add('crear');
                await route.abort('failed');
                return;
            }
            await respuesta({ ok: true, datos: resumen(estado.lote) });
        } else if (p.operacion === 'estado') {
            await respuesta({ ok: true, datos: resumen(estado.lote) });
        } else if (p.operacion === 'procesar') {
            estado.peticionesEnCurso++;
            estado.maxEnCurso = Math.max(estado.maxEnCurso, estado.peticionesEnCurso);
            assert.match(p.factura_id, /^[1-9][0-9]*$/);
            const fila = estado.lote.resultados.find(function (r) { return String(r.factura_id) === p.factura_id; });
            assert.ok(fila, 'La factura debe pertenecer al lote.');
            assert.equal(fila.estado, 'pendiente', 'La UI no debe reenviar una factura ya resuelta.');
            await new Promise(function (resolve) { setTimeout(resolve, opts.demora || 15); });
            fila.estado = opts.rechazar === fila.factura_id ? 'rechazada' : 'timbrada';
            fila.mensaje = fila.estado === 'rechazada' ? '<img src=x onerror="window.inyectado=1">' : 'Timbrado ficticio';
            fila.uuid = fila.estado === 'timbrada' ? 'uuid-ficticio-' + fila.factura_id : '';
            estado.procesadas.push(fila.factura_id);
            estado.peticionesEnCurso--;
            if (opts.falloProcesar === fila.factura_id && !estado.falladas.has(fila.factura_id)) {
                estado.falladas.add(fila.factura_id);
                await route.abort('failed');
                return;
            }
            await respuesta({ ok: true, datos: resumen(estado.lote) });
        } else {
            throw new Error('Operación desconocida: ' + p.operacion);
        }
    });
    await page.goto(URL_BASE + (opts.lote ? '&lote_id=' + TOKEN : ''));
    return {
        page, estado,
        cerrar: async function () {
            assert.deepEqual(errores, []);
            await context.close();
        }
    };
}

async function listaLista(page) {
    await page.waitForFunction(function () {
        const texto = document.getElementById('tm-conteo').textContent;
        return texto.includes('encontradas') && !document.getElementById('tm-filtrar').disabled;
    });
}
async function seleccionarYConfirmar(page) {
    await listaLista(page);
    await page.locator('#tm-pagina-todas').check();
    await page.locator('#tm-revisar').click();
    await page.locator('#tm-confirmar').click();
}
async function terminado(page) {
    await page.waitForFunction(function () {
        return !document.getElementById('tm-consultar').disabled &&
            document.getElementById('tm-reanudar').disabled &&
            !document.getElementById('tm-resultados').hidden;
    });
}

(async function () {
    const launch = { headless: true };
    if (process.env.CHROME_BIN) launch.executablePath = process.env.CHROME_BIN;
    else if (!fs.existsSync(chromium.executablePath()) && fs.existsSync('/opt/google/chrome/chrome')) {
        launch.executablePath = '/opt/google/chrome/chrome';
    }
    const browser = await chromium.launch(launch);
    try {
        {
            const f = await fixture(browser, { cantidad: 30 });
            await listaLista(f.page);
            assert.equal(await f.page.locator('#tm-filas tr').first().locator('td').nth(1).innerText(), 'F-1');
            assert.equal(await f.page.locator('#tm-filas input').first().getAttribute('aria-label'), 'Seleccionar factura F-1');
            await f.page.locator('#tm-filas input').first().check();
            await f.page.locator('#tm-siguiente').click();
            await f.page.waitForFunction(function () { return document.getElementById('tm-pagina-estado').textContent === 'Página 2 de 2'; });
            await f.page.locator('#tm-filas input').first().check();
            assert.match(await f.page.locator('#tm-seleccion-detalle').innerText(), /2 facturas seleccionadas/);
            await f.page.locator('#tm-rfc').fill('filtro');
            assert.equal(await f.page.locator('#tm-revisar').isDisabled(), true);
            await f.page.locator('#tm-filtrar').click();
            await listaLista(f.page);
            assert.match(await f.page.locator('#tm-conteo').innerText(), /^2 facturas/);
            assert.equal(await f.page.locator('#tm-revisar').isDisabled(), true);
            assert.equal(f.estado.llamadas.some(function (r) { return r.operacion === 'crear'; }), false);
            casos.push('seleccion_entre_paginas_y_reset_por_filtros');
            await f.cerrar();
        }
        {
            const f = await fixture(browser, { cantidad: 30 });
            await listaLista(f.page);
            await f.page.locator('#tm-todas').click();
            await f.page.locator('#tm-revisar').click();
            assert.match(await f.page.locator('#tm-confirmacion-resumen').innerText(), /30 facturas/);
            assert.equal(f.estado.llamadas.some(function (r) { return r.operacion === 'crear'; }), false);
            await f.page.locator('#tm-cancelar').click();
            await f.page.locator('#tm-revisar').click();
            await f.page.locator('#tm-confirmar').click();
            await terminado(f.page);
            assert.equal(f.estado.llamadas.find(function (r) { return r.operacion === 'crear'; }).modo, 'todas');
            assert.deepEqual(
                JSON.parse(f.estado.llamadas.find(function (r) { return r.operacion === 'crear'; }).factura_ids),
                filas(30).map(function (fila) { return String(fila.id); })
            );
            assert.equal(f.estado.procesadas.length, 30);
            assert.equal(f.estado.maxEnCurso, 1);
            assert.ok(f.page.url().includes('lote_id=' + TOKEN));
            assert.equal(await f.page.locator('#tm-pendientes').isVisible(), false);
            casos.push('todas_filtradas_confirmacion_y_procesamiento_secuencial');
            await f.cerrar();
        }
        {
            const f = await fixture(browser, { cantidad: 105 });
            await listaLista(f.page);
            assert.equal(await f.page.locator('#tm-todas').isDisabled(), true);
            await f.page.locator('#tm-pagina-todas').check();
            assert.match(await f.page.locator('#tm-seleccion-detalle').innerText(), /25 facturas/);
            assert.match(await f.page.locator('#tm-seleccion-detalle').innerText(), /máximo de 100/);
            for (let pagina = 2; pagina <= 4; pagina++) {
                await f.page.locator('#tm-siguiente').click();
                await f.page.waitForFunction(function (numeroPagina) {
                    return document.getElementById('tm-pagina-estado').textContent === 'Página ' + numeroPagina + ' de 5';
                }, pagina);
                await f.page.locator('#tm-pagina-todas').check();
            }
            await f.page.locator('#tm-siguiente').click();
            await f.page.waitForFunction(function () { return document.getElementById('tm-pagina-estado').textContent === 'Página 5 de 5'; });
            await f.page.locator('#tm-pagina-todas').click();
            assert.match(await f.page.locator('#tm-seleccion-detalle').innerText(), /100 facturas seleccionadas/);
            assert.equal(await f.page.locator('#tm-pagina-todas').isChecked(), false);
            assert.match(await f.page.locator('#tm-aviso').innerText(), /superaría/);
            casos.push('maximo_no_trunca_todas_y_seleccion_pagina_explicita');
            await f.cerrar();
        }
        {
            const f = await fixture(browser, { cantidad: 3, rechazar: 2, inyeccion: true, foliosCompuestos: true });
            await listaLista(f.page);
            const foliosEsperados = ['SERIE-000002', 'SERIE 000003', 'SERIE'];
            for (let i = 0; i < foliosEsperados.length; i++) {
                assert.equal(await f.page.locator('#tm-filas tr').nth(i).locator('td').nth(1).innerText(), foliosEsperados[i]);
                assert.equal(await f.page.locator('#tm-filas input').nth(i).getAttribute('aria-label'),
                    'Seleccionar factura ' + foliosEsperados[i]);
            }
            await capturar(f.page, 'pendientes-escritorio');
            await seleccionarYConfirmar(f.page);
            await terminado(f.page);
            assert.deepEqual(f.estado.procesadas, [1, 2, 3]);
            assert.match(await f.page.locator('#tm-resumen').innerText(), /Timbradas: 2/);
            assert.match(await f.page.locator('#tm-resumen').innerText(), /Rechazadas: 1/);
            assert.equal(await f.page.locator('#tm-resultados-filas img').count(), 0);
            assert.equal(await f.page.evaluate(function () { return window.inyectado; }), undefined);
            assert.equal(f.estado.llamadas.find(function (r) { return r.operacion === 'crear'; }).modo, 'ids');
            await capturar(f.page, 'resultado-parcial-escritorio');
            await f.page.setViewportSize({ width: 390, height: 900 });
            await capturar(f.page, 'resultado-parcial-movil');
            casos.push('exito_parcial_mensajes_texto_y_sin_reintentar_rechazos');
            await f.cerrar();
        }
        {
            const f = await fixture(browser, { cantidad: 2, falloProcesar: 1 });
            await seleccionarYConfirmar(f.page);
            await f.page.waitForFunction(function () {
                return document.getElementById('tm-aviso').textContent.includes('perdió la conexión') &&
                    !document.getElementById('tm-reanudar').disabled;
            });
            assert.deepEqual(f.estado.procesadas, [1]);
            await f.page.locator('#tm-reanudar').click();
            await terminado(f.page);
            assert.deepEqual(f.estado.procesadas, [1, 2]);
            const operaciones = f.estado.llamadas.map(function (r) { return r.operacion; });
            assert.deepEqual(operaciones.slice(2), ['procesar', 'estado', 'procesar']);
            casos.push('red_perdida_consulta_estado_antes_de_reanudar_sin_duplicar');
            await f.cerrar();
        }
        {
            const f = await fixture(browser, { lote: lote([1, 2]) });
            await f.page.waitForFunction(function () { return !document.getElementById('tm-reanudar').disabled; });
            assert.deepEqual(f.estado.llamadas.map(function (r) { return r.operacion; }), ['estado']);
            await f.page.reload();
            await f.page.waitForFunction(function () { return !document.getElementById('tm-reanudar').disabled; });
            assert.deepEqual(f.estado.procesadas, []);
            await f.page.locator('#tm-reanudar').click();
            await terminado(f.page);
            assert.deepEqual(f.estado.procesadas, [1, 2]);
            casos.push('recarga_recupera_lote_sin_emision_automatica');
            await f.cerrar();
        }
        {
            const inicial = lote([1, 2]);
            inicial.resultados[0].estado = 'procesando';
            const f = await fixture(browser, { lote: inicial });
            await f.page.waitForFunction(function () { return !document.getElementById('tm-reanudar').disabled; });
            await f.page.locator('#tm-reanudar').click();
            await f.page.waitForFunction(function () { return !document.getElementById('tm-consultar').disabled; });
            assert.deepEqual(f.estado.procesadas, []);
            assert.match(await f.page.locator('#tm-aviso').innerText(), /factura en proceso/);
            f.estado.lote.resultados[0].estado = 'revision_requerida';
            await f.page.locator('#tm-reanudar').click();
            await terminado(f.page);
            assert.deepEqual(f.estado.procesadas, [2]);
            casos.push('en_proceso_bloquea_siguiente_y_revision_no_reintenta');
            await f.cerrar();
        }
        {
            const f = await fixture(browser, { cantidad: 2, falloCrear: true });
            await seleccionarYConfirmar(f.page);
            await f.page.waitForFunction(function () {
                return document.getElementById('tm-aviso').textContent.includes('perdió la conexión') &&
                    !document.getElementById('tm-confirmar').disabled;
            });
            assert.deepEqual(f.estado.procesadas, []);
            await f.page.locator('#tm-confirmar').click();
            await terminado(f.page);
            assert.equal(f.estado.claves.length, 2);
            assert.equal(f.estado.claves[0], f.estado.claves[1]);
            assert.deepEqual(f.estado.procesadas, [1, 2]);
            casos.push('crear_reintento_manual_conserva_clave_idempotente');
            await f.cerrar();
        }
        {
            const f = await fixture(browser, { cantidad: 2, falloLista: true, ancho: 390 });
            await f.page.waitForFunction(function () { return !document.getElementById('tm-filtrar').disabled; });
            assert.match(await f.page.locator('#tm-aviso').innerText(), /No disponible/);
            assert.equal(await f.page.evaluate(function () { return window.inyectado; }), undefined);
            await f.page.locator('#tm-filtrar').click();
            await listaLista(f.page);
            await capturar(f.page, 'pendientes-movil');
            await f.page.addScriptTag({ content: entradas.js });
            assert.equal(f.estado.llamadas.filter(function (r) { return r.operacion === 'listar'; }).length, 2);
            assert.equal(await f.page.evaluate(function () {
                return document.documentElement.scrollWidth <= window.innerWidth;
            }), true);
            casos.push('error_http_recuperable_inicio_unico_y_ancho_movil');
            await f.cerrar();
        }
        {
            const f = await fixture(browser, { cantidad: 3, cambioSeleccion: true });
            await listaLista(f.page);
            await f.page.locator('#tm-todas').click();
            await f.page.locator('#tm-revisar').click();
            assert.match(await f.page.locator('#tm-confirmacion-resumen').innerText(), /3 facturas/);
            await f.page.locator('#tm-confirmar').click();
            await f.page.waitForFunction(function () {
                return document.getElementById('tm-aviso').textContent.includes('selección cambió') &&
                    !document.getElementById('tm-filtrar').disabled;
            });
            assert.deepEqual(f.estado.llamadas.map(function (r) { return r.operacion; }), ['listar', 'crear']);
            assert.deepEqual(JSON.parse(f.estado.llamadas[1].factura_ids), ['1', '2', '3']);
            assert.deepEqual(f.estado.procesadas, []);
            assert.equal(f.estado.lote, null);
            assert.equal(await f.page.locator('#tm-confirmacion').isVisible(), false);
            assert.equal(await f.page.locator('#tm-revisar').isDisabled(), true);
            assert.equal(await f.page.locator('#tm-todas').isDisabled(), true);
            casos.push('todas_congela_ids_confirmados_y_rechaza_cambio_sin_emitir');
            await f.cerrar();
        }
        {
            const f = await fixture(browser, { endpointExterno: true });
            await f.page.waitForFunction(function () { return document.getElementById('tm-aviso').textContent.includes('no corresponde'); });
            assert.equal(f.estado.llamadas.length, 0);
            assert.equal(await f.page.locator('#tm-confirmar').isDisabled(), true);
            casos.push('endpoint_fuera_del_origen_rechazado');
            await f.cerrar();
        }
        process.stdout.write(JSON.stringify({ estado: 'PASS_TIMBRADO_MASIVO_UI', casos, solicitudes_externas: solicitudesExternas, capturas }));
    } finally {
        await browser.close();
    }
})().catch(function (error) {
    process.stderr.write(error.stack + '\n');
    process.exitCode = 1;
});
