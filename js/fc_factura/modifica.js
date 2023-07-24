let session_id = getParameterByName('session_id');
let adm_menu_id = getParameterByName('adm_menu_id');
let sl_fc_csd = $("#fc_csd_id");
let sl_cat_sat_forma_pago = $("#cat_sat_forma_pago_id");
let sl_cat_sat_metodo_pago = $("#cat_sat_metodo_pago_id");
let sl_cat_sat_moneda = $("#cat_sat_moneda_id");
let sl_cat_sat_uso_cfdi = $("#cat_sat_uso_cfdi_id");
let sl_com_sucursal = $("#com_sucursal_id");

let txt_serie = $("#serie");

let sl_com_producto = $("#com_producto_id");
let sl_com_tipo_cambio = $("#com_tipo_cambio_id");
let sl_cat_sat_conf_imps_id = $("#cat_sat_conf_imps_id");
let txt_descripcion = $("#descripcion");
let txt_unidad = $("#unidad");
let txt_impuesto = $("#impuesto");
let txt_tipo_factor = $("#tipo_factor");
let txt_factor = $("#factor");
let txt_cantidad = $("#cantidad");
let txt_valor_unitario = $("#valor_unitario");
let txt_descuento = $(".partidas #descuento");
let txt_subtotal = $(".partidas #subtotal");
let txt_total = $(".partidas #total");
let txt_cuenta_predial = $("#cuenta_predial");
let txt_fecha = $("#fecha");
let frm_partida = $("#frm-partida");
let btn_alta_partida = $("#btn-alta-partida");
let hidden_registro_id = $("input[name='registro_id']");
let txt_fc_partida_descripcion = $(".fc_partida_descripcion");
let txt_fc_partida_cantidad = $(".fc_partida_cantidad");
let txt_fc_partida_valor_unitario = $(".fc_partida_valor_unitario");
let txt_fc_partida_descuento = $(".fc_partida_descuento");

sl_fc_csd.change(function () {
    let selected = $(this).find('option:selected');
    let serie = selected.data(`fc_csd_serie`);

    txt_serie.val(serie);
});

frm_partida.submit(function () {
    let cantidad = txt_cantidad.val();
    let valor_unitario = txt_valor_unitario.val();
    if(cantidad <=0.0){
        alert('La cantidad debe ser mayor a 0');
        txt_cantidad.focus();
        return false;
    }
    if(valor_unitario <=0.0){
        alert('La valor unitario debe ser mayor a 0');
        valor_unitario.focus();
        return false;
    }


});

function modifica_partida_bd(contenedores, data){

    let url = get_url("fc_factura","modifica_partida_bd", {});
    let registro_partida_id = -1;
    $.ajax({

        url : url,
        data : data ,
        type : 'POST',

        success : function(json) {
            console.log(json);
            alert(json.mensaje);

            if(!isNaN(json.error)){
                alert(url);
                if(json.error === 1) {
                    return false;
                }
            }
            registro_partida_id = json.registro_id;
            contenedores.td_fc_partida_descripcion.data('fc_partida_factura_id', registro_partida_id);
            return registro_partida_id;

        },

        error : function(xhr, status) {
            alert('Disculpe, existió un problema');
            console.log(xhr);
            console.log(status);
            return false;

        },

        // código a ejecutar sin importar si la petición falló o no
        complete : function(xhr, status) {
            //alert('Petición realizada');
        }

    });
    return true;
}

function init_data(data){
    if(data.cantidad <=0.0){
        alert('La cantidad debe ser mayor a 0');
        txt_cantidad.focus();
        return false;
    }
    if(data.valor_unitario <= 0.0){
        alert('La valor unitario debe ser mayor a 0');
        txt_valor_unitario.focus();
        return false;
    }

    if(data.descripcion === ''){
        alert('Integre una descripcion');
        txt_descripcion.focus();
        return false;
    }
    if(data.descuento === ''){
        data.descuento = 0;
    }
    return data;
}

function tds(contenedor){
    let cte_formulario = contenedor.parent().parent().parent();
    let td_fc_partida_cantidad = cte_formulario.children(".tr_data_partida").children(".td_fc_partida_cantidad");
    let td_fc_partida_valor_unitario = cte_formulario.children(".tr_data_partida").children(".td_fc_partida_valor_unitario");
    let td_fc_partida_descuento = cte_formulario.children(".tr_data_partida").children(".td_fc_partida_descuento");
    let td_fc_partida_descripcion = cte_formulario.children(".tr_fc_partida_descripcion").children(".td_fc_partida_descripcion");

    return {
        cte_formulario: cte_formulario, td_fc_partida_cantidad: td_fc_partida_cantidad,
        td_fc_partida_valor_unitario: td_fc_partida_valor_unitario, td_fc_partida_descuento: td_fc_partida_descuento,
        td_fc_partida_descripcion: td_fc_partida_descripcion
    };
}

function data_contenedores(contenedores){
    let ct_fc_partida_cantidad = contenedores.td_fc_partida_cantidad.children(".fc_partida_cantidad");
    let ct_fc_partida_valor_unitario = contenedores.td_fc_partida_valor_unitario.children(".fc_partida_valor_unitario");
    let ct_fc_partida_descuento = contenedores.td_fc_partida_descuento.children(".fc_partida_descuento");
    let ct_fc_partida_descripcion = contenedores.td_fc_partida_descripcion.children(".fc_partida_descripcion");

    return {
        ct_fc_partida_cantidad: ct_fc_partida_cantidad,
        ct_fc_partida_valor_unitario: ct_fc_partida_valor_unitario, ct_fc_partida_descuento: ct_fc_partida_descuento,
        ct_fc_partida_descripcion: ct_fc_partida_descripcion
    };
}

function valores_partida(contenedor){
    let contenedores = tds(contenedor);
    let campos = data_contenedores(contenedores);



    let cantidad = campos.ct_fc_partida_cantidad.val();
    let valor_unitario = campos.ct_fc_partida_valor_unitario.val();
    let descripcion = campos.ct_fc_partida_descripcion.val();
    let descuento = campos.ct_fc_partida_descuento.val();
    let registro_id = hidden_registro_id.val();
    let registro_partida_id = contenedores.td_fc_partida_descripcion.data('fc_partida_factura_id');


    let data = {descripcion: descripcion,cantidad: cantidad, valor_unitario: valor_unitario, descuento: descuento,
        registro_partida_id:  registro_partida_id, registro_id: registro_id};

    data = init_data(data);

    return data;
}

txt_fc_partida_descripcion.change(function () {

    let data = valores_partida($(this));
    let contenedores = tds($(this));

    data = init_data(data);

    if(!data){
        alert('Error al inicializa data');
        return false;
    }
    modifica_partida_bd(contenedores, data);



});

txt_fc_partida_cantidad.change(function () {
    let data = valores_partida($(this));
    let contenedores = tds($(this));

    data = init_data(data);

    if(!data){
        alert('Error al inicializa data');
        return false;
    }
    modifica_partida_bd(contenedores, data);

});


txt_fc_partida_valor_unitario.change(function () {

    let data = valores_partida($(this));
    let contenedores = tds($(this));

    data = init_data(data);

    if(!data){
        alert('Error al inicializa data');
        return false;
    }
    modifica_partida_bd(contenedores, data);


});

txt_fc_partida_descuento.change(function () {

    let data = valores_partida($(this));
    let contenedores = tds($(this));

    data = init_data(data);

    if(!data){
        alert('Error al inicializa data');
        return false;
    }
    modifica_partida_bd(contenedores, data);


});

function input_txt(name_class, name_input, valor){
    return "<input type='text' class='form-control form-control-sm " + name_class + "' " +
        "name='" + name_input + "' value='" + valor + "'/>";
}

function td_input(name_class, name_input, valor){
    let input_data = input_txt(name_class,name_input,valor);
    return "<td class='td_" + name_class + "'>" + input_data + "</td>";
}

function tr_tags_partida(){

    return "<tr>" +
        "<td><b>Cantidad</b></td>" +
        "<td><b>Valor Unitario</b></td>" +
        "<td><b>Importe</b></td>" +
        "<td><b>Descuento</b></td>" +
        "</tr>";

}

btn_alta_partida.click(function () {

    let cantidad = txt_cantidad.val();
    let valor_unitario = txt_valor_unitario.val();
    let com_producto_id = sl_com_producto.val();
    let descripcion = txt_descripcion.val();
    let descuento = txt_descuento.val();
    let cuenta_predial = txt_cuenta_predial.val();
    let cat_sat_conf_imps_id = sl_cat_sat_conf_imps_id.val();
    let registro_id = hidden_registro_id.val();

    if(cantidad <=0.0){
        alert('La cantidad debe ser mayor a 0');
        txt_cantidad.focus();
        return false;
    }
    if(valor_unitario <= 0.0){
        alert('La valor unitario debe ser mayor a 0');
        txt_valor_unitario.focus();
        return false;
    }
    if(com_producto_id === ''){
        alert('La seleccione un producto');
        sl_com_producto.focus();
        return false;
    }
    if(cat_sat_conf_imps_id === ''){
        alert('La seleccione una Configuracion');
        sl_cat_sat_conf_imps_id.focus();
        return false;
    }
    if(descripcion === ''){
        alert('Integre una descripcion');
        txt_descripcion.focus();
        return false;
    }
    if(descuento === ''){
        descuento = 0;
    }

    let selected_producto = sl_com_producto.find('option:selected');
    let unidad = selected_producto.data(`cat_sat_unidad_descripcion`);
    let impuesto = selected_producto.data(`cat_sat_obj_imp_descripcion`);
    let tipo_factor = selected_producto.data(`cat_sat_tipo_factor_descripcion`);
    let factor = selected_producto.data(`cat_sat_factor_factor`);
    let aplica_predial = selected_producto.data('com_producto_aplica_predial');


    if(aplica_predial){
        if(txt_cuenta_predial.val() === ''){
            alert('Agregue una cuenta predial');
            txt_cuenta_predial.focus();
            return false;
        }
    }


    let url = get_url("fc_partida","alta_bd", {});
    $.ajax({
        // la URL para la petición
        url : url,
        // la información a enviar
        // (también es posible utilizar una cadena de datos)
        data : {com_producto_id: com_producto_id, descripcion: descripcion,cantidad: cantidad,
            valor_unitario: valor_unitario, descuento: descuento,cat_sat_conf_imps_id:cat_sat_conf_imps_id,
            fc_factura_id:registro_id,cuenta_predial: cuenta_predial } ,

        // especifica si será una petición POST o GET
        type : 'POST',

        // el tipo de información que se espera de respuesta


        // código a ejecutar si la petición es satisfactoria;
        // la respuesta es pasada como argumento a la función
        success : function(json) {
            console.log(json);
            alert(json.mensaje);

            sl_com_producto.val(-1);
            sl_com_producto.selectpicker('refresh');
            txt_descripcion.val('');
            txt_cantidad.val(0);
            txt_valor_unitario.val(0);
            txt_subtotal.val(0);
            txt_total.val(0);

            if(!isNaN(json.error)){
                alert(url);
                if(json.error === 1) {
                    return false;
                }
            }


            let fc_partida_id = json.registro_id;

            let input_descripcion = input_txt('fc_partida_descripcion','descripcion',json.registro_obj.fc_partida_descripcion);
            let td_fc_partida_descripcion = "<tr class='tr_fc_partida_descripcion'><td colspan='5' class='td_fc_partida_descripcion' data-fc_partida_factura_id='"+json.registro_obj.fc_partida_id+"'>"+input_descripcion+"</td></tr>";

            let td_com_producto_codigo = "<td><b>CVE SAT: </b><td>"+json.registro_obj.com_producto_codigo+"</td>";
            let td_cat_sat_unidad_descripcion = "<td><b>Unidad: </b>"+json.registro_obj.cat_sat_unidad_descripcion+"</td>";
            let td_cat_sat_obj_imp_descripcion = "<td><b>Obj Imp: </b>"+json.registro_obj.cat_sat_obj_imp_descripcion+"</td>";



            let td_fc_partida_cantidad =td_input('fc_partida_cantidad','cantidad',json.registro_obj.fc_partida_cantidad);
            let td_fc_partida_valor_unitario =td_input('fc_partida_valor_unitario','valor_unitario',json.registro_obj.fc_partida_valor_unitario);

            let td_fc_partida_importe = "<td><input type='text' class='form-control form-control-sm' disabled value='"+json.registro_obj.fc_partida_sub_total_base+"' /></td>";


            let td_fc_partida_descuento =td_input('fc_partida_descuento','descuento',json.registro_obj.fc_partida_descuento);


            let td_fc_partida_sub_total = "<tr><td><b>Sub Total: </b>"+json.registro_obj.fc_partida_sub_total+"</td>";
            let td_fc_partida_traslados = "<td><b>Traslados: </b>"+json.registro_obj.fc_partida_total_traslados+"</td>";
            let td_fc_partida_retenciones = "<td><b>Retenciones: </b>"+json.registro_obj.fc_partida_total_retenciones+"</td>";
            let td_fc_partida_total = "<td><b>Total: </b>"+json.registro_obj.fc_partida_total+"</td>";

            let tr_tags =tr_tags_partida();
            let tr_inputs_montos = "<tr class='tr_data_partida'>"+td_fc_partida_cantidad+td_fc_partida_valor_unitario+td_fc_partida_importe+td_fc_partida_descuento+"</tr>";

            let tr_data_producto = "<tr>"+td_com_producto_codigo+td_cat_sat_unidad_descripcion+td_cat_sat_obj_imp_descripcion+"</tr>";
            let tr_montos = tr_inputs_montos+"<tr>"+td_fc_partida_sub_total+td_fc_partida_traslados+td_fc_partida_retenciones+td_fc_partida_total+"</tr>";
            let tr_buttons = "<tr><td colspan='5'>" +
                "<div class='col-md-12'>" +
                    "<a role='button' title='Eliminar' href='index.php?seccion=fc_partida&accion=elimina_bd&registro_id="+fc_partida_id+"&session_id="+session_id+"&adm_menu_id="+adm_menu_id+"&seccion_retorno=fc_factura&accion_retorno=modifica&id_retorno="+registro_id+"' class='btn btn-danger col-sm-12'><span class='bi bi-trash'></span>" +
                    "</a>" +
                "</div>" +
                "</td></tr>";

            let table_full = "" +
                "<form method='post' action='./index.php?seccion=fc_factura&accion=modifica_partida_bd&registro_id="+registro_id+"&adm_menu_id="+adm_menu_id+"&session_id="+session_id+"&registro_partida_id="+fc_partida_id+"'>"+
                "<table class='table table-striped data-partida' style='border: 2px solid'><tbody>"+
                td_fc_partida_descripcion+tr_data_producto+tr_tags+tr_montos+tr_buttons+
                "</tbody>" +
                "</table>"+
                "</form>";
            console.log(json.registro_obj);
            $("#row-partida").prepend(table_full);

            $(".fc_partida_descripcion").change(function () {

                let data = valores_partida($(this));
                let contenedores = tds($(this));

                data = init_data(data);

                if(!data){
                    alert('Error al inicializa data');
                    return false;
                }
                modifica_partida_bd(contenedores, data);

            });

            $(".fc_partida_cantidad").change(function () {
                let data = valores_partida($(this));
                let contenedores = tds($(this));

                data = init_data(data);

                if(!data){
                    alert('Error al inicializa data');
                    return false;
                }
                modifica_partida_bd(contenedores, data);

            });


            $(".fc_partida_valor_unitario").change(function () {

                let data = valores_partida($(this));
                let contenedores = tds($(this));

                data = init_data(data);

                if(!data){
                    alert('Error al inicializa data');
                    return false;
                }
                modifica_partida_bd(contenedores, data);


            });

            $(".fc_partida_descuento").change(function () {

                let data = valores_partida($(this));
                let contenedores = tds($(this));

                data = init_data(data);

                if(!data){
                    alert('Error al inicializa data');
                    return false;
                }
                modifica_partida_bd(contenedores, data);


            });


        },

        // código a ejecutar si la petición falla;
        // son pasados como argumentos a la función
        // el objeto de la petición en crudo y código de estatus de la petición
        error : function(xhr, status) {

            alert('Disculpe, existió un problema');
            console.log(xhr);
            console.log(status);
            sl_com_producto.val(-1);
            sl_com_producto.selectpicker('refresh');
            txt_descripcion.val('');
            txt_cantidad.val(0);
            txt_valor_unitario.val(0);
            txt_subtotal.val(0);
            txt_total.val(0);

        },

        // código a ejecutar sin importar si la petición falló o no
        complete : function(xhr, status) {
            //alert('Petición realizada');
        }
    });

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

sl_com_producto.change(function () {
    let selected = $(this).find('option:selected');
    let descripcion = selected.data(`com_producto_descripcion`);
    let unidad = selected.data(`cat_sat_unidad_descripcion`);
    let impuesto = selected.data(`cat_sat_obj_imp_descripcion`);
    let tipo_factor = selected.data(`cat_sat_tipo_factor_descripcion`);
    let factor = selected.data(`cat_sat_factor_factor`);
    let aplica_predial = selected.data('com_producto_aplica_predial');
    let cat_sat_conf_imps_id = selected.data('cat_sat_conf_imps_id');

    txt_cuenta_predial.prop( "disabled", true );
    if(aplica_predial === 'activo'){
        txt_cuenta_predial.prop( "disabled", false );
    }
    //sl_cat_sat_conf_imps_id.val(cat_sat_conf_imps_id);
    //sl_cat_sat_conf_imps_id.selectpicker('refresh');

    txt_descripcion.val(descripcion);
    txt_unidad.val(unidad);
    txt_impuesto.val(impuesto);
    txt_tipo_factor.val(tipo_factor);
    txt_factor.val(factor);
});

txt_cantidad.on('input', function () {
    let valor = $(this).val();
    let valor_unitario = txt_valor_unitario.val();
    let subtotal = valor * valor_unitario;
    let descuento = txt_descuento.val();
    let total = subtotal - descuento;

    txt_subtotal.val(subtotal);
    txt_total.val(total);
});

txt_valor_unitario.on('input', function () {
    let valor = $(this).val();
    let cantidad = txt_cantidad.val();
    let subtotal = valor * cantidad;
    let descuento = txt_descuento.val();
    let total = subtotal - descuento;

    txt_subtotal.val(subtotal);
    txt_total.val(total);
});

txt_descuento.on('input', function () {
    let valor = $(this).val();
    let cantidad = txt_cantidad.val();
    let valor_unitario = txt_valor_unitario.val();
    let subtotal = cantidad * valor_unitario;

    if (valor > subtotal){
        alert("El descuento no puede superar al subtotal obtenido")
        valor = 0;
        $(this).val(0);
    }

    let total = subtotal - valor;

    txt_subtotal.val(subtotal);
    txt_total.val(total);
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

