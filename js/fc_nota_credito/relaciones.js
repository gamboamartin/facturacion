let aplica_saldo_sel = $("#aplica_saldo");
let aplica_saldo_hidden = $('[name="aplica_saldo"]');



aplica_saldo_sel.click(function () {
    if( $(this).prop('checked') ) {
        aplica_saldo_hidden.val('activo');
    }
    else{
        aplica_saldo_hidden.val('inactivo');
    }

});