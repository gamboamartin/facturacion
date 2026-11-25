(function () {
    'use strict';
    var form = document.querySelector('.com-tipo-form');
    if (!form) { return; }
    var input = form.querySelector('[name="descripcion"]');
    if (input) {
        var label = form.querySelector('label[for="' + input.id + '"]');
        if (label) { label.textContent = 'Nombre del tipo de cliente'; }
        input.placeholder = 'Ej. Residencial'; input.minLength = 3; input.maxLength = 200;
        input.setAttribute('aria-describedby', 'tipo-nombre-ayuda');
        input.addEventListener('input', function () {
            input.setCustomValidity(input.value && input.value.trim().length < 3 ? 'Escribe un nombre de al menos 3 caracteres.' : '');
        });
    }
    var code = form.querySelector('[name="codigo"]');
    if (code && code.disabled) {
        var codeLabel = form.querySelector('label[for="' + code.id + '"]');
        if (codeLabel) { codeLabel.textContent = 'Código automático'; }
        var codeHelp = document.getElementById('tipo-codigo-ayuda');
        if (!codeHelp) {
            codeHelp = document.createElement('p');
            codeHelp.id = 'tipo-codigo-ayuda'; codeHelp.className = 'com-cliente-help';
            codeHelp.textContent = 'El sistema genera este código.';
            code.insertAdjacentElement('afterend', codeHelp);
        }
        var describedBy = (code.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean);
        if (describedBy.indexOf(codeHelp.id) === -1) { describedBy.push(codeHelp.id); }
        code.setAttribute('aria-describedby', describedBy.join(' '));
    }
    var dirty = false;
    var submitting = false;
    form.addEventListener('input', function (event) { if (event.target.name) { dirty = true; } });
    form.addEventListener('change', function (event) { if (event.target.name) { dirty = true; } });
    form.addEventListener('submit', function (event) {
        if (submitting) { event.preventDefault(); event.stopImmediatePropagation(); return; }
        submitting = true;
    }, true);
    window.addEventListener('beforeunload', function (event) {
        if (dirty && !submitting) { event.preventDefault(); event.returnValue = ''; }
    });
}());
