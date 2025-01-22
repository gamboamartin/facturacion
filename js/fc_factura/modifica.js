let session_id = getParameterByName('session_id');
let adm_menu_id = getParameterByName('adm_menu_id');


let sl_cat_sat_forma_pago = $("#cat_sat_forma_pago_id");
let sl_cat_sat_metodo_pago = $("#cat_sat_metodo_pago_id");
let sl_cat_sat_moneda = $("#cat_sat_moneda_id");
let sl_cat_sat_uso_cfdi = $("#cat_sat_uso_cfdi_id");
let sl_com_sucursal = $("#com_sucursal_id");


let sl_com_producto = $("#com_producto_id");
let sl_com_tipo_cambio = $("#com_tipo_cambio_id");
let sl_cat_sat_conf_imps_id = $("#cat_sat_conf_imps_id");
let sl_cat_sat_obj_imp_id = $("#cat_sat_obj_imp_id");
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
let btn_alta_partida = $("#btn-alta-partida");
let entidad_partida = 'fc_partida';
let hidden_registro_id = $("input[name='registro_id']");
let entidad_factura = 'fc_factura';

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

ejecuciones_partida(entidad_factura,entidad_partida);


btn_alta_partida.click(function () {

    let cantidad = txt_cantidad.val();
    let valor_unitario = txt_valor_unitario.val();
    let com_producto_id = sl_com_producto.val();
    let descripcion = txt_descripcion.val();
    let descuento = txt_descuento.val();
    let cuenta_predial = txt_cuenta_predial.val();
    let cat_sat_obj_imp_id = sl_cat_sat_obj_imp_id.val();
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
    if(cat_sat_obj_imp_id === ''){
        alert('La seleccione una Configuracion');
        sl_cat_sat_obj_imp_id.focus();
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
    let aplica_predial = selected_producto.data('com_producto_aplica_predial');


    if(aplica_predial === 'activo'){
        if(txt_cuenta_predial.val() === ''){
            alert('Agregue una cuenta predial');
            txt_cuenta_predial.focus();
            return false;
        }
    }


    let url = get_url(entidad_partida,"alta_bd", {});
    $.ajax({
        // la URL para la petición
        url : url,
        // la información a enviar
        // (también es posible utilizar una cadena de datos)
        data : {com_producto_id: com_producto_id, descripcion: descripcion,cantidad: cantidad,
            valor_unitario: valor_unitario, descuento: descuento,cat_sat_conf_imps_id:cat_sat_conf_imps_id,
            key_entidad_factura_id:registro_id,cuenta_predial: cuenta_predial,cat_sat_obj_imp_id: cat_sat_obj_imp_id } ,

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
            txt_cantidad.val(1);
            txt_valor_unitario.val(0);
            txt_subtotal.val(0);
            txt_total.val(0);

            let impuestos_traslados_previos = parseFloat($("#impuestos_trasladados").val());
            let impuestos_trasladados_partida =  parseFloat(json.registro_puro.total_traslados);

            let impuestos_retenciones_previos = parseFloat($("#impuestos_retenidos").val());
            let impuestos_retenciones_partida =  parseFloat(json.registro_puro.total_retenciones);

            let subtotal_previos = parseFloat($("#subtotal").val());
            let subtotal_partida =  parseFloat(json.registro_puro.sub_total);

            let descuento_previos = parseFloat($("#descuento").val());
            let descuento_partida =  parseFloat(json.registro_puro.descuento);

            let total_previos = parseFloat($("#total").val());
            let total_partida =  parseFloat(json.registro_puro.total);

            let impuestos_trasladados = parseFloat(impuestos_traslados_previos + impuestos_trasladados_partida).toFixed(2);
            let impuestos_retenciones = parseFloat(impuestos_retenciones_previos + impuestos_retenciones_partida).toFixed(2);
            let _subtotal = parseFloat(subtotal_previos + subtotal_partida).toFixed(2);
            let _descuento = parseFloat(descuento_previos + descuento_partida).toFixed(2);
            let _total = parseFloat(total_previos + total_partida).toFixed(2);

            $("#impuestos_trasladados").val(impuestos_trasladados);
            $("#impuestos_retenidos").val(impuestos_retenciones);
            $("#subtotal").val(_subtotal);
            $("#descuento").val(_descuento);
            $("#total").val(_total);


            if(!isNaN(json.error)){
                alert(url);
                if(json.error === 1) {
                    return false;
                }
            }


            let fc_partida_id = json.registro_id;


            let td_fc_partida_descripcion_html = td_fc_partida_descripcion(json);


            let td_fc_partida_cantidad =td_input('fc_partida_cantidad','cantidad',json.registro_puro.cantidad);
            let td_fc_partida_valor_unitario =td_input('fc_partida_valor_unitario','valor_unitario',json.registro_puro.valor_unitario);

            let td_fc_partida_importe = "<td><input type='text' class='form-control form-control-sm' disabled value='"+json.registro_puro.sub_total_base+"' /></td>";


            let td_fc_partida_descuento =td_input('fc_partida_descuento','descuento',json.registro_puro.descuento);


            let td_fc_partida_sub_total = "<tr><td><b>Sub Total: </b>"+json.registro_puro.sub_total+"</td>";
            let td_fc_partida_traslados = "<td><b>Traslados: </b>"+json.registro_puro.total_traslados+"</td>";
            let td_fc_partida_retenciones = "<td><b>Retenciones: </b>"+json.registro_puro.total_retenciones+"</td>";
            let td_fc_partida_total = "<td><b>Total: </b>"+json.registro_puro.total+"</td>";

            let tr_tags =tr_tags_partida();
            let tr_inputs_montos = "<tr class='tr_data_partida'>"+td_fc_partida_cantidad+td_fc_partida_valor_unitario+td_fc_partida_importe+td_fc_partida_descuento+"</tr>";

            let tr_data_producto_html = tr_data_producto(json);
            let tr_montos = tr_inputs_montos+"<tr>"+td_fc_partida_sub_total+td_fc_partida_traslados+td_fc_partida_retenciones+td_fc_partida_total+"</tr>";
            let tr_buttons = "<tr class='tr_elimina_partida'>"+
                "<td colspan='5' class='td_elimina_partida'>"+
                "<button type='button' class='btn btn-danger col-md-12 elimina_partida' data-fc_partida_factura_id='"+json.registro_puro.id+"' value='elimina' name='btn_action_next'>Elimina</button>"+
        "</td> </tr>";

            let table_full = "" +
                "<form method='post' action='./index.php?seccion="+entidad_factura+"&accion=modifica_partida_bd&registro_id="+registro_id+"&adm_menu_id="+adm_menu_id+"&session_id="+session_id+"&registro_partida_id="+fc_partida_id+"'>"+
                "<table class='table table-striped data-partida' style='border: 2px solid'><tbody>"+
                td_fc_partida_descripcion_html+tr_data_producto_html+tr_tags+tr_montos+tr_buttons+
                "</tbody>" +
                "</table>"+
                "</form>";
            console.log(json.registro_obj);
            $("#row-partida").prepend(table_full);

            ejecuciones_partida(entidad_factura,entidad_partida);

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
            txt_cantidad.val(1);
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



sl_cat_sat_moneda.change(function () {
    change_moneda();

});

sl_com_producto.change(function () {
    let selected = $(this).find('option:selected');
    let descripcion = selected.data(`com_producto_descripcion`);
    let unidad = selected.data(`cat_sat_unidad_descripcion`);
    let impuesto = selected.data(`cat_sat_obj_imp_descripcion`);
    let obj_imp = selected.data(`cat_sat_obj_imp_id`);
    let tipo_factor = selected.data(`cat_sat_tipo_factor_descripcion`);
    let factor = selected.data(`cat_sat_factor_factor`);
    let aplica_predial = selected.data('com_producto_aplica_predial');
    let precio = selected.data('com_producto_precio');
    let cat_sat_conf_imps_id = selected.data('cat_sat_conf_imps_id');
    let txt_cantidad = $("#cantidad");
    let txt_valor_unitario = $("#valor_unitario");





    txt_cuenta_predial.prop( "disabled", true );
    if(aplica_predial === 'activo'){
        txt_cuenta_predial.prop( "disabled", false );
    }
    //if(parseInt(cat_sat_conf_imps_id) !== 1) {
        sl_cat_sat_conf_imps_id.val(cat_sat_conf_imps_id);
        sl_cat_sat_conf_imps_id.selectpicker('refresh');

        sl_cat_sat_obj_imp_id.val(obj_imp);
        sl_cat_sat_obj_imp_id.selectpicker('refresh');
    //}

    txt_descripcion.val(descripcion);
    txt_unidad.val(unidad);
    txt_impuesto.val(impuesto);
    txt_tipo_factor.val(tipo_factor);
    txt_factor.val(factor);
    txt_valor_unitario.val(precio);


    let cantidad = txt_cantidad.val();
    let subtotal = precio * cantidad;
    let descuento = $("#frm-partida #descuento").val();
    let total = subtotal-descuento;


    $("#frm-partida #subtotal").val(subtotal);
    $("#frm-partida #total").val(total);

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




