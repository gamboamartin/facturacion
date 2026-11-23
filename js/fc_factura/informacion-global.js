(function () {
    'use strict';

    function iniciar() {
        const bloque = document.getElementById('fc-global-information');
        const sucursal = document.getElementById('com_sucursal_id');
        const tipo = document.getElementById('cat_sat_tipo_de_comprobante_id');
        const contextoJson = document.getElementById('fc-global-contexto');
        const aviso = document.getElementById('fc-global-aviso');
        const campos = ['global_periodicidad', 'global_meses', 'global_anio'].map(function (id) {
            return document.getElementById(id);
        });
        if (!bloque || !sucursal || !tipo || !contextoJson || campos.some(function (campo) { return !campo; })) {
            return;
        }

        let contexto;
        try {
            contexto = JSON.parse(contextoJson.textContent);
        } catch (error) {
            contexto = {};
        }
        const sucursales = Array.isArray(contexto.sucursales) ? contexto.sucursales : [];
        const tiposIngreso = Array.isArray(contexto.tipos_ingreso) ? contexto.tipos_ingreso : [];

        function actualizar() {
            const aplica = sucursales.includes(sucursal.value) && tiposIngreso.includes(tipo.value);
            const habiaDatos = campos.some(function (campo) { return campo.value !== ''; });
            bloque.hidden = !aplica;
            campos.forEach(function (campo) {
                campo.required = aplica;
                // Los vacíos se envían para borrar un periodo anterior al cambiar de receptor.
                campo.disabled = false;
                if (!aplica) {
                    campo.value = '';
                }
            });
            if (aviso) {
                if (aplica) {
                    aviso.hidden = true;
                } else if (habiaDatos) {
                    aviso.hidden = false;
                }
            }
        }

        [sucursal, tipo].forEach(function (select) {
            select.addEventListener('change', actualizar);
            if (window.jQuery) {
                window.jQuery(select).on('changed.bs.select', actualizar);
            }
        });
        actualizar();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar);
    } else {
        iniciar();
    }
}());
