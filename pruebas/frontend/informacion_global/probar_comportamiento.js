'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync(process.argv[2], 'utf8');

function pagina(cliente, tipo, valores = ['', '', ''], ready = 'complete') {
    const ids = ['com_sucursal_id', 'cat_sat_tipo_de_comprobante_id', 'fc-global-contexto',
        'fc-global-information', 'fc-global-aviso', 'global_periodicidad', 'global_meses', 'global_anio'];
    const elements = Object.fromEntries(ids.map(id => [id, {
        value: '', hidden: true, required: false, disabled: false, listeners: {},
        addEventListener(name, callback) { this.listeners[name] = callback; },
        cambiar(name = 'change') { this.listeners[name](); }
    }]));
    elements.com_sucursal_id.value = cliente;
    elements.cat_sat_tipo_de_comprobante_id.value = tipo;
    elements['fc-global-contexto'].textContent = JSON.stringify({sucursales: ['10'], tipos_ingreso: ['1']});
    ['global_periodicidad', 'global_meses', 'global_anio'].forEach((id, i) => { elements[id].value = valores[i]; });
    const document = {readyState: ready, listeners: {}, getElementById: id => elements[id],
        addEventListener(name, callback) { this.listeners[name] = callback; }};
    const window = {jQuery: element => ({on: (name, callback) => { element.listeners[name] = callback; }})};
    vm.runInNewContext(source, {document, window});
    return {elements, document};
}
function comprobarOculto(page) {
    assert.equal(page.elements['fc-global-information'].hidden, true);
    for (const id of ['global_periodicidad', 'global_meses', 'global_anio']) {
        assert.equal(page.elements[id].value, '');
        assert.equal(page.elements[id].required, false);
        assert.equal(page.elements[id].disabled, false, 'Los vacíos deben enviarse para limpiar el periodo anterior');
    }
}
for (const [cliente, tipo] of [['', ''], ['-1', '1'], ['20', '1'], ['10', '2'], ['10', '']]) {
    comprobarOculto(pagina(cliente, tipo, ['04', '11', '2026']));
}
const global = pagina('10', '1', ['04', '11', '2026']);
assert.equal(global.elements['fc-global-information'].hidden, false);
assert.deepEqual(['global_periodicidad', 'global_meses', 'global_anio'].map(id => global.elements[id].value), ['04', '11', '2026']);
assert.ok(['global_periodicidad', 'global_meses', 'global_anio'].every(id => global.elements[id].required));
global.elements.com_sucursal_id.value = '20';
global.elements.com_sucursal_id.cambiar();
comprobarOculto(global);
assert.equal(global.elements['fc-global-aviso'].hidden, false);
global.elements.com_sucursal_id.value = '10';
global.elements.com_sucursal_id.cambiar('changed.bs.select');
assert.equal(global.elements['fc-global-information'].hidden, false);
assert.equal(global.elements['global_periodicidad'].value, '', 'Regresar al receptor global no inventa periodicidad');
assert.equal(global.elements['global_meses'].value, '');
assert.equal(global.elements['global_anio'].value, '');
assert.equal(global.elements['fc-global-aviso'].hidden, true);
global.elements['global_periodicidad'].value = '01';
global.elements.cat_sat_tipo_de_comprobante_id.value = '2';
global.elements.cat_sat_tipo_de_comprobante_id.cambiar('changed.bs.select');
comprobarOculto(global);
const loaded = pagina('10', '1', ['04', '11', '2026'], 'loading');
assert.equal(loaded.elements['fc-global-information'].hidden, true);
loaded.document.listeners.DOMContentLoaded();
assert.equal(loaded.elements['fc-global-information'].hidden, false);
assert.equal(loaded.elements['global_meses'].value, '11');
console.log(JSON.stringify({estado: 'PASS_GLOBAL_UI_JS', escenarios: 10, limite: 'DOM sintético; no certifica render visual en navegador'}));
