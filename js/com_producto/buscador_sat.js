(function ($) {
    'use strict';
    var input = document.querySelector('#form_com_producto_alta #cat_sat_producto');
    var body = document.getElementById('datos_producto');
    var status = document.getElementById('producto-sat-estado');
    var panel = document.querySelector('.com-producto-resultados');
    if (!input || !body || !status || !panel) { return; }
    var label = document.querySelector('label[for="' + input.id + '"]');
    if (label) { label.textContent = 'Clave o descripción SAT'; }
    input.setAttribute('aria-describedby', 'producto-sat-ayuda producto-sat-estado');
    input.setAttribute('autocomplete', 'off');
    input.required = true;
    var timer;
    var request;
    var sequence = 0;
    function message(text, state) { status.textContent = text; status.dataset.state = state || ''; }
    function clear() { while (body.firstChild) { body.removeChild(body.firstChild); } }
    function showRows(rows) {
        clear();
        rows.slice(0, 20).forEach(function (row) {
            var id = String(row.cat_sat_cve_prod_id || '');
            var code = String(row.cat_sat_cve_prod_codigo || '');
            if (!/^[1-9][0-9]*$/.test(id) || code === '') { return; }
            var description = String(row.cat_sat_cve_prod_descripcion || '');
            var tr = document.createElement('tr');
            var text = document.createElement('td');
            var choice = document.createElement('td');
            var radio = document.createElement('input');
            var radioLabel = document.createElement('label');
            text.textContent = code + ' · ' + description;
            radio.type = 'radio'; radio.name = 'cat_sat_producto_radio'; radio.value = id;
            radio.id = 'sat-producto-opcion-' + id;
            radio.setAttribute('aria-label', 'Seleccionar ' + code + ' · ' + description);
            radioLabel.htmlFor = radio.id;
            radioLabel.appendChild(radio);
            radioLabel.appendChild(document.createTextNode('Elegir'));
            radio.addEventListener('change', function () {
                input.value = code;
                body.querySelectorAll('tr').forEach(function (item) { item.classList.remove('is-selected'); });
                tr.classList.add('is-selected');
                message('Seleccionado: ' + code + ' · ' + description, 'success');
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });
            choice.appendChild(radioLabel); tr.appendChild(text); tr.appendChild(choice); body.appendChild(tr);
        });
        message(body.children.length ? body.children.length + ' resultados. Elige la clave que corresponda.' : 'No encontramos coincidencias. Prueba otra descripción o captura la clave completa.');
    }
    input.addEventListener('input', function () {
        window.clearTimeout(timer);
        sequence += 1;
        var token = sequence;
        if (request) { request.abort(); }
        clear(); panel.setAttribute('aria-busy', 'false');
        var term = input.value.trim();
        if (term.length < 3) { message('Escribe al menos 3 caracteres para buscar.'); return; }
        message('Buscando en el catálogo SAT…', 'loading');
        timer = window.setTimeout(function () {
            if (typeof get_url !== 'function') { message('No se pudo preparar la búsqueda. Recarga la página e inténtalo de nuevo.', 'error'); return; }
            panel.setAttribute('aria-busy', 'true');
            request = $.ajax({ url: get_url('cat_sat_cve_prod', 'get_data_descripcion', {}), data: { data: term, limit: 20 }, dataType: 'json' });
            request.done(function (data) {
                if (token !== sequence) { return; }
                if (!data || !Array.isArray(data.registros)) { message('No se pudo consultar el catálogo SAT. Inténtalo de nuevo.', 'error'); return; }
                showRows(data.registros);
            }).fail(function (xhr, state) {
                if (token !== sequence || state === 'abort') { return; }
                message('No se pudo consultar el catálogo SAT. Revisa tu conexión e inténtalo de nuevo.', 'error');
            }).always(function () { if (token === sequence) { panel.setAttribute('aria-busy', 'false'); } });
        }, 350);
    });
}(jQuery));
