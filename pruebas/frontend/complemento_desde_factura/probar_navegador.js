'use strict';
// Chromium con tráfico interceptado y datos ficticios; no abre aplicación, bases ni PAC.
const fs = require('fs');
const path = require('path');
const assert = require('assert').strict;
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || '/home/em3/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
const entrada = JSON.parse(fs.readFileSync(0, 'utf8'));
const BASE = 'http://facturacion.test/index.php?seccion=fc_factura&accion=complemento_pago&registro_id=7';
const casos = [];
let solicitudesExternas = 0;

async function fixture(browser, opciones) {
    const opts = opciones || {};
    const context = await browser.newContext({ viewport: { width: opts.ancho || 1366, height: 900 } });
    const page = await context.newPage();
    page.setDefaultTimeout(5000);
    const estado = { posts: [], errores: [], navegaciones: [] };
    page.on('pageerror', function (error) { estado.errores.push(error.message); });
    await page.route('**/*', async function (route) {
        const request = route.request();
        const url = new URL(request.url());
        if (url.origin !== 'http://facturacion.test') {
            solicitudesExternas++;
            await route.abort();
            return;
        }
        if (request.method() === 'GET' && url.pathname === '/index.php') {
            if (url.searchParams.get('seccion') === 'fc_complemento_pago') {
                estado.navegaciones.push(url.href);
                await route.fulfill({ contentType: 'text/html', body: '<!doctype html><html lang="es"><title>Complemento ficticio</title><h1 id="destino">Complemento ficticio creado</h1></html>' });
                return;
            }
            let html = opts.ataque ? entrada.html_ataque : opts.vacios ? entrada.html_vacios : entrada.html;
            if (opts.endpointExterno) html = html.replace('index.php?seccion=fc_factura&amp;accion=complemento_pago', 'https://otro.test/index.php?seccion=fc_factura&amp;accion=complemento_pago')
                .replace(/"endpoint":"[^"]*"/, '"endpoint":"https://otro.test/crear"');
            await route.fulfill({ contentType: 'text/html', body: '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Prueba de complemento</title><style>' + entrada.css_base + '</style></head><body>' + html + '<script src="js/fc_factura/complemento_pago.js"></script></body></html>' });
            return;
        }
        if (request.method() === 'POST' && url.pathname === '/index.php') {
            estado.posts.push(Object.fromEntries(new URLSearchParams(request.postData())));
            if (opts.falloRed && estado.posts.length === 1) {
                await route.abort('failed');
                return;
            }
            if (opts.demora) await new Promise(function (resolve) { setTimeout(resolve, opts.demora); });
            if (opts.errorPrimero && estado.posts.length === 1) {
                await route.fulfill({ status: 422, contentType: 'application/json', body: JSON.stringify({ ok: false, mensaje: '<b>El saldo cambió.</b> Revisa el importe.' }) });
                return;
            }
            await route.fulfill({ contentType: 'application/json', body: JSON.stringify({ ok: true, datos: {
                fc_complemento_pago_id: 88,
                link_modifica: opts.redireccionExterna ? 'https://otro.test/robar' : 'index.php?seccion=fc_complemento_pago&accion=modifica&registro_id=88'
            } }) });
            return;
        }
        if (url.pathname.endsWith('/complemento_pago.js')) {
            await route.fulfill({ contentType: 'application/javascript', body: entrada.js });
            return;
        }
        if (url.pathname.endsWith('/complemento_pago.css')) {
            await route.fulfill({ contentType: 'text/css', body: entrada.css });
            return;
        }
        await route.abort();
    });
    await page.goto(BASE);
    await page.waitForFunction(function () { return document.getElementById('vista_fc_factura_complemento_pago').dataset.iniciado === '1'; });
    return { page, estado, cerrar: async function () {
        assert.deepEqual(estado.errores, []);
        await context.close();
    } };
}

async function llenar(page) {
    await page.locator('#cdf-fecha').fill('2026-11-24T10:30');
    await page.locator('#cdf-forma').selectOption('3');
    await page.locator('#cdf-monto').fill('58.00');
}

async function capturar(page, nombre) {
    if (!process.env.UI_CAPTURE_DIR) return;
    const dir = path.resolve(process.env.UI_CAPTURE_DIR);
    fs.mkdirSync(dir, { recursive: true });
    await page.screenshot({ path: path.join(dir, nombre + '.png'), fullPage: true, animations: 'disabled' });
}

(async function () {
    const launch = { headless: true, args: ['--no-sandbox'] };
    if (process.env.CHROME_BIN) launch.executablePath = process.env.CHROME_BIN;
    else if (!fs.existsSync(chromium.executablePath()) && fs.existsSync('/opt/google/chrome/chrome')) {
        launch.executablePath = '/opt/google/chrome/chrome';
    }
    const browser = await chromium.launch(launch);
    try {
        {
            const f = await fixture(browser);
            assert.equal(await f.page.locator('#cdf-monto').inputValue(), '116.00');
            assert.equal(await f.page.locator('#cdf-fecha').inputValue(), '');
            assert.equal(await f.page.locator('#cdf-forma option[value="99"]').count(), 0);
            assert.match(await f.page.locator('.cdf-referencia').innerText(), /F-000007/);
            assert.equal(f.estado.posts.length, 0);
            casos.push('precarga_referencia_y_saldo_sin_operaciones_automaticas');
            await f.cerrar();
        }
        {
            for (const ancho of [320, 390, 1366]) {
                const f = await fixture(browser, { ancho });
                assert.equal(await f.page.evaluate(function () { return document.documentElement.scrollWidth <= innerWidth + 1; }), true, 'desbordamiento a ' + ancho);
                for (const id of ['cdf-monto', 'cdf-fecha', 'cdf-forma', 'cdf-cambio', 'cdf-csd']) {
                    const box = await f.page.locator('#' + id).boundingBox();
                    assert.ok(box.width > 100 && box.x >= 0 && box.x + box.width <= ancho + 1, id + ' fuera de viewport');
                }
                await capturar(f.page, 'complemento-' + ancho);
                await f.cerrar();
            }
            casos.push('responsive_320_390_1366_sin_desbordamiento');
        }
        {
            const f = await fixture(browser, { ataque: true });
            assert.equal(await f.page.evaluate(function () { return window.inyectado; }), undefined);
            assert.equal(await f.page.locator('.cdf-referencia img').count(), 0);
            assert.match(await f.page.locator('.cdf-referencia').innerText(), /<img/);
            casos.push('datos_factura_y_catalogos_escapados');
            await f.cerrar();
        }
        {
            const f = await fixture(browser, { demora: 150 });
            await llenar(f.page);
            await f.page.locator('#cdf-guardar').click();
            assert.equal(f.estado.posts.length, 0);
            await f.page.locator('#cdf-confirmar').check();
            await f.page.addScriptTag({ content: entrada.js });
            await f.page.locator('#cdf-guardar').evaluate(function (button) { button.click();button.click(); });
            await f.page.waitForSelector('#destino');
            assert.equal(f.estado.posts.length, 1);
            assert.deepEqual(Object.keys(f.estado.posts[0]).sort(), ['csrf','clave_solicitud','monto','fecha_pago','cat_sat_forma_pago_id','com_tipo_cambio_id','fc_csd_id'].sort());
            assert.equal(f.estado.posts[0].monto, '58.00');
            assert.equal(f.estado.posts[0].csrf, 'csrf-ficticio');
            assert.match(f.estado.navegaciones[0], /seccion=fc_complemento_pago.*registro_id=88/);
            casos.push('confirmacion_obligatoria_doble_click_y_doble_script_solo_un_post');
            await f.cerrar();
        }
        {
            const f = await fixture(browser, { errorPrimero: true });
            await llenar(f.page);await f.page.locator('#cdf-confirmar').check();await f.page.locator('#cdf-guardar').click();
            await f.page.waitForFunction(function () { return !document.getElementById('cdf-guardar').disabled; });
            assert.equal(await f.page.locator('#cdf-monto').inputValue(), '58.00');
            assert.equal(await f.page.locator('#cdf-fecha').inputValue(), '2026-11-24T10:30');
            assert.equal(await f.page.locator('#cdf-aviso b').count(), 0);
            assert.match(await f.page.locator('#cdf-aviso').innerText(), /<b>El saldo cambió/);
            await f.page.locator('#cdf-guardar').click();await f.page.waitForSelector('#destino');
            assert.equal(f.estado.posts.length, 2);
            assert.equal(f.estado.posts[0].clave_solicitud, f.estado.posts[1].clave_solicitud);
            assert.equal(f.estado.posts[1].monto, '58.00');
            casos.push('error_conserva_datos_y_clave_para_reintento');
            await f.cerrar();
        }
        {
            const f = await fixture(browser);
            await llenar(f.page);await f.page.locator('#cdf-confirmar').check();await f.page.locator('#cdf-cambio').selectOption('2');
            assert.equal(await f.page.locator('#cdf-monto').inputValue(), '');
            assert.equal(await f.page.locator('#cdf-moneda-pago').innerText(), 'USD');
            assert.equal(await f.page.locator('#cdf-confirmar').isChecked(), false);
            await f.page.locator('#cdf-monto').fill('2.90');await f.page.locator('#cdf-cambio').selectOption('4');
            assert.equal(await f.page.locator('#cdf-monto').inputValue(), '2.90');
            assert.equal(f.estado.posts.length, 0);
            casos.push('cambio_moneda_limpia_importe_sin_convertir_y_misma_moneda_conserva');
            await f.cerrar();
        }
        {
            const f = await fixture(browser, { endpointExterno: true });
            assert.equal(await f.page.locator('#cdf-guardar').isDisabled(), true);
            await f.page.locator('#cdf-formulario').evaluate(function (form) { form.requestSubmit(); });
            assert.match(await f.page.locator('#cdf-aviso').innerText(), /no corresponde/);
            assert.equal(f.estado.posts.length, 0);
            casos.push('endpoint_externo_bloqueado_sin_peticiones');
            await f.cerrar();
        }
        {
            const f = await fixture(browser, { redireccionExterna: true });
            await llenar(f.page);await f.page.locator('#cdf-confirmar').check();await f.page.locator('#cdf-guardar').click();
            await f.page.waitForFunction(function () { return document.getElementById('cdf-aviso').textContent.includes('no se pudo abrir'); });
            assert.equal(await f.page.locator('#cdf-guardar').isDisabled(), true);
            assert.equal(f.page.url(), BASE);
            assert.equal(f.estado.posts.length, 1);
            casos.push('redireccion_externa_bloqueada_despues_de_crear');
            await f.cerrar();
        }
        {
            const f = await fixture(browser, { falloRed: true });
            await llenar(f.page);await f.page.locator('#cdf-confirmar').check();await f.page.locator('#cdf-guardar').click();
            await f.page.waitForFunction(function () { return document.getElementById('cdf-aviso').textContent.includes('Se perdió la conexión'); });
            assert.equal(f.estado.posts.length, 1);
            assert.equal(await f.page.locator('#cdf-monto').inputValue(), '58.00');
            await f.page.locator('#cdf-guardar').click();await f.page.waitForSelector('#destino');
            assert.equal(f.estado.posts.length, 2);
            assert.equal(f.estado.posts[0].clave_solicitud, f.estado.posts[1].clave_solicitud);
            casos.push('fallo_red_conserva_clave_y_no_reintenta_automaticamente');
            await f.cerrar();
        }
        {
            const f = await fixture(browser, { vacios: true });
            assert.equal(await f.page.locator('#cdf-guardar').isDisabled(), true);
            assert.equal(f.estado.posts.length, 0);
            casos.push('catalogos_ausentes_impiden_guardar');
            await f.cerrar();
        }
        process.stdout.write(JSON.stringify({ estado: 'PASS_COMPLEMENTO_DESDE_FACTURA_UI', casos, solicitudes_externas: solicitudesExternas }));
    } finally { await browser.close(); }
})().catch(function (error) { process.stderr.write(error.stack + '\n');process.exitCode = 1; });
