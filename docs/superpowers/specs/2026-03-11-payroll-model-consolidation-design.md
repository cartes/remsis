# Payroll model consolidation design

## Problema

El modulo `Payroll` mantiene hoy dos modelos (`Payroll` y `PayrollLine`) sobre la misma tabla `payrolls`.

Eso genera ambiguedad de dominio:

- el wizard y `PayrollCalculationService` operan con `PayrollLine`,
- el historial, relaciones y seeders usan `Payroll`,
- ambos modelos describen la misma persistencia con responsabilidades superpuestas.

Aunque ya se corrigio el descalce inmediato de columnas (`overtime_amount` y `gratification_amount`), la duplicidad sigue elevando el riesgo de bugs, dificulta el mantenimiento y vuelve confuso cualquier cambio futuro en calculo, edicion o visualizacion de remuneraciones.

## Objetivo

Consolidar `Payroll` como modelo canonico y unico punto principal de acceso a la tabla `payrolls`, manteniendo compatibilidad temporal con `PayrollLine` durante la transicion.

## Decision de diseno

### Modelo canonico

`Modules\\Payroll\\Models\\Payroll` sera la entidad operativa oficial para:

- calculo de remuneraciones por periodo,
- edicion desde el wizard,
- historial de remuneraciones,
- relaciones con `Employee`, `Company`, `PayrollPeriod` y `PayrollDetail`.

### Compatibilidad transitoria

`Modules\\Payroll\\Models\\PayrollLine` dejara de ser el modelo principal.

En esta fase quedara solo como capa legacy de compatibilidad para evitar rupturas inmediatas en referencias internas o codigo auxiliar aun no migrado. Su retiro definitivo se abordara en una fase posterior, una vez comprobado que no quedan consumidores reales.

## Alcance de esta fase

### Incluye

- Migrar `PayrollCalculationService` para leer y escribir `Payroll`.
- Migrar `PayrollPeriodController` y el wizard para operar sobre `Payroll`.
- Mantener la tabla `payrolls` como almacenamiento unico para los registros calculados por periodo y empleado.
- Conservar el comportamiento actual de rutas, vistas y UX.
- Añadir pruebas que verifiquen que calculo y edicion del wizard persisten correctamente usando `Payroll`.

### No incluye

- Crear una tabla nueva `payroll_lines`.
- Redisenar el dominio completo de cabecera vs detalle.
- Eliminar inmediatamente `PayrollLine`.
- Cambiar URLs, estructura visual o navegacion.
- Replantear `PayrollDetail` mas alla de asegurar compatibilidad con `Payroll`.

## Arquitectura resultante

El flujo consolidado queda asi:

1. `PayrollPeriod` define el contexto del proceso.
2. `PayrollCalculationService` calcula por empleado y persiste sobre `Payroll`.
3. `PayrollPeriodController` consulta y actualiza registros `Payroll`.
4. El wizard edita esos mismos registros.
5. El historial lista esos mismos registros.

Con esto desaparece la separacion artificial entre "linea operativa" e "historial" cuando ambos en realidad representan la misma fila de `payrolls`.

## Estrategia de implementacion

### Paso 1: mover consumidores internos

- Cambiar imports, tipos de retorno y consultas en `PayrollCalculationService` para usar `Payroll`.
- Cambiar bindings y consultas en `PayrollPeriodController` para usar `Payroll`.
- Revisar cualquier uso directo del nombre `PayrollLine` dentro del modulo y migrarlo si corresponde.

### Paso 2: dejar compatibilidad controlada

- Reducir `PayrollLine` a una clase legacy minima.
- Evitar que introduzca schema, relaciones o reglas distintas a `Payroll`.
- Documentar en el codigo que su uso es transitorio.

### Paso 3: asegurar consistencia

- Verificar que `fillable`, relaciones y casts relevantes vivan en `Payroll`.
- Alinear cualquier acceso del wizard a esos campos.
- Confirmar que tenancy (`BelongsToTenant`) sigue intacto en el modelo canonico.

## Manejo de errores y compatibilidad

- No se cambiaran nombres de rutas ni payloads del wizard en esta fase.
- Si existe codigo legacy que siga importando `PayrollLine`, continuara funcionando mientras dure la compatibilidad.
- No se agregaran silencios ni fallbacks amplios: si una relacion o columna requerida no existe, los tests deben detectarlo.

## Testing

Se agregaran o ajustaran pruebas para cubrir:

- calculo de un periodo persiste registros usando `Payroll`,
- edicion de una linea desde el wizard actualiza el mismo registro `Payroll`,
- los tests existentes de tenancy siguen pasando sobre el modelo canonico,
- la vista y controladores siguen resolviendo correctamente el flujo company-owned.

## Riesgos

- Puede existir codigo no auditado aun que siga dependiendo explicitamente de `PayrollLine`.
- `PayrollDetail` podria asumir implicitamente cierta semantica del modelo padre, por lo que debe validarse en pruebas.
- La eliminacion definitiva de `PayrollLine` no debe hacerse hasta confirmar que ya no aporta compatibilidad real.

## Resultado esperado

Al finalizar esta fase, `Payroll` sera el unico modelo principal del dominio de remuneraciones sobre `payrolls`, el wizard y el calculo trabajaran sobre la misma entidad y `PayrollLine` quedara identificado como compatibilidad legacy lista para retiro futuro.
