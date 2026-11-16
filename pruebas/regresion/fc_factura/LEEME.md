# Pruebas unitarias nuevas de factura

## Ejecución reproducible

Desde la raíz del repositorio, instalar herramientas con `composer install --working-dir=pruebas/herramientas --no-plugins --no-scripts` y ejecutar `python3 pruebas/regresion/ejecutar_phpunit.py`. Usa PHPUnit 10.5.66, clases TestCase y procesos separados. El registro central contiene nombres exactos y conteos, y el resultado no cero bloquea commit. No cargar configuración ni autoload de producción. Los scripts iniciales de caracterización no son parte del conjunto activo.

## Cobertura inicial: 10 casos

- Constructor de modelo, validación crítica activada/desactivada: tabla, clave, etiqueta y expresión UUID.
- Reportes de facturación y ventas: carga de registro ficticio y colección vacía; verifica rango completo del mes y filtro TIMBRADO exactos, y retorno sin modificación.
- Controlador alta y modifica: modalidades ws activada/desactivada, header falso; verifica delegación de opciones, identidad del modelo, retorno y dependencias de partida.

## Aislamiento y límites

Se carga el código real de los dos archivos objetivo. Las clases padre y colaboradores son dobles declarados expresamente antes de cargarlos. No se carga el autoloader de producción. PDO se construye sin DSN y bloquea query/prepare/exec; Las clases PDO dobles no establecen conexión. No hay conexión SQL ni servicios externos. Son pruebas unitarias de contratos, NO pruebas de integración: no comprueban consultas reales, transacciones, herencia real, HTML renderizado, constructor completo del controlador, timbrado, alta persistente, cancelaciones ni efectos contables. Un retorno ficticio del padre no demuestra que el padre funciona.

Los reportes incluyen la palabra TIMBRADO únicamente como valor del filtro esperado; no se timbra nada. La vista alta utiliza configuración ficticia sin layout ni agentes; esas ramas quedan pendientes.

## Evidencia de fuente

Remoto main verificado: `14e8dbaf6e3ad70797315fdc28240228e7f1acdc`.

Archivos del worktree NC cotejados byte a byte con esa revisión:
- orm/fc_factura.php: SHA256 `2c425ca4ddaa25ada07d14a38796dc40c2a4980caebeaf474937880d00a1306d`
- controllers/controlador_fc_factura.php: SHA256 `aad676779be98eb763f735cc1acf00587cd1f5804348dcc4fe3ddc2ad009cb46`

El snapshot de rendimiento era distinto en el modelo. Se repitieron las 10 pruebas contra los archivos coincidentes con main sin modificar expectativas: 10/10. No se cotejó producción desde este subtrabajo.

## Suite histórica y siguiente paso

Se retiró tests/ exclusivamente de la copia integrada nueva tras respaldo completo e inventario con hashes; consultar docs/regresion/PROTOCOLO.md. No ejecutar las pruebas históricas contra producción. Las nuevas suites corren separadas porque sus dobles comparten nombres. Integración y HTML real siguen pendientes y no se acreditan con resultados unitarios.
