document.addEventListener('DOMContentLoaded', function () {

    const tipo_agente_asesor_id = Number(
        document.getElementById('tipo_agente_asesor_id').value
    );

    const num_asesor_container = document.getElementById('num_asesor_container');
    const num_asesor = document.getElementById('num_asesor');
    const tipo_agente = document.getElementById('com_tipo_agente_id');

    function actualizar_numero_asesor() {
        const tipo_agente_id = Number(tipo_agente.value);

        const es_asesor = tipo_agente_id === tipo_agente_asesor_id;

        num_asesor_container.style.display = es_asesor ? '' : 'none';
        num_asesor.disabled = !es_asesor;
    }

    tipo_agente.addEventListener('change', actualizar_numero_asesor);

    actualizar_numero_asesor();

});