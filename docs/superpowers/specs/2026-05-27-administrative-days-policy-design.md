# Spec: Configuración global de días administrativos por empresa

**Fecha**: 2026-05-27  
**Módulos afectados**: Companies, Employees, Payroll  
**Estado**: Aprobado

---

## Problema

Hoy Remsis permite registrar asistencia, ausencias, licencias, vacaciones y permisos con goce en la ficha del colaborador, pero no existe una política formal de **días administrativos** configurable por empresa.

Eso deja tres vacíos:

- la empresa no puede definir su cupo propio de días administrativos,
- RRHH no puede controlar cuántos administrativos ha usado cada colaborador,
- payroll no tiene una categoría explícita para distinguir este beneficio de otras ausencias o permisos.

La necesidad validada es que cada tenant pueda configurar una política propia con:

- cantidad por ciclo,
- unidad única por empresa (`día entero` o `medio día`),
- periodicidad inicial `anual` o `semestral`,
- vencimiento del remanente al cierre del ciclo,
- efecto equivalente a permiso con goce de sueldo.

## Objetivo

Agregar una configuración global de días administrativos por empresa, con saldo controlado por colaborador y consumo integrado al flujo actual de asistencia, sin crear un módulo paralelo de solicitudes.

## Decisión de diseño

Se implementará el enfoque **política por empresa + saldo por colaborador**, porque cubre el requerimiento actual y deja una base limpia para crecer después sin rediseñar persistencia ni reglas.

### Fuera de alcance en esta fase

- excepciones por colaborador como política independiente,
- arrastre de saldo entre ciclos,
- acumulación progresiva mes a mes,
- workflow de solicitud/aprobación,
- políticas distintas por área, cargo o centro de costo.

---

## Arquitectura funcional

La solución se divide en tres piezas.

### 1. Política global por empresa

Cada empresa tendrá una política activa de días administrativos con estos atributos:

- `is_enabled`
- `days_per_cycle`
- `unit` (`full_day` o `half_day`)
- `cycle_type` (`annual` o `semiannual`)

La política vive en el contexto de empresa y se administra desde la ficha `companies.edit`, dentro de la sección **Remuneraciones**.

En esta fase, para evitar sobreconfiguración:

- `annual` siempre corre del **1 de enero al 31 de diciembre**,
- `semiannual` siempre corre en **enero-junio** y **julio-diciembre**,
- el vencimiento al cierre del ciclo es obligatorio y no configurable.

### 2. Saldo por colaborador y ciclo

Cada colaborador tendrá un saldo materializado por ciclo, en vez de calcularlo implícitamente desde asistencia cada vez.

Ese saldo debe guardar como mínimo:

- colaborador,
- empresa,
- ciclo vigente,
- cantidad asignada,
- cantidad usada,
- cantidad disponible.

Esto permite:

- bloquear sobreconsumo,
- revertir correctamente al editar o eliminar registros,
- mantener trazabilidad del uso,
- evitar cálculos frágiles sobre históricos de asistencia.

### 3. Consumo desde asistencia

El uso de un día administrativo no se registrará en una pantalla nueva. Se integrará al flujo actual de asistencia del colaborador.

Cuando RRHH registre un administrativo:

1. el sistema identifica el ciclo vigente,
2. valida saldo disponible,
3. descuenta la unidad configurada,
4. persiste el consumo ligado al registro de asistencia,
5. deja el evento marcado como **no descontable** para payroll.

Todas estas operaciones deben ocurrir dentro de **una sola transacción** y con bloqueo del saldo del ciclo (`lockForUpdate` o equivalente) para evitar sobreconsumo si dos usuarios editan al mismo colaborador al mismo tiempo.

---

## Modelo de datos propuesto

## Política por empresa

Se recomienda una tabla dedicada para no seguir cargando `companies` con reglas de dominio específicas.

### Tabla sugerida: `company_administrative_day_policies`

```sql
id
company_id
is_enabled
days_per_cycle          decimal(5,2)
unit                    enum(full_day, half_day)
cycle_type              enum(annual, semiannual)
created_at
updated_at
```

Reglas:

- un solo registro activo por empresa,
- `days_per_cycle` admite decimal para soportar cupos equivalentes cuando la unidad sea medio día,
- si `unit = full_day`, `days_per_cycle` solo admite enteros positivos (`1`, `2`, `3`, ...),
- si `unit = half_day`, `days_per_cycle` solo admite múltiplos de `0.5`,
- si ya existen saldos emitidos para el ciclo vigente, no se permiten cambios de `days_per_cycle`, `unit` ni `cycle_type` hasta el siguiente ciclo,
- no se agregan campos de arrastre ni calendario flexible en esta fase.

## Saldos por colaborador

### Tabla sugerida: `employee_administrative_day_balances`

```sql
id
company_id
employee_id
policy_id
cycle_key               string
cycle_starts_on         date
cycle_ends_on           date
assigned_units          decimal(5,2)
used_units              decimal(5,2)
available_units         decimal(5,2)
created_at
updated_at
```

Reglas:

- índice único por `company_id + employee_id + cycle_key`,
- `available_units` **se mantendrá persistido** y se actualizará en cada operación de consumo o reversa,
- `assigned_units` debe reflejar exactamente el cupo vigente del ciclo, no un cálculo dinámico posterior.

### Formato de `cycle_key`

- anual: `2026`
- semestral: `2026-S1` o `2026-S2`

Esto deja una clave humana, estable y fácil de indexar.

## Consumos

Para ligar consumo y reversa de forma robusta, se recomienda una tabla explícita.

### Tabla sugerida: `administrative_day_usages`

```sql
id
company_id
employee_id
attendance_record_id
balance_id
used_units              decimal(5,2)
used_on                 date
notes                   text nullable
created_at
updated_at
```

Esto evita depender de inferencias sobre `attendance_records` al momento de rehacer saldo.

Reglas:

- `attendance_record_id` debe ser **único** dentro de esta tabla para garantizar relación 1:1 entre asistencia y consumo,
- editar un registro existente debe actualizar el usage actual, no crear uno nuevo,
- borrar asistencia debe borrar o revertir su usage dentro de la misma transacción.

## Garantía de unicidad en asistencia

La solución asume un único `attendance_record` por colaborador y fecha, y esa regla debe quedar garantizada a nivel persistencia:

- índice único por `employee_id + date` en `attendance_records`,
- limpieza previa de duplicados si existieran datos históricos incompatibles,
- el flujo seguirá usando `updateOrCreate` sobre esa clave canónica.

---

## Cambios en asistencia

La pestaña actual `tab_asistencia` ya resuelve ausencias y justificaciones. El diseño propuesto reutiliza ese flujo.

### UI

En `Companies > Colaborador > Asistencia`:

- agregar un nuevo estado visible: **Día administrativo**, o
- mantener `ausente` como estado técnico y agregar una justificación explícita `administrative_day`.

La recomendación es **mantener el patrón actual de ausencias justificadas y agregar una justificación explícita `administrative_day`**, porque minimiza ruptura visual y aprovecha la lógica ya existente de motivos.

### Contrato canónico de `justification_type`

La implementación debe dejar un set canónico único y compartido entre UI, asistencia y payroll:

- `administrative_day`
- `permiso_con_goce`
- `permiso_sin_goce`
- `licencia_medica`
- `inasistencia_injustificada`
- `accidente_trabajo`
- `suspension_disciplinaria`

Payroll y asistencia no deben usar aliases alternativos en inglés para estos valores.

### Migración de valores históricos

Antes de dejar activa la nueva lógica, se debe normalizar la data y el código existente hacia el contrato canónico:

- `unpaid_leave` -> `permiso_sin_goce`
- `work_accident` -> `accidente_trabajo`
- `suspension` -> `suspension_disciplinaria`
- `unjustified` -> `inasistencia_injustificada`

Durante la transición, payroll debe leer mediante una capa de normalización centralizada para no romper históricos mientras la migración termina. Al cerrar la implementación, la persistencia nueva debe escribir solo valores canónicos.

### Representación de medios días

El flujo actual de asistencia está modelado por fecha, con un único registro operativo por día. Para no romper ese contrato:

- un día administrativo de empresa configurado como `half_day` consumirá `0.5` unidades,
- seguirá existiendo **un solo registro de asistencia por colaborador y fecha**,
- no se permitirán dos administrativos distintos en la misma fecha,
- la UI debe etiquetar ese registro como **Medio día administrativo** cuando la política de la empresa use `half_day`.

En esta fase no se modelarán dos bloques horarios distintos dentro de un mismo día. El medio día se entiende como una unidad administrativa de consumo, no como un turno parcial detallado.

El modal debe mostrar:

- saldo disponible del ciclo actual,
- unidad configurada por empresa,
- advertencia si la política no está activa,
- bloqueo si el saldo no alcanza.

### Reglas de guardado

Al guardar un administrativo:

- `is_justified = true`,
- `justification_type = administrative_day`,
- no puede coexistir con tipos descontables,
- debe existir una política activa en la empresa,
- debe existir saldo suficiente en el ciclo vigente.

Al editar o eliminar un administrativo:

- se ajusta o elimina el consumo asociado,
- se recompone el saldo del ciclo.

La persistencia de asistencia, usage y balance debe resolverse siempre en una sola transacción.

---

## Efecto en payroll

Los días administrativos deben comportarse como **permiso con goce de sueldo**, pero con semántica explícita.

Por lo tanto:

- no deben entrar al conteo de ausencias descontables,
- no deben disminuir `workedDays`,
- no deben rebajar sueldo base proporcional,
- deben quedar diferenciados de `permiso_con_goce` genérico para reportes y futura trazabilidad.

Los servicios `ProcessEmployeePayroll` y `PayrollCalculationService` hoy filtran ausencias descontables usando `status`, `is_justified` y `justification_type`. La implementación deberá excluir de forma explícita `administrative_day` de cualquier regla de descuento.

---

## Flujos operativos

### Configuración de empresa

1. El admin entra a `companies.edit` en `section=remunerations`.
2. Activa la política de días administrativos.
3. Define cantidad por ciclo.
4. Define unidad (`día entero` o `medio día`).
5. Define periodicidad (`anual` o `semestral`).
6. Guarda.

Al guardar una política nueva o reactivada:

- **activación inicial** en una empresa sin política previa: crea saldos del ciclo actual para colaboradores activos,
- **reactivación** de una política previamente desactivada dentro del mismo ciclo: reutiliza los saldos ya existentes, sin recalcular ni volver a asignar cupo,
- **edición** de cantidad, unidad o ciclo sobre una política ya activa: si el ciclo vigente ya tiene saldos emitidos, el cambio se rechaza y deberá hacerse en el siguiente ciclo.

### Alta de colaborador

Cuando se cree un nuevo colaborador en una empresa con política activa:

- el sistema debe generar su saldo del ciclo vigente usando la política actualmente efectiva para ese ciclo,
- como los cambios estructurales de política quedan bloqueados una vez emitidos los saldos del ciclo, un alta nueva siempre hereda la misma configuración ya aplicada al resto del ciclo.

### Reingresos y reactivaciones

- Si un colaborador vuelve a estar activo dentro de la **misma empresa** y ya tiene saldo en el ciclo vigente, se reutiliza ese saldo con su remanente real.
- Si vuelve a estar activo y no tiene saldo en el ciclo vigente, se crea uno nuevo con el cupo completo del ciclo.
- Si un colaborador cambia de empresa, el saldo anterior queda histórico en la empresa origen y la empresa destino evalúa su propia política como un caso nuevo.

### Definición de colaborador activo

Para esta fase, un colaborador activo es aquel que:

- pertenece a la empresa,
- no está eliminado lógicamente,
- y tiene usuario con estado activo cuando exista relación de usuario.

### Uso de administrativo

1. RRHH abre la pestaña Asistencia del colaborador.
2. Registra un administrativo para una fecha.
3. El sistema resuelve el ciclo usando **la fecha del registro de asistencia**, no la fecha actual del servidor.
4. El sistema valida el saldo de ese ciclo.
5. Si la validación pasa, persiste asistencia + consumo.
6. El resumen del colaborador refleja el uso.

### Cambio de ciclo

Cuando el sistema detecte un nuevo ciclo:

- el saldo anterior queda vencido,
- se crea el saldo del nuevo ciclo,
- no se arrastra remanente.

La transición puede resolverse de forma lazy al primer uso/consulta del nuevo ciclo, sin exigir un job inicial.

### Desactivación de política

Si la política se desactiva:

- no se crean nuevos consumos administrativos mientras esté desactivada,
- los saldos existentes quedan históricos y visibles,
- los consumos ya registrados no se alteran,
- payroll e historial siguen leyendo esos registros normalmente.

Si luego se reactiva dentro del mismo ciclo, se retoman los saldos existentes; no se reasignan unidades.

---

## Manejo de errores y validaciones

- No permitir registrar administrativos si la empresa no tiene política activa.
- No permitir consumo si el saldo disponible es menor al valor de la unidad configurada.
- No permitir configuraciones incompletas al activar la política.
- No mezclar administrativos con justificaciones incompatibles en un mismo registro.
- Los cambios de política nunca recalculan retroactivamente saldos ya emitidos en el ciclo vigente.

### Regla obligatoria

Los cambios de política afectan **solo desde el siguiente ciclo**, salvo activación inicial en una empresa sin política previa. Eso evita recalcular saldos ya usados dentro del mismo semestre o año.

---

## Responsabilidades de servicios

Para evitar repartir la lógica entre controladores y modelos sin un contrato claro, la implementación debe separarse al menos así:

### `AdministrativeDayCycleResolver`

Responsable de:

- resolver el ciclo según **la fecha efectiva del registro** y el tipo de ciclo,
- producir `cycle_key`, `cycle_starts_on` y `cycle_ends_on`.

### `AdministrativeDayBalanceService`

Responsable de:

- crear saldo inicial del ciclo,
- obtener o crear saldo vigente,
- descontar unidades,
- revertir unidades,
- aplicar bloqueo transaccional sobre el saldo.

### `AdministrativeDayProvisioner`

Responsable de integrarse con altas, reactivaciones y cambios de empresa:

- en alta de colaborador, crea el saldo del ciclo vigente si la empresa tiene política activa,
- en reactivación dentro de la misma empresa, reutiliza el saldo existente o lo crea si no existe,
- en cambio de empresa, no migra saldo histórico: evalúa a la empresa destino como un alta nueva.

### `AdministrativeDayAttendanceService`

Responsable de:

- validar si un registro de asistencia corresponde a administrativo,
- persistir o actualizar `administrative_day_usages`,
- coordinar en transacción asistencia + usage + balance,
- manejar la reversa cuando el registro deja de ser administrativo o se elimina.

Los controladores solo orquestan request/response. `EmployeeService` o el punto actual de alta/reactivación delega la provisión en `AdministrativeDayProvisioner`. Payroll consume el contrato resultante, pero no recalcula saldos ni resuelve ciclos.

---

## Testing

Se deben cubrir al menos estos casos:

1. creación y actualización de política por empresa,
2. creación automática de saldo para colaboradores activos,
3. creación de saldo para un colaborador nuevo dentro de una empresa con política activa,
4. consumo correcto de 1 unidad o 0.5 unidades según la política,
5. bloqueo por sobreconsumo,
6. reversa del saldo al editar o eliminar un registro de asistencia,
7. inicio de nuevo ciclo sin arrastre,
8. payroll no descuenta un `administrative_day`,
9. payroll sigue descontando inasistencias, licencias y permisos sin goce.

---

## Riesgos

- Cambiar `attendance_records` sin una categoría clara puede mezclar días administrativos con permisos genéricos y volver opaco el dominio.
- Si el saldo se calcula solo desde asistencia, la lógica de reversa y validación quedará más frágil.
- Si la política modifica saldos vigentes retroactivamente, puede generar inconsistencias entre asistencia ya registrada y cupo disponible.

---

## Resultado esperado

Al finalizar esta fase, cada tenant podrá definir su política de días administrativos desde la ficha de empresa, cada colaborador tendrá saldo controlado por ciclo y RRHH podrá registrar el uso desde asistencia sin que payroll descuente ese beneficio.
