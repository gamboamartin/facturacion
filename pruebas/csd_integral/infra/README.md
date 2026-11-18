# Infraestructura aislada para alta CSD integral

Adaptación del launcher documental acreditado (13 pruebas PASS), no una prueba integral aprobada por sí sola.

```sh
python3 pruebas/csd_integral/infra/ejecutar.py \
  --engine-root /ruta/rootfs-mariadb-verificado \
  --deps /ruta/dependencias-oficiales \
  --documento /ruta/candidato-documento \
  --plugins /ruta/candidato-plugins \
  --ddl /ruta/esquema-csd-sanitizado.json \
  --solo-esquema
```

Quitar `--solo-esquema` únicamente cuando `esquema.php`, `precondiciones.php` y `escenario.php` estén preparados y revisados. El modo limitado se informa como METADATOS_PRESENTES_NO_PRUEBA_ALTA, nunca PASS integral. El escenario completo requiere JSON único con estado PASS y casos_ejecutados igual a 3. No modificar este criterio para acomodar un fallo.

Se conserva aislamiento de red/PID/usuario, memoria AS 2 GiB, dos CPU, timeout global 180/190 segundos y PHP 256 MiB. Motor efímero sin TCP/eventos/binlog, cuenta sólo para qa_csd_integral. Todos los binds del host son readonly, incluidas dependencias y candidatos. No hay configuración de FC3 ni certificados reales. Se superponen exactamente doc_documento.php y ssl.php/Importador.php candidatos; las clases de Facturación usan sus mappings Composer originales. No cargar otros plugins ni usar red. PHP ejecuta sin shell/procesos/red, y la capa namespace no tiene rutas.

`/qa/bootstrap.php` carga configuración sintética y autoload. Raíz del código: `/var/www/html/facturacion`. Directorios privados propios `archivos/doc_documento` y `archivos/temporales` se crean sólo dentro de tmpfs. Credenciales ficticias e identidad de la instancia están en `/qa-credenciales-ficticias.json` y `/qa-instance-id`, ambos archivos efímeros. El DDL saneado se monta readonly en `/qa/esquema-csd-sanitizado.json`. El archivo placeholder versionado nunca constituye DDL acreditado.

La configuración OpenSSL es ficticia. Los certificados deben generarse dentro del namespace; ninguna llave ni contraseña real debe entrar al ensayo. No hay PAC ni operaciones fiscales. La salida del escenario debe ser un resumen sin contraseñas ni contenido de llaves. Captura de stdout no equivale a demostrar ausencia de secretos: el escenario debe comprobar su propio contrato.

El runner registra hashes previos y verifica que runtime y scripts no hayan cambiado concurrentemente. Cualquier drift invalida la corrida. Shutdown SQL sólo tras comprobar server_id, socket, datadir, PID y hash del ejecutable; se espera salida real del motor. Fallos de cleanup se informan aparte; inspección /proc puede tener entradas no legibles y se declara su límite.

Primer ensayo sólo esquema: aislamiento y cierre acreditados; constructor cat_sat requerido PDO no suministrado por esquema.php. No se ejecutó alta. Evidencia local conservada en resultado-infra-01.jsonl, no se interpreta como fallo de negocio ni se cambia expectativa.
