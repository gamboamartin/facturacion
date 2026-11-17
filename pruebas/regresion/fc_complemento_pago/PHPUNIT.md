# Complemento de pago: batería PHPUnit nueva

## Ejecución verificada

PHPUnit 10.5.66, PHP 8.3.17, procesos aislados: **22 pruebas, 40 aserciones, todas exitosas** contra main 14e8dba. Requiere `RAIZ_REPO` explícita o constante `RAIZ_REPOSITORIO_PRUEBAS`. No depende del runtime Partida.

## Casos

Se conservan intactas las 12 expectativas iniciales: partida con identificadores ficticios; tasas cero, dieciséis por ciento, redondeo a seis decimales y unidad; mapeo traslado/retención y estado de error; columnas, filtros y menú del controlador.

Se agregan:
- Seis casos de rechazo: unidad, producto y complemento con identificador cero o negativo. Comprueban mensaje, valor rechazado y estado de error.
- Fallo simulado del validador de claves: comprueba propagación y que no continúa la validación numérica.
- Fallo simulado del validador numérico: comprueba propagación, orden de llamadas y ausencia del resultado de impuesto.
- Controlador sin modelo inicializado: comprueba el contrato técnico de inicialización mediante Error. No representa manejo de errores de formulario ni respuesta HTTP.
- Repetición de listado: misma configuración, tabla sin cambios y ausencia de error.

## Aislamiento y límites

Los archivos concretos de modelo/controlador son reales. Los padres, el contenedor de errores y el validador son dobles mínimos explícitos; los dos fallos inyectados verifican la reacción del modelo, no la implementación del paquete de validación. No existe PDO, red, credenciales, transacciones ni timbrado. Se evita el constructor real de aplicación.

Esta batería es de unidades aisladas y **no acredita** alta/persistencia, constructores completos, formularios, permisos reales, base de datos, generación de CFDI ni integración entre paquetes reales. Hace falta una capa de integración aislada para ello.

No se modificó aplicación, expectativas anteriores, commits ni pushes. La primera discrepancia histórica provenía de ejecutar un candidato Partida local en lugar de main; no se corrigió cambiando expectativas.
