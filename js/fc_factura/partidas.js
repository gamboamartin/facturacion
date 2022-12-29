let url = getAbsolutePath();

let session_id = getParameterByName('session_id');

let btn_alta = $("[name=btn_action_next]");
let txt_cantidad = $('#cantidad');
let txt_valor_unitario = $('#valor_unitario');
btn_alta.on( "click", function() {
    if(txt_cantidad.val() <= 0){
        alert('Cantidad no puede ser menor o igual a 0');
        return false;
    }
    if(txt_valor_unitario.val() <= 0){
        alert('Valor Unitario no puede ser menor o igual a 0');
        return false;
    }
});