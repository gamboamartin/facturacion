# Precio de producto

Ejecutar desde la raíz del checkout:

```sh
python3 pruebas/producto_precio/ejecutar.py
```

38 casos y 94 aserciones. Carga el modelo real propio con dobles explícitos de la
herencia y de la regeneración de impuestos. Comprueba alta y modificación, la
conservación de cero/negativos/decimales y actualizaciones sin precio. Rechaza
texto, vacío, null, arrays, booleanos y valores no finitos antes de llegar a
persistencia o regenerar impuestos. No cambia los valores aceptados ni limita su
signo o precisión.

No necesita configuración privada, PDO ni PAC. El laboratorio HTTP/MySQL separado
comprueba además el contrato con la herencia instalada y la persistencia real.
