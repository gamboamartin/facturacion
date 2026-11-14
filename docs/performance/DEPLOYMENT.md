# Despliegue: bloqueado para este commit

Este commit conserva investigación y pruebas; no cambia controladores, ORM,
autoload principal, servicios ni configuración. **No es una release operativa.**
No ejecutar `git pull` de esta rama directamente en Facturación3 ni migrar sus
bases a partir de estos módulos experimentales.

## Puerta de salida para un parche posterior
- Identificar exactamente el cuello de botella y el diff mínimo de rendimiento.
- Medir antes/después con la misma carga y comprobar resultados y permisos.
- Probar altas/modificaciones y rollback; si toca cálculos, cubrir concurrencia,
  precisión, referencias y todos los writers afectados antes de promoverlo.
- Revisión independiente del diff, pruebas y selección de archivos.
- Respaldar archivos y base de destino, verificar recuperación y definir una
  reversión concreta, sin reemplazar configuraciones propias de Facturación3.
- Preservar bloqueos de salida, PAC inhabilitado y recursos locales. No modificar
  producción original ni activar envíos/timbrado como efecto del despliegue.
- Desplegar únicamente el parche aprobado, comprobar salud y funcionalidad;
  volver al respaldo si una verificación falla.

## Reversión de este avance documental
No hay migración o servicio que revertir: el paquete no se conecta a la aplicación.
Cualquier futura integración deberá tener su propia estrategia probada. Un
`revert` de Git no sustituye restaurar datos cuando un cambio sí modifica una DB.
