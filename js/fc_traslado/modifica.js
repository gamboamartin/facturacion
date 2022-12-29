let sl_fc_partida = $("#fc_partida_id");
let txt_codigo = $("#codigo");
let txt_descripcion = $("#descripcion");

sl_fc_partida.change(function () {
    let selected = $(this).find('option:selected');
    let codigo = selected.data(`fc_partida_codigo`);
    let descripcion = selected.data(`fc_partida_descripcion`);

    txt_codigo.val(codigo);
    txt_descripcion.val(descripcion);
});