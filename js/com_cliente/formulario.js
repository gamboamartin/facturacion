(function ($) {
    'use strict';
    var form = document.querySelector('.com-cliente-form');
    if (!form) { return; }
    var labels = {
        codigo: 'Código del cliente', rfc: 'RFC', cp: 'Código postal',
        numero_exterior: 'Número exterior', numero_interior: 'Número interior'
    };
    Object.keys(labels).forEach(function (id) {
        var label = form.querySelector('label[for="' + id + '"]');
        if (label) { label.textContent = labels[id]; }
    });
    var placeholders = { codigo: 'Ej. CLI-0001', rfc: 'Ej. XAXX010101000' };
    Object.keys(placeholders).forEach(function (id) {
        var input = form.querySelector('#' + id);
        if (input) { input.setAttribute('placeholder', placeholders[id]); }
    });
    var dirty = false;
    var submitting = false;
    var message = document.getElementById('cliente-form-estado');
    form.addEventListener('input', function (event) { if (event.target.type !== 'file') { dirty = true; } });
    form.addEventListener('change', function (event) { if (event.target.type !== 'file') { dirty = true; } });
    form.addEventListener('clientes:prefill', function () { dirty = true; });
    form.clientesHasChanges = function () { return dirty; };
    window.addEventListener('beforeunload', function (event) {
        if (dirty && !submitting) { event.preventDefault(); event.returnValue = ''; }
    });
    form.addEventListener('submit', function (event) {
        var municipio = form.querySelector('#dp_municipio_id');
        var busy = form.getAttribute('aria-busy') === 'true';
        if (busy || (municipio && (!municipio.value || municipio.value === '-1'))) {
            event.preventDefault(); event.stopImmediatePropagation();
            message.textContent = busy ? 'Espera a que termine la lectura del CIF antes de guardar.' : 'Selecciona el municipio del domicilio fiscal antes de guardar.';
            message.hidden = false; message.scrollIntoView({ block: 'center', behavior: 'smooth' });
            return;
        }
        submitting = true;
        message.hidden = true;
    }, true);
    var rfc = form.querySelector('#rfc');
    if (rfc) { rfc.setAttribute('autocapitalize', 'characters'); rfc.setAttribute('spellcheck', 'false'); }
    var cp = form.querySelector('#cp');
    if (cp) { cp.setAttribute('inputmode', 'numeric'); cp.setAttribute('autocomplete', 'postal-code'); }
    var telefono = form.querySelector('#telefono');
    if (telefono) { telefono.setAttribute('inputmode', 'tel'); telefono.setAttribute('autocomplete', 'tel'); }
    var metodo = $('#cat_sat_metodo_pago_id');
    var forma = $('#cat_sat_forma_pago_id');
    var permitted = window.clientesMetodosPagoPermitidos;
    function validatePayment() {
        if (!permitted || !metodo.length || !forma.length) { return; }
        var method = metodo.find('option:selected').data('cat_sat_metodo_pago_codigo');
        var payment = forma.find('option:selected').data('cat_sat_forma_pago_codigo');
        var options = permitted[method];
        if (!options || !payment) { return; }
        var valid = options.some(function (item) { return String(item) === String(payment); });
        var help = document.getElementById('cliente-pago-ayuda');
        if (!valid) {
            forma.val(null).selectpicker('refresh');
            help.textContent = 'Selecciona una forma de pago compatible con el método elegido.';
            help.classList.add('is-error');
        } else {
            help.textContent = 'La forma de pago es compatible con el método seleccionado.';
            help.classList.remove('is-error');
        }
    }
    metodo.add(forma).on('change.clientes', validatePayment);
    $('.com-cliente-actions a').each(function () {
        var url = new URL(this.href, window.location.href);
        if (url.searchParams.get('seccion') !== 'com_cliente') { return; }
        if (url.searchParams.get('accion') === 'alta') { this.textContent = '+ Nuevo cliente'; }
        if (url.searchParams.get('accion') === 'lista') { this.textContent = 'Volver a clientes'; this.classList.add('com-cliente-action-lista'); }
    });
}(jQuery));
