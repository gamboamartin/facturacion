let sl_fc_csd = $("#fc_csd_id");
let sl_cat_sat_forma_pago = $("#cat_sat_forma_pago_id");
let sl_cat_sat_metodo_pago = $("#cat_sat_metodo_pago_id");
let sl_cat_sat_moneda = $("#cat_sat_moneda_id");
let sl_cat_sat_uso_cfdi = $("#cat_sat_uso_cfdi_id");
let sl_com_sucursal = $("#com_sucursal_id");
let sl_com_tipo_cambio = $("#com_tipo_cambio_id");

let txt_serie = $("#serie");

sl_fc_csd.change(function () {
    let selected = $(this).find('option:selected');
    let serie = selected.data(`fc_csd_serie`);

    txt_serie.val(serie);
});

let txt_fecha = $("#fecha");

sl_com_sucursal.change(function () {
    /*let selected = $(this).find('option:selected');
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
    sl_cat_sat_uso_cfdi.selectpicker('refresh');*/

    change_moneda();
});

sl_cat_sat_moneda.change(function () {
    change_moneda();

});

function change_moneda(){

    let cat_sat_moneda_id = sl_cat_sat_moneda.val();
    let fecha = txt_fecha.val();
    let url = get_url("com_tipo_cambio","get", {});
    $.ajax({
        // la URL para la petición
        url : url,
        // la información a enviar
        // (también es posible utilizar una cadena de datos)
        data : { filtros : {'cat_sat_moneda.id': cat_sat_moneda_id,'com_tipo_cambio.fecha': fecha} },

        // especifica si será una petición POST o GET
        type : 'POST',

        // el tipo de información que se espera de respuesta


        // código a ejecutar si la petición es satisfactoria;
        // la respuesta es pasada como argumento a la función
        success : function(json) {
            console.log(json);
            sl_com_tipo_cambio.empty();
            integra_new_option(sl_com_tipo_cambio,'Seleccione un tipo de cambio','-1');


            $.each(json.registros, function( index, com_tipo_cambio ) {
                integra_new_option(sl_com_tipo_cambio,com_tipo_cambio.cat_sat_moneda_codigo+' '+com_tipo_cambio.com_tipo_cambio_monto,
                    com_tipo_cambio.com_tipo_cambio_id);
                sl_com_tipo_cambio.val(com_tipo_cambio.com_tipo_cambio_id);
            });

            sl_com_tipo_cambio.selectpicker('refresh');
        },

        // código a ejecutar si la petición falla;
        // son pasados como argumentos a la función
        // el objeto de la petición en crudo y código de estatus de la petición
        error : function(xhr, status) {
            alert('Disculpe, existió un problema');
            console.log(xhr);
            console.log(status);
        },

        // código a ejecutar sin importar si la petición falló o no
        complete : function(xhr, status) {
            //alert('Petición realizada');
        }
    });

}