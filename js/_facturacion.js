
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
function ejecuciones_partida(entidad_factura, entidad_partida){
    $(".fc_partida_descripcion").change(function () {

        let data = valores_partida($(this));
        let contenedores = tds($(this));

        data = init_data(data);

        if(!data){
            alert('Error al inicializa data');
            return false;
        }
        modifica_partida_bd(contenedores, data, entidad_factura);

    });

    $(".fc_partida_cantidad").change(function () {
        let data = valores_partida($(this));
        let contenedores = tds($(this));

        data = init_data(data);

        if(!data){
            alert('Error al inicializa data');
            return false;
        }
        modifica_partida_bd(contenedores, data,entidad_factura);

    });


    $(".fc_partida_valor_unitario").change(function () {

        let data = valores_partida($(this));
        let contenedores = tds($(this));

        data = init_data(data);

        if(!data){
            alert('Error al inicializa data');
            return false;
        }
        modifica_partida_bd(contenedores, data,entidad_factura);


    });

    $(".fc_partida_descuento").change(function () {

        let data = valores_partida($(this));
        let contenedores = tds($(this));

        data = init_data(data);

        if(!data){
            alert('Error al inicializa data');
            return false;
        }
        modifica_partida_bd(contenedores, data,entidad_factura);

    });

    $(".elimina_partida").click(function () {
        elimina_partida_bd($(this),entidad_partida);
    });
}
function elimina_partida_bd(boton, entidad_partida){

    let registro_partida_id = boton.data('fc_partida_factura_id');
    let url = get_url(entidad_partida,"elimina_bd", {});

    url = url+"&registro_id="+registro_partida_id;

    let ct = boton.parent().parent().parent();
    $.ajax({

        url : url,
        type : 'GET',

        success : function(json) {
            console.log(json);
            alert(json.mensaje);

            if(!isNaN(json.error)){
                alert(url);
                if(json.error === 1) {
                    return false;
                }
            }
            console.log(json);
            ct.hide();

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

function tr_data_producto(json){
    let td_com_producto_codigo = "<td>" +
                                            "<b>CVE SAT: </b>"+json.registro_obj.com_producto_codigo+
                                        "</td>";

    let td_cat_sat_unidad_descripcion = "<td>" +
        "                                           <b>Unidad: </b>"+json.registro_obj.cat_sat_unidad_descripcion+
                                                "</td>";

    let td_cat_sat_obj_imp_descripcion = "<td colspan='2'>" +
        "                                           <b>Obj Imp: </b>"+json.registro_obj.cat_sat_obj_imp_descripcion+
                                                "</td>";

    return "<tr>"
                + td_com_producto_codigo +
                td_cat_sat_unidad_descripcion +
                td_cat_sat_obj_imp_descripcion +
            "</tr>";
}

function td_fc_partida_descripcion(json){
    let input_descripcion = input_txt('fc_partida_descripcion','descripcion',
        json.registro_puro.descripcion);
    return "<tr class='tr_fc_partida_descripcion'>" +
                "<td colspan='5' class='td_fc_partida_descripcion' data-fc_partida_factura_id='"
                    + json.registro_puro.id + "'>"
                        + input_descripcion +
                "</td>" +
            "</tr>";
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

function input_txt(name_class, name_input, valor){
    return "<input type='text' class='form-control form-control-sm " + name_class + "' " +
        "name='" + name_input + "' value='" + valor + "'/>";
}

function modifica_partida_bd(contenedores, data, entidad_factura){

    let url = get_url(entidad_factura,"modifica_partida_bd", {});
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

            contenedores.td_elimina_partida.children('.elimina_partida').data('fc_partida_factura_id', registro_partida_id);
            contenedores.td_fc_partida_descripcion.data('fc_partida_factura_id', registro_partida_id);
            contenedores.fc_partida_sub_total_base.children('.fc_partida_sub_total_base').val(json.registro_puro.sub_total_base);
            contenedores.fc_partida_sub_total.empty();
            contenedores.fc_partida_traslados.empty();
            contenedores.fc_partida_retenciones.empty();
            contenedores.fc_partida_total.empty();

            let subtotal_rs = "<b>Sub Total:</b> "+json.registro_puro.sub_total;
            let traslados_rs = "<b>Traslados:</b> "+json.registro_puro.total_traslados;
            let retenciones_rs = "<b>Retenciones:</b> "+json.registro_puro.total_retenciones;
            let total_rs = "<b>Total:</b> "+json.registro_puro.total;

            contenedores.fc_partida_sub_total.html(subtotal_rs);
            contenedores.fc_partida_traslados.html(traslados_rs);
            contenedores.fc_partida_retenciones.html(retenciones_rs);
            contenedores.fc_partida_total.html(total_rs);


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

function td_input(name_class, name_input, valor){
    let input_data = input_txt(name_class,name_input,valor);
    return "<td class='td_" + name_class + "'>" + input_data + "</td>";
}

function tds(contenedor){
    let cte_formulario = contenedor.parent().parent().parent();
    let td_fc_partida_cantidad = cte_formulario.children(".tr_data_partida").children(".td_fc_partida_cantidad");
    let td_fc_partida_valor_unitario = cte_formulario.children(".tr_data_partida").children(".td_fc_partida_valor_unitario");
    let td_fc_partida_descuento = cte_formulario.children(".tr_data_partida").children(".td_fc_partida_descuento");
    let td_fc_partida_descripcion = cte_formulario.children(".tr_fc_partida_descripcion").children(".td_fc_partida_descripcion");
    let td_elimina_partida = cte_formulario.children(".tr_elimina_partida").children(".td_elimina_partida");
    let fc_partida_sub_total_base = cte_formulario.children(".tr_data_partida").children(".td_fc_partida_sub_total_base");
    let fc_partida_sub_total = cte_formulario.children(".tr_data_partida_rs").children(".td_fc_partida_sub_total");
    let fc_partida_traslados = cte_formulario.children(".tr_data_partida_rs").children(".td_fc_partida_traslados");
    let fc_partida_retenciones = cte_formulario.children(".tr_data_partida_rs").children(".td_fc_partida_retenciones");
    let fc_partida_total = cte_formulario.children(".tr_data_partida_rs").children(".td_fc_partida_total");

    return {
        cte_formulario: cte_formulario, td_fc_partida_cantidad: td_fc_partida_cantidad,
        td_fc_partida_valor_unitario: td_fc_partida_valor_unitario, td_fc_partida_descuento: td_fc_partida_descuento,
        td_fc_partida_descripcion: td_fc_partida_descripcion,td_elimina_partida: td_elimina_partida,
        fc_partida_sub_total_base: fc_partida_sub_total_base,
        fc_partida_sub_total: fc_partida_sub_total,fc_partida_traslados:fc_partida_traslados,
        fc_partida_retenciones:fc_partida_retenciones,fc_partida_total:fc_partida_total
    };
}

function tr_tags_partida(){

    return "<tr>" +
        "<td><b>Cantidad</b></td>" +
        "<td><b>Valor Unitario</b></td>" +
        "<td><b>Importe</b></td>" +
        "<td><b>Descuento</b></td>" +
        "</tr>";

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

window.onload = function() {
    $("#fc_csd_id").change(function () {
        let selected = $(this).find('option:selected');
        let serie = selected.data(`fc_csd_serie`);

        $("#serie").val(serie);
    });
};
