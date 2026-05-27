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
- `cycle_start_month`
- `expires_at_cycle_end` (`true` en esta fase)

La política vive en el contexto de empresa y se administra desde la ficha `companies.edit`, dentro de la sección **Remuneraciones**.

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
cycle_start_month       tinyint
expires_at_cycle_end    boolean
created_at
updated_at
```

Reglas:

- un solo registro activo por empresa,
- `days_per_cycle` admite decimal para soportar cupos equivalentes cuando la unidad sea medio día,
- `cycle_start_month` permite resolver el inicio real del ciclo sin hardcodear enero/julio en la lógica.

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

- índice único por `employee_id + cycle_key`,
- `available_units` puede mantenerse persistido por claridad operativa, o derivarse en mutator si se quiere reducir duplicación,
- `assigned_units` debe reflejar exactamente el cupo vigente del ciclo, no un cálculo dinámico posterior.

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

---

## Cambios en asistencia

La pestaña actual `tab_asistencia` ya resuelve ausencias y justificaciones. El diseño propuesto reutiliza ese flujo.

### UI

En `Companies > Colaborador > Asistencia`:

- agregar un nuevo estado visible: **Día administrativo**, o
- mantener `ausente` como estado técnico y agregar una justificación explícita `administrative_day`.

La recomendación es **mantener el patrón actual de ausencias justificadas y agregar una justificación explícita `administrative_day`**, porque minimiza ruptura visual y aprovecha la lógica ya existente de motivos.

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

- se crea o sincroniza el saldo del ciclo actual para colaboradores activos de la empresa.

### Alta de colaborador

Cuando se cree un nuevo colaborador en una empresa con política activa:

- el sistema debe generar su saldo del ciclo vigente usando la política actual.

### Uso de administrativo

1. RRHH abre la pestaña Asistencia del colaborador.
2. Registra un administrativo para una fecha.
3. El sistema valida el saldo.
4. Si la validación pasa, persiste asistencia + consumo.
5. El resumen del colaborador refleja el uso.

### Cambio de ciclo

Cuando el sistema detecte un nuevo ciclo:

- el saldo anterior queda vencido,
- se crea el saldo del nuevo ciclo,
- no se arrastra remanente.

La transición puede resolverse de forma lazy al primer uso/consulta del nuevo ciclo, sin exigir un job inicial.

---

## Manejo de errores y validaciones

- No permitir registrar administrativos si la empresa no tiene política activa.
- No permitir consumo si el saldo disponible es menor al valor de la unidad configurada.
- No permitir configuraciones incompletas al activar la política.
- No mezclar administrativos con justificaciones incompatibles en un mismo registro.
- Si la política cambia durante un ciclo, la implementación debe definir explícitamente si afecta solo ciclos futuros o también saldos vigentes.

### Regla recomendada

Los cambios de política afectan **desde el siguiente ciclo**, salvo activación inicial en una empresa sin política previa. Eso evita recalcular saldos ya usados dentro del mismo semestre o año.

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
