'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const root = process.argv[2];
for (const action of ['alta', 'modifica']) {
    const source = fs.readFileSync(path.join(root, 'js', 'com_agente', `${action}.js`), 'utf8');
    function render(asesor, seleccionado) {
        let cambiar;
        const elements = {
            tipo_agente_asesor_id: {value: String(asesor)},
            num_asesor_container: {style: {}},
            num_asesor: {disabled: false},
            com_tipo_agente_id: {value: String(seleccionado), addEventListener(type, cb) { assert.equal(type, 'change'); cambiar = cb; }}
        };
        const document = {getElementById: id => elements[id], addEventListener(type, cb) { assert.equal(type, 'DOMContentLoaded'); cb(); }};
        vm.runInNewContext(source, {document});
        return {elements, cambiar};
    }
    function verificar(estado, visible) {
        assert.equal(estado.elements.num_asesor_container.style.display, visible ? '' : 'none');
        assert.equal(estado.elements.num_asesor.disabled, !visible);
    }
    const sinConfig = render(-1, -1); verificar(sinConfig, false);
    sinConfig.elements.com_tipo_agente_id.value = '7'; sinConfig.cambiar(); verificar(sinConfig, false);
    verificar(render(0, 0), false);
    verificar(render('incorrecto', 7), false);
    const conConfig = render(7, 7); verificar(conConfig, true);
    conConfig.elements.com_tipo_agente_id.value = '2'; conConfig.cambiar(); verificar(conConfig, false);
    conConfig.elements.com_tipo_agente_id.value = '7'; conConfig.cambiar(); verificar(conConfig, true);
}
console.log('PASS_FORMULARIOS_AGENTES');
