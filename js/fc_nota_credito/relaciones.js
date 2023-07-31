let aplica_saldo_sel = $("#aplica_saldo");
let aplica_saldo_hidden = $('[name="aplica_saldo"]');
let chk_relacion = $(".chk_relacion");
let saldo = 0.0;

aplica_saldo_sel.click(function () {
    if( $(this).prop('checked') ) {
        aplica_saldo_hidden.val('activo');
    }
    else{
        aplica_saldo_hidden.val('inactivo');
    }

});

chk_relacion.click(function () {
    saldo = '';

    if( $(this).prop('checked') ) {
        saldo = $(this).data("saldo");
    }
    else{
        saldo = '';
    }

    let selector_monto = $(this).parent().parent().children('.td_monto').children('.control-group').children('.form-control').val(saldo);


});