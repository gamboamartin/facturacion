(function ($) {
    'use strict';
    var input = document.querySelector('.com-cliente #documento');
    if (!input) { return; }
    var form = input.form;
    var status = document.getElementById('cliente-cif-estado');
    input.setAttribute('accept', 'application/pdf,.pdf');
    input.setAttribute('aria-describedby', 'cliente-cif-estado');
    function updateStatus(state, text) {
        status.dataset.state = state;
        status.textContent = text;
    }
    var lockedControls = [];
    function busy(value) {
        form.setAttribute('aria-busy', String(value));
        if (value) {
            lockedControls = Array.from(form.querySelectorAll('input, select, textarea, button')).map(function (element) {
                var previous = element.disabled;
                element.disabled = true;
                return { element: element, disabled: previous };
            });
        } else {
            lockedControls.forEach(function (control) { control.element.disabled = control.disabled; });
            lockedControls = [];
        }
        $(form).find('select.selectpicker').selectpicker('refresh');
    }
    async function request(section, action, params, options) {
        var url = new URL(get_url(section, action, {}), window.location.href);
        url.searchParams.set('header', '0');
        Object.keys(params || {}).forEach(function (key) { url.searchParams.set(key, params[key]); });
        var response = await fetch(url.href, options || {});
        if (!response.ok) { throw new Error('No fue posible consultar la información. Intenta de nuevo.'); }
        var result;
        try { result = await response.json(); }
        catch (error) { throw new Error('No fue posible leer la respuesta. Comprueba que tu sesión siga activa.'); }
        if (!result || result.error || result.status === 'error') {
            throw new Error('No fue posible leer el CIF. Verifica que sea un PDF con un código QR del SAT legible.');
        }
        return result;
    }
    function setValue(id, value) {
        var element = document.getElementById(id);
        if (element) { element.value = value === undefined || value === null ? '' : String(value); }
    }
    function setSelect(id, value) {
        var select = $('#' + id);
        select.val(String(value)).selectpicker('refresh');
    }
    function setOptions(id, rows, prefix, selected) {
        var select = document.getElementById(id);
        select.replaceChildren(new Option(id === 'dp_estado_id' ? 'Selecciona un estado' : 'Selecciona un municipio', '-1'));
        rows.forEach(function (row) {
            select.add(new Option(String(row[prefix + '_descripcion']), String(row[prefix + '_id'])));
        });
        setSelect(id, selected);
    }
    function requireValue(value) {
        if (value === undefined || value === null || value === '') { throw new Error('El CIF no contiene todos los datos necesarios para precargar. Puedes completar el formulario manualmente.'); }
        return value;
    }
    function requireOption(id, value) {
        var element = document.getElementById(id);
        if (!Array.from(element.options).some(function (option) { return option.value === String(value); })) {
            throw new Error('No encontramos una equivalencia en los catálogos fiscales. Tus datos capturados se conservaron; puedes continuar manualmente.');
        }
    }
    input.addEventListener('change', async function () {
        var file = input.files[0];
        if (!file) { return; }
        if ((file.type && file.type !== 'application/pdf') || !/\.pdf$/i.test(file.name)) {
            input.value = ''; updateStatus('error', 'Selecciona un archivo PDF de la constancia fiscal.'); return;
        }
        if (form.clientesHasChanges && form.clientesHasChanges() && !window.confirm('La lectura del CIF reemplazará los datos de identidad y domicilio que ya capturaste. ¿Quieres continuar?')) {
            input.value = ''; return;
        }
        busy(true); updateStatus('loading', 'Leyendo ' + file.name + '… Espera un momento.');
        try {
            var data = new FormData(); data.append('documento', file);
            var result = await request('com_cliente', 'leer_qr', { registro_id: -1 }, { method: 'POST', body: data });
            var cif = requireValue(result.data);
            var identity = requireValue(cif.datos_identificacion);
            var address = requireValue(cif.datos_ubicacion);
            var fiscal = requireValue(cif.datos_fiscales);
            requireValue(cif.rfc); requireValue(cif.tipo_persona);
            updateStatus('loading', 'CIF leído. Preparando los datos fiscales y el domicilio…');
            var results = await Promise.all([
                request('cat_sat_tipo_persona', 'get_tipo_persona', { tipo_persona: cif.tipo_persona }),
                request('cat_sat_regimen_fiscal', 'get_regimen_fiscal', { regimen_fiscal: requireValue(fiscal.regimen) }),
                request('dp_municipio', 'get_ubicacion_sat', { municipio: requireValue(address.municipio_o_delegacion) })
            ]);
            var personId = requireValue(results[0].cat_sat_tipo_persona_id);
            var regimeId = requireValue(results[1].cat_sat_regimen_fiscal_id);
            var location = results[2];
            var countryId = requireValue(location.dp_pais_id);
            var stateId = requireValue(location.dp_estado_id);
            var municipalityId = requireValue(location.dp_municipio_id);
            var locations = await Promise.all([
                request('dp_estado', 'get_estado', { dp_pais_id: countryId }),
                request('dp_municipio', 'get_municipio', { dp_estado_id: stateId })
            ]);
            var states = locations[0].registros;
            var municipalities = locations[1].registros;
            if (!Array.isArray(states) || !Array.isArray(municipalities) ||
                !states.some(function (row) { return String(row.dp_estado_id) === String(stateId); }) ||
                !municipalities.some(function (row) { return String(row.dp_municipio_id) === String(municipalityId); })) {
                throw new Error('No fue posible preparar el domicilio. Tus datos capturados se conservaron; puedes continuar manualmente.');
            }
            requireOption('cat_sat_tipo_persona_id', personId);
            requireOption('cat_sat_regimen_fiscal_id', regimeId);
            requireOption('dp_pais_id', countryId);
            var name = cif.tipo_persona === 'PERSONA FISICA' ? [identity.nombre, identity.apellido_paterno, identity.apellido_materno].filter(Boolean).join(' ') : identity.denominacion_o_razon_social;
            requireValue(name);
            // Apply only after every lookup succeeds, preserving typed data on failures.
            setValue('codigo', cif.rfc); setValue('rfc', cif.rfc); setValue('razon_social', name);
            setSelect('cat_sat_tipo_persona_id', personId); setSelect('cat_sat_regimen_fiscal_id', regimeId);
            setSelect('dp_pais_id', countryId);
            setOptions('dp_estado_id', states, 'dp_estado', stateId);
            setOptions('dp_municipio_id', municipalities, 'dp_municipio', municipalityId);
            ['cp', 'colonia', 'numero_exterior', 'numero_interior'].forEach(function (id) { setValue(id, address[id]); });
            setValue('calle', address.nombre_de_la_vialidad);
            form.dispatchEvent(new Event('clientes:prefill'));
            updateStatus('success', 'Datos precargados. Revisa el RFC, régimen fiscal y domicilio; completa el resto y guarda al cliente.');
        } catch (error) {
            updateStatus('error', error.message || 'No fue posible leer el CIF. Puedes continuar con la captura manual.');
        } finally {
            input.value = ''; busy(false);
        }
    });
}(jQuery));
