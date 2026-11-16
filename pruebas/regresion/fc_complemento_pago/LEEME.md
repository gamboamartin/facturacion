# Complemento de pago: contratos PHPUnit

12 métodos individuales, 12 aserciones: partida con referencias ficticias (producto 91002, unidad 91001, complemento 91003), cuatro tasas (0, 0.16, 0.106666666, 1), dos mapeos fiscales y dos banderas sin error, columnas, filtros y menú del controlador. Entradas y expectativas estrictas están en cada método de ComplementoPagoTest.php y se registran por nombre en registro.json.

La partida debe conservar total=0 y total_retenciones=0. No se modificó la expectativa para acomodar el candidato Partida que los omitía. Las tasas esperadas son cadenas de seis decimales; los mapeos usan base ficticia 1000 e importe 160. No hay operaciones reales.

Las clases FC bajo prueba son código real del repositorio. soporte_cp.php simula padres y validación mínima. No acredita validación real, constructor completo, persistencia, alta completa, vistas o CFDI. La caracterización inicial con validador real queda como evidencia histórica externa, no dependencia del ejecutor canónico. PHPUnit corre cada caso aislado.
