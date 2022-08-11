function getParameterByName(name) {
    name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
    const regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
        results = regex.exec(location.search);
    return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
}
function integra_new_option(container, descripcion, value){
    let new_option =new_option_sl(descripcion,value);
    $(new_option).appendTo(container);
}


function new_option_sl(descripcion,value){
    return "<option value =" + value + ">" + descripcion + "</option>";
}