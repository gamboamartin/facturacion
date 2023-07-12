<script>
let sl_fc_csd = $("#fc_csd_id");
let sl_cat_sat_forma_pago = $("#cat_sat_forma_pago_id");
let sl_cat_sat_metodo_pago = $("#cat_sat_metodo_pago_id");
let sl_cat_sat_moneda = $("#cat_sat_moneda_id");
let sl_cat_sat_uso_cfdi = $("#cat_sat_uso_cfdi_id");
let sl_com_sucursal = $("#com_sucursal_id");

let txt_serie = $("#serie");

sl_fc_csd.change(function () {
    let selected = $(this).find('option:selected');
    let serie = selected.data(`fc_csd_serie`);

    txt_serie.val(serie);
});

sl_com_sucursal.change(function () {
    let selected = $(this).find('option:selected');
    let cat_sat_forma_pago = selected.data(`com_cliente_cat_sat_forma_pago_id`);
    let cat_sat_metodo_pago = selected.data(`com_cliente_cat_sat_metodo_pago_id`);
    let cat_sat_moneda = selected.data(`com_cliente_cat_sat_moneda_id`);
    let cat_sat_uso_cfdi = selected.data(`com_cliente_cat_sat_uso_cfdi_id`);

    sl_cat_sat_forma_pago.val(cat_sat_forma_pago);
    sl_cat_sat_forma_pago.selectpicker('refresh');
    sl_cat_sat_metodo_pago.val(cat_sat_metodo_pago);
    sl_cat_sat_metodo_pago.selectpicker('refresh');
    sl_cat_sat_moneda.val(cat_sat_moneda);
    sl_cat_sat_moneda.selectpicker('refresh');
    sl_cat_sat_uso_cfdi.val(cat_sat_uso_cfdi);
    sl_cat_sat_uso_cfdi.selectpicker('refresh');
});

let cat_sat_metodo_pago_id_sl = $("#cat_sat_metodo_pago_id");
let cat_sat_forma_pago_id_sl = $("#cat_sat_forma_pago_id");

let metodo_pago_permitido = <?php echo(json_encode((new \gamboamartin\cat_sat\models\_validacion())->metodo_pago_permitido)); ?>;
let formas_pagos_permitidas = [];

let cat_sat_metodo_pago_codigo = '';
let cat_sat_forma_pago_codigo = '';

cat_sat_metodo_pago_id_sl.change(function() {
    cat_sat_metodo_pago_codigo = $('option:selected', this).data("cat_sat_metodo_pago_codigo");
    formas_pagos_permitidas = metodo_pago_permitido[cat_sat_metodo_pago_codigo];

    if(cat_sat_forma_pago_codigo !== ''){
            let permitido = false;
            $.each(formas_pagos_permitidas, function(i, item) {
            if(item == cat_sat_forma_pago_codigo){
            permitido = true;
        }
        });

            if(!permitido){
                cat_sat_metodo_pago_id_sl.val(null);
            $('#myModal').modal('show')
        }

    }


});

cat_sat_forma_pago_id_sl.change(function() {

    cat_sat_forma_pago_codigo = $('option:selected', this).data("cat_sat_forma_pago_codigo");

    let permitido = false;
    $.each(formas_pagos_permitidas, function(i, item) {
        if(item == cat_sat_forma_pago_codigo){
            permitido = true;
        }
    });

    if(!permitido){
        cat_sat_forma_pago_id_sl.val(null);
        $('#myModal').modal('show')
    }

});



    </script>