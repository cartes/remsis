# Spec: Arquitectura Relacional de Ítems para Colaboradores

**Fecha**: 2026-03-27  
**Módulos afectados**: Payroll, Employees, Companies  
**Estado**: Aprobado

---

## Problema

Actualmente, los haberes variables de un colaborador (colación, movilización, bonos, descuentos, créditos) están almacenados como columnas fijas en la tabla `employees`. Esto impide gestionar conceptos dinámicos por colaborador y hace imposible modelar créditos en cuotas u otros haberes recurrentes/variables sin agregar más columnas.

## Solución

Introducir un modelo relacional con:
- **Catálogo de ítems** por empresa (`items`)
- **Asignaciones por colaborador** (`employee_items`)

Y migrar los datos existentes de `meal_allowance` y `mobility_allowance` a esta nueva estructura.

---

## Fase 1: Base de Datos

### Tabla `items` (catálogo por empresa)

```sql
id                    bigint PK
company_id            FK → companies.id (nullOnDelete)
name                  string(150)         -- "Bono Producción", "Colación"
code                  string(50) nullable -- "BON_PROD", "COLACION"
type                  enum(haber_imponible, haber_no_imponible, descuento_legal, descuento_varios, credito)
is_taxable            boolean default true
is_gratification_base boolean default false
created_at / updated_at / deleted_at (softDeletes)
```

**Índice único**: `(company_id, code)` — evita duplicados por empresa.

### Tabla `employee_items` (asignaciones)

```sql
id                  bigint PK
employee_id         FK → employees.id (cascadeOnDelete)
item_id             FK → items.id (cascadeOnDelete)
amount              decimal(12,2)
unit                enum(CLP, UF, UTM, PERCENTAGE)  default CLP
periodicity         enum(fixed, variable)            default fixed
total_installments  integer nullable  -- solo para créditos
current_installment integer nullable  -- cuota actual (auto-incrementable)
is_active           boolean default true
notes               text nullable
created_at / updated_at
```

### Migración de datos (incluida en la migración)

Al crear las nuevas tablas, la migración incluye un bloque `DB::statement` / seeder que:
1. Por cada empresa que tenga empleados con `meal_allowance > 0`, crea ítem "Colación" (haber_no_imponible) en el catálogo y genera rows en `employee_items`.
2. Por cada empresa que tenga empleados con `mobility_allowance > 0`, ídem con "Movilización".
3. Elimina las columnas `meal_allowance` y `mobility_allowance` de `employees`.

---

## Fase 2: Modelos

### `Modules\Payroll\Models\Item`

- `belongsTo(Company::class)`
- `hasMany(EmployeeItem::class)`
- `$fillable`: name, code, type, is_taxable, is_gratification_base, company_id
- Scopes: `haberesImponibles()`, `haberesNoImponibles()`, `descuentos()`, `creditos()`

### `Modules\Payroll\Models\EmployeeItem`

- `belongsTo(Employee::class)`
- `belongsTo(Item::class)`
- `$fillable`: employee_id, item_id, amount, unit, periodicity, total_installments, current_installment, is_active, notes
- Método `resolvedAmountCLP(LegalParameter $uf, LegalParameter $utm, int $base = 0): int` — convierte según `unit`:
  - CLP → retorna `amount` directo
  - UF → `(int) round($amount * $uf->value)`
  - UTM → `(int) round($amount * $utm->value)`
  - PERCENTAGE → `(int) round($base * $amount / 100)` — requiere `$base` (imponible del empleado); nunca llamar directamente en `sum()`, se resuelve en el servicio pasando la base

### `Modules\Employees\Models\Employee` — cambios

- Agrega relación usando FQCN string para evitar dependencia circular de namespace:
  ```php
  public function employeeItems(): HasMany
  {
      return $this->hasMany('Modules\Payroll\Models\EmployeeItem', 'employee_id');
  }
  ```
  > **Nota**: `Modules\Employees` depende de `Modules\Payroll` solo por FQCN string, no por import. Esta dependencia unidireccional es intencional y documentada.
- Elimina `meal_allowance`, `mobility_allowance` de `$fillable` y `$casts`

---

## Fase 3: PayrollCalculationService

Reemplazar lectura de columnas planas por consulta a `employee_items`:

```php
// Cargar ítems activos del empleado con su item de catálogo
$activeItems = $employee->employeeItems()
    ->with('item')
    ->where('is_active', true)
    ->get();

// Base imponible para calcular items tipo PERCENTAGE
$baseImponible = (int) ($employee->salary ?? 0);

// Clasificar por tipo
$haberesImponibles   = $activeItems->filter(fn($ei) => $ei->item->type === 'haber_imponible');
$haberesNoImponibles = $activeItems->filter(fn($ei) => $ei->item->type === 'haber_no_imponible');
$descuentosVarios    = $activeItems->filter(fn($ei) => $ei->item->type === 'descuento_varios');
$creditos            = $activeItems->filter(fn($ei) => $ei->item->type === 'credito');

// Resolver montos con UF/UTM del período (PERCENTAGE recibe $baseImponible)
$totalHaberesNoImponibles = $haberesNoImponibles->sum(fn($ei) => $ei->resolvedAmountCLP($uf, $utm, $baseImponible));
$totalHaberesImponibles   = $haberesImponibles->sum(fn($ei) => $ei->resolvedAmountCLP($uf, $utm, $baseImponible));

// Haberes imponibles que cuentan para base de gratificación (is_gratification_base = true)
$baseGratificacion = $baseImponible + $haberesImponibles
    ->filter(fn($ei) => $ei->item->is_gratification_base)
    ->sum(fn($ei) => $ei->resolvedAmountCLP($uf, $utm, $baseImponible));
```

Los `haber_imponible` se suman al imponible antes de calcular AFP/salud.  
Los `haber_no_imponible` se agregan al líquido sin afectar cotizaciones.  
Los `descuento_varios` se descuentan del líquido final.  
Los `credito` se descuentan del líquido final e **incrementan `current_installment`** después de procesar:

```php
// Después de cerrar el payroll, incrementar cuotas de créditos activos
foreach ($creditos as $ei) {
    $ei->increment('current_installment');
    // Desactivar si se completó
    if ($ei->current_installment >= $ei->total_installments) {
        $ei->update(['is_active' => false]);
    }
}
```

El Payroll resultante guarda el detalle en JSON (`items_detail`) para histórico.

---

## Fase 4: Vistas y Controladores (Companies module — vista admin)

### Vista: ficha de edición del colaborador (`edit.blade.php`)

**Ruta**: `GET /companies/{company}/employees/{employee}/edit`  
**Controlador**: `CompanyEmployeeController@edit`

Usa Alpine.js con `x-data="{ tab: 'personal' }"`. 5 tabs:

| Tab | Campos |
|-----|--------|
| Personal | first_name, last_name, rut, email, birth_date, gender, nationality, phone, address |
| Laboral | position, hire_date, contract_type, work_schedule_type, part_time_hours, cost_center_id |
| Previsional | afp_id, health_system, isapre_id, health_contribution, ccaf_id, apv_amount |
| Remuneraciones | salary, salary_type, payment_method, bank_id, bank_account_type, bank_account_number |
| Ítems | Sub-tabs: Haberes / Descuentos / Créditos — CRUD inline con Alpine.js |

Cada tab tiene su propio `<form>` con `method POST` + `@method('PATCH')` apuntando a `update()`. Se guarda independientemente.

### Tab Ítems — flujo UX

1. Botón "Agregar ítem" abre un mini-panel inline (no modal separado)
2. Select de catálogo de la empresa filtrado por tipo (haber/descuento/crédito)
3. Campos: monto, unidad (CLP/UF), periodicidad (fijo/variable)
4. Si type === 'credito': campos adicionales total_installments
5. Al guardar: POST a `/companies/{company}/employees/{employee}/items`

### Rutas nuevas (bajo el resource existente)

```php
// Edición de colaborador
Route::get('{employee}/edit', [CompanyEmployeeController::class, 'edit'])
    ->name('companies.employees.edit');
Route::patch('{employee}', [CompanyEmployeeController::class, 'update'])
    ->name('companies.employees.update');

// CRUD de employee_items
Route::post('{employee}/items', [CompanyEmployeeController::class, 'storeItem'])
    ->name('companies.employees.items.store');
Route::patch('{employee}/items/{employeeItem}', [CompanyEmployeeController::class, 'updateItem'])
    ->name('companies.employees.items.update');
Route::delete('{employee}/items/{employeeItem}', [CompanyEmployeeController::class, 'destroyItem'])
    ->name('companies.employees.items.destroy');

// Catálogo de ítems de la empresa (gestión admin)
Route::get('items-catalog', [CompanyItemsController::class, 'index'])
    ->name('companies.items.index');
Route::post('items-catalog', [CompanyItemsController::class, 'store'])
    ->name('companies.items.store');
Route::patch('items-catalog/{item}', [CompanyItemsController::class, 'update'])
    ->name('companies.items.update');
Route::delete('items-catalog/{item}', [CompanyItemsController::class, 'destroy'])
    ->name('companies.items.destroy');
```

> El catálogo de ítems se gestiona desde la ficha de la empresa (no del colaborador). Inicialmente se crean ítems "Colación" y "Movilización" vía migración de datos. El gestor de nómina puede agregar ítems propios desde la UI de la empresa.

### CompanyEmployeeController — métodos nuevos/modificados

- `edit($company, $employee)` — carga employee + items eager-loaded + catálogo de la empresa
- `update($company, $employee)` — procesa secciones (personal/laboral/previsional/remuneraciones) por separado según `section` input hidden
- `storeItem($company, $employee)` — crea EmployeeItem
- `updateItem($company, $employee, $employeeItem)` — actualiza monto/periodicidad
- `destroyItem($company, $employee, $employeeItem)` — elimina (soft o hard delete)

### CompanyItemsController (nuevo — catálogo de ítems)

- `index($company)` — lista ítems del catálogo de la empresa
- `store($company)` — crea ítem en catálogo
- `update($company, $item)` — edita ítem del catálogo
- `destroy($company, $item)` — elimina ítem (solo si no tiene employee_items activos)

---

## Migración de `payrolls` table

La tabla `payrolls` también tiene columnas `meal_allowance` y `mobility_allowance` (líneas 157–158 de PayrollCalculationService). Estas son columnas **históricas** que deben **mantenerse** en la tabla `payrolls` para preservar el histórico de liquidaciones ya procesadas. Se seguirán poblando usando el valor resuelto desde `employee_items`.

En PayrollCalculationService, al escribir al Payroll:
```php
'meal_allowance'      => $haberesNoImponibles->where('item.code', 'COLACION')->sum(...),
'mobility_allowance'  => $haberesNoImponibles->where('item.code', 'MOVILIZACION')->sum(...),
// + nuevo campo:
'items_detail'        => json_encode($activeItems->map(...)), // snapshot completo
```

Esto mantiene compatibilidad con vistas de liquidaciones que muestren esas columnas por nombre.

---

## Orden de implementación

1. Migración: crear `items` + `employee_items` + migrar datos + eliminar columnas
2. Modelos: `Item`, `EmployeeItem`, actualizar `Employee`
3. Actualizar `PayrollCalculationService`
4. Nuevas rutas en `Modules/Companies/routes/web.php`
5. `CompanyEmployeeController`: agregar `edit()`, `update()`, `storeItem()`, `updateItem()`, `destroyItem()`
6. Vista `edit.blade.php` con 5 tabs (Alpine.js + Tailwind)
7. Tests: verificar que cálculo de nómina sigue correcto con nueva estructura

---

## Riesgos y mitigaciones

| Riesgo | Mitigación |
|--------|-----------|
| Datos existentes en meal_allowance/mobility_allowance se pierden | Script de migración de datos incluido en la misma migration |
| PayrollCalculationService falla si no hay employee_items | `->get()` en colección vacía retorna 0 — sin error |
| Performance: N+1 en cálculo de nómina | Usar `with('employeeItems.item')` al cargar employees para el período |
| Columnas eliminadas de employees rompen código antiguo | Buscar todas las referencias a `meal_allowance`/`mobility_allowance` y limpiarlas |
| Columnas históricas en payrolls se rompen | Mantener columnas en payrolls, poblarlas desde items_detail en el servicio |
| PERCENTAGE sin base en sum() retorna null | resolvedAmountCLP() requiere $base, service lo pasa explícitamente — no usar en sum() anónimo |
| Créditos nunca se completan | PayrollCalculationService incrementa current_installment y desactiva al llegar al total |
| Catálogo vacío al crear employee_items | Migración de datos crea ítems base (Colación, Movilización) por empresa; admin puede agregar más |
| periodicity=variable sin flujo de activación | Para V1, los ítems variable se comportan como fixed (siempre activos). Flujo de activación por período = fase posterior. |
