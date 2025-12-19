let url = get_url("fc_factura","filtro_and", {});

$('#com_sucursal_id').on('changed.bs.select', function (e, clickedIndex, isSelected, previousValue) {
    const com_sucursal_id = $(this).val();
    console.log('url:', url);
    console.log('com_sucursal_id:', com_sucursal_id);

    $.ajax({
        url : url,
        data : { com_sucursal_id : com_sucursal_id},
        type : 'POST',
        success : function(json) {
            console.log(json);
        },

        error : function(xhr, status) {

            console.log(xhr);
            console.log(status);
        },

        // código a ejecutar sin importar si la petición falló o no
        complete : function(xhr, status) {
            //alert('Petición realizada');
        }
    });

});