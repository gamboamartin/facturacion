# Integración CSD obligatoria

`registro.json` registra tres casos de ORM real con MariaDB efímera: éxito, contraseña incorrecta y pareja CER/KEY distinta. En cada caso se exige reversión de conteos, retirada de archivos propios e inventario idéntico. No sustituye pruebas HTTP/controlador, commit de alta, FPM ni validación de certificados SAT reales.

## Ejecución reproducible

Preparar dependencias oficiales en copia aislada y las fuentes candidatas. El motor debe coincidir con los hashes de `infra/engine-pins.json`. El DDL debe estar saneado y acreditado; no se distribuyen aquí esquemas operativos, binarios, credenciales, certificados ni datos reales.

```sh
export QA_CSD_ENGINE_ROOT=/ruta/rootfs-verificado
export QA_CSD_DEPS=/ruta/dependencias-aisladas
export QA_CSD_DOCUMENTO=/ruta/candidato-documento
export QA_CSD_PLUGINS=/ruta/candidato-plugins
export QA_CSD_DDL=/ruta/ddl-sanitizado-acreditado.json
python3 pruebas/csd_integral/ejecutar.py
```

Si falta un recurso, la suite resulta **BLOQUEADO** y devuelve código distinto de cero. No se omite, no reutiliza un resultado anterior y bloquea commit. Los resultados se guardan en `pruebas/resultados/csd_integral.json`; evidencia completa y resumen histórico únicos en el directorio privado `.git/qa/csd-integral/ejecuciones`. No agregar esos logs voluminosos al repositorio.

El ejecutor comprueba JSON de cada paso, tres casos exactos, expectativas de rechazo, recuperación, FK activas, hashes de clases efectivamente cargadas y ausencia de cambios concurrentes. Exige aislamiento, shutdown con identidad verificada, salida real del motor y ausencia de procesos visibles restantes. La limitación de lectura de `/proc` se informa, no se oculta.

`infra/README.md` describe límites y aislamiento. No relajar seguridad del host para ejecutar esta suite. Nunca ejecutarla contra un schema existente o configuración activa. Todo el almacenamiento del ensayo es efímero y sintético; el código y dependencias del host se montan sólo lectura.

## Separación de baterías

Esta integración se añade a la batería vigente como tipo `integracion`, con ejecutor propio. No reemplaza ni modifica las 155 pruebas unitarias originales, las focales de compensación, frontend o las pruebas legacy de paquetes. Un PASS de otra batería no cubre un recurso ausente o un fallo de esta integración.

## Batería vigente obligatoria

`pruebas/regresion/registro.json` mantiene intactas las 155 pruebas originales y registra por separado 46 focales y esta integración de 3 casos. `pruebas/ejecutar_completa.py` ejecutó los 10 pasos: 155 pruebas/539 aserciones, 46 focales, 3 integrales ORM y 21 comprobaciones frontend. Todos finalizaron con código 0; estado `PASS_BATERIAS_REGISTRADAS`.

El gate `pruebas/csd_integral/ejecutar.py` exige `QA_CSD_ENGINE_ROOT`, `QA_CSD_DEPS`, `QA_CSD_DOCUMENTO`, `QA_CSD_PLUGINS` y `QA_CSD_DDL`. Si falta cualquiera, registra BLOQUEADO; no reutiliza un resultado anterior ni omite la suite. Compara hashes de clases cargadas con los candidatos, casos exactos, FK, invariantes y cierre de motor/namespace. Conserva historial privado de cada corrida y resumen vigente en `pruebas/resultados/csd_integral.json`.

El timeout del agregador para esta suite es 300 segundos para permitir el cierre del gate; los límites internos del motor y aislamiento permanecen intactos. Una expiración nunca se interpreta como PASS.
