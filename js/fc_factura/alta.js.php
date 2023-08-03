<script>
    function getParameterByName(name) {
        name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
        const regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
        results = regex.exec(location.search);
        return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
    }

let sl_fc_csd = $("#fc_csd_id");
let sl_cat_sat_forma_pago = $("#cat_sat_forma_pago_id");
let sl_cat_sat_metodo_pago = $("#cat_sat_metodo_pago_id");
let sl_cat_sat_moneda = $("#cat_sat_moneda_id");
let sl_cat_sat_uso_cfdi = $("#cat_sat_uso_cfdi_id");
let sl_com_sucursal = $("#com_sucursal_id");
let sl_com_tipo_cambio = $("#com_tipo_cambio_id");
let sl_plantilla = $('select[name="plantilla"]')


let txt_serie = $("#serie");
    let txt_fecha = $("#fecha");

sl_fc_csd.change(function () {
    let selected = $(this).find('option:selected');
    let serie = selected.data(`fc_csd_serie`);

    txt_serie.val(serie);
});
    sl_plantilla.change(function () {
        let session_id = getParameterByName('session_id');
        let adm_menu_id = getParameterByName('adm_menu_id');
        let row_entidad_id = $(this).val();
        let genera_factura = confirm('Deseas generar la factura basado en la plantilla?');
        if(genera_factura){
            let  url = "index.php?seccion=fc_factura&accion=inserta_factura_plantilla_bd&fc_factura_id="+row_entidad_id+"&session_id="+session_id+"&adm_menu_id="+adm_menu_id;
            window.location.href = url;
        }
        sl_plantilla.val(-1);
        sl_plantilla.selectpicker('refresh');

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

    change_moneda();

});


    sl_cat_sat_moneda.change(function () {
        change_moneda();

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


                if(!isNaN(json.error)){
                    alert(url);
                    alert(json.mensaje);
                    if(json.error === 1) {
                        return false;
                    }
                }

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



    </script>