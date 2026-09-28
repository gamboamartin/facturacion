const tipo_agente_asesor_id = Number(
    document.getElementById('tipo_agente_asesor_id').value
);

const num_asesor_container = document.getElementById('num_asesor_container');

const tipo_agente = document.getElementById('com_tipo_agente_id');

function actualizar_numero_asesor() {
    const tipo_agente_id = Number(tipo_agente.value);

    num_asesor_container.style.display =
        tipo_agente_id === tipo_agente_asesor_id ? '' : 'none';
}

tipo_agente.addEventListener('change', actualizar_numero_asesor);

actualizar_numero_asesor();