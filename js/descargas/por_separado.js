(function () {
    'use strict';

    function enlaceValido(enlace, tipo, vista) {
        var url = new URL(enlace.href, window.location.href);
        var parametros = url.searchParams;
        var accion = tipo === 'xml' ? 'descarga_xml' : 'genera_pdf';
        if (url.origin !== window.location.origin || url.pathname !== window.location.pathname
            || url.username || url.password || url.hash
            || parametros.getAll('seccion').length !== 1 || parametros.getAll('accion').length !== 1
            || parametros.getAll('registro_id').length !== 1
            || parametros.get('seccion') !== vista.dataset.seccion || parametros.get('accion') !== accion
            || parametros.get('registro_id') !== vista.dataset.registroId) {
            throw new Error('El enlace no corresponde a este comprobante.');
        }
        return url.href;
    }

    function nombreArchivo(disposicion, tipo) {
        var nombre = '';
        var codificado = /(?:^|;)\s*filename\*\s*=\s*UTF-8'[^']*'([^;]+)/i.exec(disposicion);
        if (codificado) {
            try { nombre = decodeURIComponent(codificado[1].trim()); } catch (error) { /* Probar filename. */ }
        }
        if (!nombre) {
            var simple = /(?:^|;)\s*filename\s*=\s*(?:"([^"]*)"|([^;]*))/i.exec(disposicion);
            nombre = simple ? (simple[1] || simple[2] || '').trim() : '';
        }
        nombre = nombre.replace(/[\\/\x00-\x1f\x7f]/g, '_').replace(/^[.\s]+|[.\s]+$/g, '').slice(0, 180);
        if (!nombre) { nombre = 'comprobante'; }
        if (!nombre.toLowerCase().endsWith('.' + tipo)) { nombre += '.' + tipo; }
        return nombre;
    }

    async function comprobarDocumento(respuesta, tipo) {
        if (!respuesta.ok || respuesta.redirected) {
            throw new Error('El servidor no entregó el documento. Revisa tu sesión y tus permisos.');
        }
        var mime = (respuesta.headers.get('Content-Type') || '').split(';')[0].trim().toLowerCase();
        var permitidos = tipo === 'xml' ? ['application/xml', 'text/xml', 'application/octet-stream']
            : ['application/pdf', 'application/octet-stream'];
        if (permitidos.indexOf(mime) === -1) {
            throw new Error('El servidor devolvió una respuesta que no es un ' + tipo.toUpperCase() + '.');
        }
        var archivo = await respuesta.blob();
        if (!archivo.size) { throw new Error('El documento está vacío.'); }
        if (tipo === 'xml') {
            var xml = new DOMParser().parseFromString(await archivo.text(), 'application/xml');
            var raiz = xml.documentElement;
            if (xml.querySelector('parsererror') || !raiz || raiz.localName !== 'Comprobante'
                || !/^http:\/\/www\.sat\.gob\.mx\/cfd\/[34]$/.test(raiz.namespaceURI || '')) {
                throw new Error('La respuesta no contiene un XML CFDI válido.');
            }
        } else if (await archivo.slice(0, 5).text() !== '%PDF-') {
            throw new Error('La respuesta no contiene un PDF válido.');
        }
        return archivo;
    }

    async function descargar(enlace, tipo, vista) {
        var respuesta = await fetch(enlaceValido(enlace, tipo, vista), {
            method: 'GET', credentials: 'same-origin', redirect: 'error', cache: 'no-store'
        });
        var archivo = await comprobarDocumento(respuesta, tipo);
        var objeto = URL.createObjectURL(archivo);
        var descarga = document.createElement('a');
        descarga.href = objeto;
        descarga.download = nombreArchivo(respuesta.headers.get('Content-Disposition') || '', tipo);
        descarga.hidden = true;
        document.body.appendChild(descarga);
        descarga.click();
        descarga.remove();
        // Conservar el objeto mientras la pestaña siga abierta evita revocarlo durante una descarga lenta.
        window.addEventListener('pagehide', function () { URL.revokeObjectURL(objeto); }, { once: true });
    }

    async function iniciar() {
        var vista = document.getElementById('descargas-por-separado');
        if (!vista || vista.dataset.iniciada === '1') { return; }
        vista.dataset.iniciada = '1';
        var resultado = document.getElementById('descargas-resultado');
        if (!resultado) { return; }
        var errores = 0;
        // Esperar el cuerpo completo del XML antes de pedir el PDF mantiene el orden real.
        for (var tipo of ['xml', 'pdf']) {
            var enlace = vista.querySelector('[data-descarga="' + tipo + '"]');
            if (!enlace) { continue; }
            var estado = vista.querySelector('[data-estado="' + tipo + '"]');
            estado.textContent = 'Solicitando ' + tipo.toUpperCase() + '…';
            try {
                await descargar(enlace, tipo, vista);
                estado.textContent = 'Enviado al navegador';
                estado.dataset.resultado = 'enviado';
            } catch (error) {
                errores++;
                estado.textContent = 'No se pudo descargar. ' + (error instanceof TypeError
                    ? 'Revisa tu conexión y tu sesión; puedes usar el enlace individual.' : error.message);
                estado.dataset.resultado = 'error';
            }
        }
        resultado.textContent = errores
            ? 'Hubo documentos que no se pudieron descargar. Usa sus enlaces individuales para reintentar.'
            : 'Se enviaron las descargas al navegador. Revisa su panel de descargas; si falta un archivo, usa su enlace individual.';
        vista.dataset.finalizada = '1';
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar, { once: true });
    } else { iniciar(); }
}());
