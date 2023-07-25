

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