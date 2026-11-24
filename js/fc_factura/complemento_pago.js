(function () {
    'use strict';

    function iniciar() {
        const root = document.getElementById('vista_fc_factura_complemento_pago');
        if (!root || root.dataset.iniciado === '1') return;
        root.dataset.iniciado = '1';
        const el = function (id) { return root.querySelector('#cdf-' + id); };
        const formulario = el('formulario');
        const boton = el('guardar');
        let config, endpoint, ocupado = false, creado = false;

        function aviso(texto, tipo) {
            el('aviso').textContent = texto;
            el('aviso').dataset.tipo = tipo || 'info';
            el('aviso').hidden = !texto;
            if (texto) el('aviso').focus();
        }

        function urlLocal(valor) {
            if (typeof valor !== 'string' || !valor || /[\u0000-\u0020\u007f\\]/.test(valor)) {
                throw new Error('La dirección del complemento no es válida.');
            }
            const url = new URL(valor, window.location.href);
            if (url.origin !== window.location.origin || !/^https?:$/.test(url.protocol)) {
                throw new Error('La dirección del complemento no corresponde a esta aplicación.');
            }
            return url;
        }

        try {
            config = JSON.parse(el('config').textContent);
            endpoint = urlLocal(config.endpoint);
            if (typeof config.csrf !== 'string' || !config.csrf ||
                typeof config.clave_solicitud !== 'string' || !config.clave_solicitud) {
                throw new Error('No se pudo preparar la sesión del formulario. Actualiza la pantalla.');
            }
            formulario.action = endpoint.href;
        } catch (error) {
            aviso(error.message || 'No se pudo preparar el complemento de pago.', 'error');
            formulario.querySelectorAll('input, select, button').forEach(function (campo) { campo.disabled = true; });
            formulario.addEventListener('submit', function (event) { event.preventDefault(); });
            return;
        }

        function monedaSeleccionada() {
            const opcion = el('cambio').selectedOptions[0];
            return opcion && opcion.dataset.moneda ? opcion.dataset.moneda : config.moneda_factura;
        }

        let monedaAnterior = monedaSeleccionada();
        el('moneda-pago').textContent = monedaAnterior || '';
        if (monedaAnterior && monedaAnterior !== config.moneda_factura) {
            el('monto').value = '';
            el('monto-ayuda').textContent = 'Captura el importe recibido en ' + monedaAnterior + '. El saldo de referencia está expresado en la moneda de la factura.';
        }
        el('cambio').addEventListener('change', function () {
            const moneda = monedaSeleccionada();
            el('moneda-pago').textContent = moneda || '';
            if (moneda !== monedaAnterior) {
                el('monto').value = '';
                el('confirmar').checked = false;
                el('monto-ayuda').textContent = 'Cambiaste la moneda del pago. Captura y confirma el importe recibido en ' + moneda + '.';
            }
            monedaAnterior = moneda;
        });

        boton.disabled = formulario.dataset.catalogosDisponibles !== '1';
        formulario.addEventListener('input', function (event) {
            if (event.target !== el('confirmar')) el('confirmar').checked = false;
        });
        formulario.addEventListener('change', function (event) {
            if (event.target !== el('confirmar')) el('confirmar').checked = false;
        });
        formulario.addEventListener('submit', async function (event) {
            event.preventDefault();
            if (ocupado || creado || !formulario.reportValidity()) return;
            const body = new URLSearchParams();
            body.set('csrf', config.csrf);
            body.set('clave_solicitud', config.clave_solicitud);
            ['monto', 'fecha_pago', 'cat_sat_forma_pago_id', 'com_tipo_cambio_id'].forEach(function (campo) {
                body.set(campo, formulario.elements.namedItem(campo).value);
            });
            const csd = formulario.elements.namedItem('fc_csd_id');
            if (csd) body.set('fc_csd_id', csd.value);
            const habilitados = Array.from(formulario.querySelectorAll('input, select, button')).filter(function (campo) {
                return !campo.disabled;
            });
            ocupado = true;
            formulario.setAttribute('aria-busy', 'true');
            habilitados.forEach(function (campo) { campo.disabled = true; });
            boton.textContent = 'Guardando complemento…';
            aviso('Se está creando el complemento con los datos confirmados.', 'info');
            try {
                let response;
                try {
                    response = await fetch(endpoint.href, {
                        method: 'POST', credentials: 'same-origin',
                        headers: { 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
                        body: body.toString()
                    });
                } catch (error) {
                    throw new Error('Se perdió la conexión. Revisa los complementos antes de continuar. Si vuelves a guardar desde esta pantalla, se conservará la misma solicitud.');
                }
                let resultado;
                try { resultado = await response.json(); }
                catch (error) { throw new Error('No se recibió una respuesta válida. Revisa la lista de complementos antes de volver a guardar.'); }
                if (!response.ok || !resultado || resultado.ok !== true) {
                    throw new Error(resultado && typeof resultado.mensaje === 'string'
                        ? resultado.mensaje.slice(0, 1000) : 'No se pudo crear el complemento. Revisa los datos y los permisos de tu usuario.');
                }
                creado = true;
                const datos = resultado.datos;
                if (!datos || !/^[1-9][0-9]*$/.test(String(datos.fc_complemento_pago_id))) {
                    throw new Error('El complemento se guardó, pero no se pudo abrir. Revisa la lista de complementos antes de volver a crear uno.');
                }
                const destino = urlLocal(datos.link_modifica);
                aviso('Complemento creado. Abriendo su pantalla de revisión…', 'exito');
                window.location.assign(destino.href);
            } catch (error) {
                aviso(creado
                    ? 'El complemento se creó, pero no se pudo abrir. Revisa la lista de complementos antes de volver a crear uno.'
                    : error.message || 'No se pudo completar la operación.', 'error');
            } finally {
                ocupado = false;
                formulario.removeAttribute('aria-busy');
                if (!creado) {
                    habilitados.forEach(function (campo) { campo.disabled = false; });
                    boton.textContent = 'Crear complemento de pago';
                } else boton.textContent = 'Complemento creado';
            }
        });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', iniciar);
    else iniciar();
})();
