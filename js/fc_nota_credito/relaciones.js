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
    saldo = $(this).data("saldo");
    //alert(saldo);

});