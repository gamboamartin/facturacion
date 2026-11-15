# Inicialización de importes del concepto de complemento de pago

## Cambio
`fc_complemento_pago::fc_partida_cp_ins()` inicializa `total` y
`total_retenciones` en cero, igual que el valor unitario y descuento del concepto
«Pago». Esos campos son obligatorios y no tienen DEFAULT en el esquema probado.

No cambia importes pagados, saldos, impuestos DR/P, catálogos ni cálculos fiscales.
No requiere migración, Composer ni dependencias nuevas.

## Comprobaciones
- Regresión autocontenida del método: 12 casos PASS, más dos omisiones
  rechazadas en memoria. Comando: `php -n -d extension=tokenizer
  tests/regression/cp_zero_totals.php`. Lint de PHP correcto.
- Alta natural de CP en laboratorio aislado: cinco INSERT correctos.
- Factura sintética 100 + IVA 16, pago 58: DR y P con base 50/IVA 8 y saldo 58.
- Excepción deliberada del llamador tras la operación: rollback y restauración
  íntegra, con auditoría conciliada.

Las pruebas funcionales utilizaron exactamente este archivo candidato y finalizaron
con rollback. No certifican timbrado, autorización web, concurrencia ni ledger.
Los runners experimentales no forman parte de este cambio.

## Despliegue acotado y reversión
Aplicar únicamente el PHP del modelo en Facturación 3 después de verificar el
hash de origen y guardar una copia fuera del directorio público. Conservar
propietario/permisos y usar reemplazo atómico. Verificar lint, hash y respuesta
HTTPS; ante fallo, restaurar la copia original y comprobar su hash. No modificar
la aplicación original ni base de datos, servicios o configuración.
