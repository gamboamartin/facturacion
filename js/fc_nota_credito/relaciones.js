let aplica_saldo_sel = $("#aplica_saldo");
let aplica_saldo_hidden = $('[name="aplica_saldo"]');
let chk_relacion = $(".chk_relacion");
let saldo = 0.0;
let inp_monto = $(".inp_monto");

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

    let selector_monto = $(this).parent().parent().children('.td_monto').children('.control-group').children('.inp_monto').val(saldo);

});

inp_monto.change(function () {
    let chk = $(this).parent().parent().parent().children('.td_chk').children('.chk_relacion');

    let monto = $(this).val();
    if(parseFloat(monto) > 0.0){
        chk.prop('checked',true)
    }
    else{
        chk.prop('checked',false)
    }


});