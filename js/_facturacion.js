
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

    return {
        cte_formulario: cte_formulario, td_fc_partida_cantidad: td_fc_partida_cantidad,
        td_fc_partida_valor_unitario: td_fc_partida_valor_unitario, td_fc_partida_descuento: td_fc_partida_descuento,
        td_fc_partida_descripcion: td_fc_partida_descripcion,td_elimina_partida: td_elimina_partida
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
