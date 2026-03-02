async function timbra_recibos(registro_id) {
  let done = false;

  while (!done) {
    const url = `index.php?seccion=fc_layout_nom&accion=timbra_recibos&registro_id=${registro_id}&ws=1`;

    const r = await fetch(url, { headers: { "Accept": "application/json" } });
    const data = await r.json();

    if (data.status === "procesando") {
      console.log(`Pendientes: ${data.pendientes} | lote: ${data.lote} | procesados: ${data.procesados}`);
      await new Promise(res => setTimeout(res, 900));
      continue;
    }

    if (data.status === "finalizado") {
      done = true;
      window.location.href = data.download_url; // dispara descarga Excel
    }
  }
}
