let selecciona = $(".selecciona");

selecciona.click(function () {
    let fc_factura_id = $(this).val();
    let fc_factura_id_txt = $("#"+fc_factura_id);
    let checked = false;
    let saldo = $( this ).data( "saldo" );
    let total_pagos_hidden = $("#total_pagos");
    let total_pagos = total_pagos_hidden.val();
    if( $(this).prop('checked') ) {
        checked = true;
    }
    if(!checked){
        fc_factura_id_txt.val(0);
    }
    else{
        fc_factura_id_txt.val(saldo);
    }


});