# Hito 1 · Línea base del cierre mensual

Revisión del 13 de noviembre de 2026. Alcance del primer bloque: pruebas HTTP de Laravel con SQLite en memoria e inspección de rutas, controladores, servicios y vistas. La navegación en un navegador con seis perfiles y el cierre integral con dos empresas y dos meses quedan pendientes.

| Paso | Perfil | Qué funciona | Evidencia en el repositorio | Brecha | Próxima tarea |
|---|---|---|---|---|---|
| Entrar y seleccionar empresa | Super-admin, contador externo, operador y colaborador interno | Las pruebas cubren acceso global, aislamiento, delegación y portal personal. | tests/Feature/Tenancy/OutsourcingAndPermissionsTest.php:28 y ResolveTenantContextSecurityTest.php:97 | Parcial. Falta recorrer los menús con los seis perfiles, incluyendo dueño aprobador y calculista con permisos distintos. | Preparar cuentas con ambas empresas y comprobar cada acceso también por URL. |
| Preparar trabajador, contrato y novedades | RR. HH. | La prueba de alta y edición verifica sincronización de contrato. El cálculo lee asistencia del período. | tests/Feature/Employees/EmploymentContractAndLreItemsTest.php:31 y Modules/Payroll/app/Services/PayrollCalculationService.php:56 | Parcial. El cálculo selecciona la dotación por estado activo actual. Queda por contrastarla con los vínculos vigentes en cada mes. | Probar un ingreso y un término entre dos períodos, una ausencia y horas extra con montos esperados. |
| Calcular y recalcular | Encargado de remuneraciones | La ruta genera la línea y admite ajustes. La prueba existente comprueba persistencia y parte de la aritmética. | tests/Feature/Payroll/PayrollConsolidationFlowTest.php:17 y Modules/Payroll/app/Services/PayrollCalculationService.php:105 | Riesgo reproducido. Una segunda ejecución descuenta otra vez y avanza la cuota del mismo mes. Además hay valores de respaldo para UF, UTM e IMM y el servicio escribe impuesto único en cero. | Asegurar repetibilidad y validar parámetros y casos de referencia por período antes de aceptar los montos. |
| Revisar, aprobar y proteger | Dueño o administrador con permiso de aprobación | La política separa cálculo y aprobación. La ruta admite el cierre. | Modules/Companies/app/Policies/CompanyPolicy.php y Modules/Payroll/app/Http/Controllers/PayrollPeriodController.php:153 | Riesgo reproducido. La ruta de cálculo vuelve a abrir de hecho un período cerrado. La transición revisada tampoco exige conciliación de la dotación. | Proteger recálculo y edición desde el servidor, incluidas operaciones simultáneas con el cierre. Verificar responsable, pendientes y totales antes de aprobar. |
| Publicar y consultar liquidación | Encargado y colaborador | Existe portal personal con control de acceso. | Modules/Employees/resources/views/profile/show.blade.php:543 y Modules/Companies/app/Http/Controllers/CompanyEmployeeController.php:406 | Implementado el 15-11-2026. La liquidación se genera en PDF (Modules/Payroll/app/Services/PayslipService.php) desde la ficha administrativa y desde el portal; el colaborador sólo ve las propias y sólo de períodos cerrados o pagados. Cubierto por tests/Feature/Payroll/PayslipTest.php. | Publicar la versión aprobada, consultar únicamente la propia y comprobar que un borrador y una liquidación ajena sean inaccesibles. |
| Cuadrar y explicar totales | Contador | La vista muestra haberes, descuentos y líquido por persona y ofrece filtro por centro de costo. | Modules/Payroll/resources/views/periods/wizard.blade.php:140 | Falta demostrar una cuadratura reproducible del período aprobado y la comparación entre meses. | Conciliar cifras esperadas por persona y centro con el total de la empresa y verificar que el otro cliente quede excluido. |

## Reproducción del primer defecto

Con el escenario de la prueba de consolidación, usar un sueldo base de $500.000 y agregar un crédito activo de cuatro cuotas de $1.000, inicialmente en cuota cero. Por HTTP, calcular con el crédito, cerrar mediante la ruta de estado y volver a enviar el cálculo del mismo período. El primer líquido fue $411.000 con una cuota contabilizada. El segundo fue $410.000 con dos cuotas contabilizadas y el período volvió de cerrado a calculado. Se ejecutó una prueba temporal con once aserciones en memoria. El servicio toma el descuento previamente consolidado como entrada, vuelve a sumar los ítems y actualiza el contador al calcular (PayrollCalculationService.php, líneas 105, 160, 201 y 211).

Criterios para corregirlo: en un período editable, repetir el cálculo con las mismas entradas conserva los importes y produce como máximo una cuota por trabajador, crédito y período. En un período cerrado, intentar calcular o editar por HTTP mantiene el estado, los importes y las cuotas. Una operación concurrente con el cierre debe respetar las mismas garantías.

## Pruebas ejecutadas

Con APP_ENV=testing, DB_CONNECTION=sqlite y DB_DATABASE=:memory:, ejecutar desde la raíz:

    vendor/bin/phpunit --do-not-cache-result
    vendor/bin/phpunit --do-not-cache-result Modules/Payroll/tests Modules/Companies/tests

La suite configurada pasó 107 pruebas y 449 aserciones. La ejecución adicional incluyó 15 pruebas y 51 aserciones: 13 pasaron y dos de EmployeeSearchTest terminaron con error al preparar Company::factory(). El resultado de la búsqueda queda sin verificar por esos casos. PHPUnit informó 13 deprecaciones en esa ejecución. Entorno observado: PHPUnit 11.5.55 y PHP 8.5.4; composer.json declara PHP ^8.5.12. Falta repetir en una versión compatible antes de tomar los resultados como referencia de entrega.

## Actualización del 15 de noviembre de 2026

Los dos riesgos reproducidos quedaron corregidos y cubiertos por [PayrollEngineTest](../tests/Feature/Payroll/PayrollEngineTest.php):

- **Recálculo repetible.** El descuento manual (`otros_descuentos`) se guarda separado de los descuentos por ítems y créditos (`descuentos_items_amount`, `creditos_amount`). Cada cuota cobrada queda vinculada a la liquidación en `payroll_details`, de modo que recalcular reutiliza la misma cuota.
- **Período protegido.** Calcular o editar una línea de un período cerrado o pagado se rechaza desde el servidor sin modificar estado, importes ni cuotas. El cálculo, la edición y el cambio de estado bloquean la fila del período para ejecutarse uno tras otro.
- **Parámetros.** El cálculo exige el indicador mensual del período y se niega a calcular si falta; ya no hay valores de respaldo para UF, UTM o IMM. El impuesto único se calcula por tramos.
- **Dotación.** Se selecciona por vínculo vigente en el mes (ingreso y término), con días proporcionales.

- **Valores legales.** Rentas mínimas, topes, tasas, comisiones de AFP y asignación familiar viven en la tabla de vigencias `Modules/Core/config/legal_indicators.php`; el sincronizador copia al indicador la entrada que rige en cada período. Los topes se convierten con la UF del último día del mes de la remuneración.
- **Empleador y límites.** Se registran el aporte del empleador de la Ley 21.735, la cesantía reducida desde los 11 años, la parte del 7% que corresponde a la CCAF y el exceso de descuentos voluntarios sobre el 15%.

La suite configurada pasa 147 pruebas y 788 aserciones.

## Actualización del 16 de noviembre de 2026: Recursos Humanos (primera etapa)

- **Historial contractual:** `employment_contracts` guarda versiones con vigencia (`valid_from`, `valid_until`). Un anexo cierra la versión anterior y abre otra; la nómina liquida cada mes con la versión vigente a su cierre (`MonthlyPayrollEarnings`). Un anexo a mitad de mes aplica a todo el mes: se recomienda vigencia desde el día 1.
- **Vacaciones:** saldo calculado (no almacenado) en `VacationService`: días por año parametrizados en la empresa (Art. 67), feriado progresivo (Art. 68), días hábiles sin sábados, domingos ni festivos (Art. 69) y aviso de acumulación sobre dos períodos (Art. 70). Los festivos están en `Modules/Core/config/holidays.php` (2026 y 2027) y deben revisarse contra el calendario oficial al cargar cada año.
- **Portal:** el colaborador solicita vacaciones y ve su saldo. Las rutas `employee/*` fijan como contexto la empresa de su propio vínculo (`UseOwnCompanyContext`); antes, un colaborador sin rol administrativo no podía solicitar días administrativos.
- Cubierto por `tests/Feature/Employees/HumanResourcesTest.php`.

## Actualización del 16 de noviembre de 2026: Recursos Humanos (licencias médicas)

- **Registro:** `medical_leaves` guarda cada licencia con folio, tipo (los siete del formulario), días corridos, resolución (en trámite, autorizada, reducida, rechazada, anulada) y entidad pagadora, que se deduce del origen y de la afiliación: mutualidad o ISL en Ley 16.744, Isapre, caja de compensación de la empresa o COMPIN. Al registrarla, `MedicalLeaveService` marca cada día como licencia en la asistencia, por lo que la nómina no los paga.
- **Subsidio estimado:** `MedicalLeaveSubsidyCalculator` promedia la remuneración neta (imponible con tope menos cotizaciones legales e impuesto) y los subsidios de los tres meses anteriores al inicio (Art. 8 DFL 44); el mes sin liquidación se completa con el neto del contrato. Aplica el mínimo diario del Art. 17 y la carencia del Art. 14 en enfermedad común y patología del embarazo: hasta 10 días se paga desde el cuarto; las licencias continuas se suman. Es una estimación para conciliar con la entidad pagadora.
- **Nómina:** `payrolls.medical_leave_days` guarda los días de licencia reconocida (base 30). Por ellos el empleador sigue enterando el SIS y su parte del seguro de cesantía, sobre la renta imponible del mes anterior al inicio de la licencia o, a falta de ella, la del contrato.
- **Fuera de alcance por ahora:** reposo parcial (media jornada), separación de remuneraciones ocasionales en la base (Art. 10), tope especial de los subsidios maternales, aportes del empleador de la Ley 21.735 por los días de licencia, registro del subsidio efectivamente pagado y licencias sobre períodos ya cerrados (hay que reabrir el período).
- **Pendiente de RRHH:** término de contrato y finiquito (incluye feriado proporcional, Art. 73), e intranet.
- Cubierto por `tests/Feature/Employees/MedicalLeaveTest.php`.
