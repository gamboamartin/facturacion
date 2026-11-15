/*
 * Pure regression for js/fc_nota_credito/modifica.js, no DOM, network or database.
 * Node: node tests/regression/nc_totals_projection.js
 * Other V8 host: evaluate this file, then call testNcTotalsProjection(sourceText)
 * with the unchanged production JS read from the checkout.
 * Contract: fc_partida_nc joins fc_nota_credito; _partida::alta_bd rereads
 * registro_obj after regenera_totales. Header uses persisted NC totals, not
 * client-side accumulation. total_descuento maps to the header descuento.
 */
function testNcTotalsProjection(sourceText) {
    const marker = 'let session_id';
    const boundary = sourceText.indexOf(marker);
    if (boundary < 0) throw new Error('Production source boundary missing');
    // Compile the whole file without executing UI setup or handlers.
    new Function(sourceText);
    const project = new Function(sourceText.slice(0, boundary) +
        '; return nc_totales_respuesta;')();
    const assert = function(condition, message) {
        if (!condition) throw new Error(message);
    };
    const base = {
        registro_puro: {total_retenciones: 0},
        registro_obj: {
            fc_nota_credito_sub_total: '100', fc_nota_credito_total_descuento: '0',
            fc_nota_credito_total_traslados: '16', fc_nota_credito_total_retenciones: '0',
            fc_nota_credito_total: '116'
        }
    };
    const expected = {
        subtotal: '100.00', descuento: '0.00', impuestos_trasladados: '16.00',
        impuestos_retenidos: '0.00', total: '116.00'
    };
    assert(JSON.stringify(project(base)) === JSON.stringify(expected), 'Exact persisted amounts');
    const numeric = JSON.parse(JSON.stringify(base));
    Object.keys(numeric.registro_obj).forEach(function(key) {
        numeric.registro_obj[key] = Number(numeric.registro_obj[key]);
    });
    assert(JSON.stringify(project(numeric)) === JSON.stringify(expected), 'Numeric amounts including zero');
    let cases = 2;
    for (const value of [null, {}, {registro_obj: {}}, {registro_puro: {}}]) {
        assert(project(value) === null, 'Missing structure');
        cases++;
    }
    for (const key of Object.keys(base.registro_obj)) {
        for (const value of [null, '', ' ', 'NaN', false, {}, Infinity, undefined]) {
            const data = JSON.parse(JSON.stringify(base));
            data.registro_obj[key] = value;
            assert(project(data) === null, 'Invalid header amount: ' + key);
            cases++;
        }
    }
    for (const value of [null, '', undefined, 'bad']) {
        const data = JSON.parse(JSON.stringify(base));
        data.registro_puro.total_retenciones = value;
        assert(project(data) === null, 'Invalid line retention');
        cases++;
    }
    // Repeat responses must project the same totals, never accumulate them.
    assert(JSON.stringify(project(base)) === JSON.stringify(expected), 'Repeat response');
    assert(!sourceText.includes('registro_puro.retenciones'), 'Wrong retention key returned');
    assert(!sourceText.includes('td_fc_partida_sub_total = "<tr>'), 'Nested table row returned');
    assert(sourceText.indexOf('if (!json || (json.error') < sourceText.indexOf('sl_com_producto.val(-1)'),
        'Error handling must precede entry reset');
    return {casesPassed: cases, syntaxPassed: true, sourceInvariantsPassed: true};
}
if (typeof module !== 'undefined' && module.exports) {
    module.exports = testNcTotalsProjection;
    if (require.main === module) {
        const fs = require('fs');
        const path = require('path');
        const source = fs.readFileSync(path.join(__dirname, '../../js/fc_nota_credito/modifica.js'), 'utf8');
        console.log(JSON.stringify(testNcTotalsProjection(source)));
    }
}
