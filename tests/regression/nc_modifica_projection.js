/* Pure V8/Node regression; mocked DOM only, no network/application/DB. */
function testNcModification(source, shared) {
    let checks = 0;
    const check = (ok, message) => { checks++; if (!ok) throw Error(message); };
    new Function(source); new Function(shared);
    const prefix = source.slice(0, source.indexOf('let session_id ='));
    let mutations = [], alerts = [];
    class Target {
        constructor(length=1) { this.length=length; }
        val(v) { mutations.push(v); return this; }
        empty() { mutations.push('empty'); return this; }
        append(v) { mutations.push(v); return this; }
        text(v) { return {text:v}; }
    }
    let headerTargets, rowTargets, row, totals, amount;
    const dollar = selector => selector === '<b>' ? new Target() : {first:()=>({find:s=>headerTargets[s]||new Target(0)})};
    const api = new Function('$','alert','document',prefix+';return {project:nc_modificacion_respuesta,apply:nc_aplica_modificacion};')(dollar,m=>alerts.push(m),{createTextNode:s=>({text:s})});
    const response = () => ({registro_id:52,registro_puro:{id:52,cantidad:3,valor_unitario:100,descuento:0,sub_total_base:300,sub_total:300,total_traslados:48,total_retenciones:0,total:348},registro_actualizado:{fc_nota_credito_sub_total:300,fc_nota_credito_total_descuento:0,fc_nota_credito_total_traslados:48,fc_nota_credito_total_retenciones:0,fc_nota_credito_total:348}});
    check(api.project(response()).cabecera.total==='348.00','authoritative joined header');
    let bad=response();bad.registro_obj={fc_nota_credito_total:116};check(api.project(bad).cabecera.total==='348.00','ignore stale alta header');
    for(const field of Object.keys(response().registro_puro).filter(x=>x!=='id')) {
        for(const value of [null,true,{},[],undefined,'',Infinity,NaN,'oops']) {bad=response();bad.registro_puro[field]=value;check(api.project(bad)===null,'invalid line '+field);}
    }
    for(const field of Object.keys(response().registro_actualizado)) {bad=response();delete bad.registro_actualizado[field];check(api.project(bad)===null,'missing header');}
    for(const value of [null,{}, {error:1}, {...response(),error:'1'}, {...response(),registro_id:53}, {...response(),registro_id:'052'}])check(api.project(value)===null,'bad response/id');
    function setup() {
        mutations=[]; alerts=[];headerTargets={};rowTargets={};amount=new Target();totals=new Target(4);totals.eq=()=>new Target();
        for(const f of ['subtotal','descuento','impuestos_trasladados','impuestos_retenidos','total'])headerTargets['[id="'+f+'"]']=new Target();
        for(const f of ['cantidad','valor_unitario','descuento'])rowTargets['input.fc_partida_'+f]=new Target();
        row=new Target();row.find=s=>rowTargets[s]||new Target(0);row.next=()=>({children:()=>totals});row.children=()=>({eq:()=>({children:()=>amount})});
        const body=new Target();body.children=()=>row;
        return {cte_formulario:body,td_fc_partida_descripcion:{data:()=>52}};
    }
    const denied=setup();check(api.apply(denied,{error:1,mensaje:'Acceso denegado'})===false,'server denial');check(alerts[0]==='Acceso denegado'&&mutations.length===0,'preserve server error without DOM mutation');
    const c=setup();check(api.apply(c,response())===true,'valid DOM update');check(mutations.includes('348.00')&&mutations.includes('300'),'header and line updated');check(alerts.length===0,'no success alert');
    for(const defect of ['missing_header','duplicate_header','missing_amount','wrong_summary','wrong_id','bad_response']) {
        const c=setup();let json=response();
        if(defect==='missing_header')delete headerTargets['[id="total"]'];
        if(defect==='duplicate_header')headerTargets['[id="total"]']=new Target(2);
        if(defect==='missing_amount')amount.length=0;
        if(defect==='wrong_summary')totals.length=3;
        if(defect==='wrong_id')c.td_fc_partida_descripcion.data=()=>53;
        if(defect==='bad_response')json.registro_puro.total=null;
        check(api.apply(c,json)===false,defect);check(mutations.length===0,'no partial mutations '+defect);
    }
    const start=shared.indexOf('function modifica_partida_bd('), success=shared.indexOf('success : function(json)',start);
    check(shared.slice(success,shared.indexOf('console.log(json)',success)).includes("if (entidad_factura === 'fc_nota_credito')"),'NC-only dispatch');
    check(source.includes("const cabecera = $('.form-main form.form-additional').first();"),'header scope');
    return {checks, result:'PASS', scope:'pure projection and mocked DOM; no browser or DB'};
}
if (typeof module !== 'undefined' && require.main === module) {
    const fs=require('fs'),path=require('path'),root=path.resolve(__dirname,'../..');
    console.log(testNcModification(fs.readFileSync(path.join(root,'js/fc_nota_credito/modifica.js'),'utf8'),fs.readFileSync(path.join(root,'js/_facturacion.js'),'utf8')));
}
