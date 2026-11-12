# Especificación de Arquitectura y Base de Datos – Remsis (SaaS Remuneraciones Chile)

## 1. Visión Arquitectónica

Remsis es un sistema SaaS Multi-Tenant y Multi-RUT de gestión de remuneraciones y recursos humanos bajo la legislación laboral chilena (Código del Trabajo, Dirección del Trabajo - LRE, Previred y Servicio de Impuestos Internos - SII).

- **Framework:** Laravel 12, PHP 8.2+ (`strict_types=1`).
- **Modularidad:** `nWidart/laravel-modules` (`Core`, `AdminPanel`, `Users`, `Companies`, `Employees`, `Payroll`, `Attendance`, `Requests`, `Documents`).
- **Aislamiento:** Jerarquía de dos niveles:
  - `Tenant` (`tenants`): Organización, grupo empresarial o estudio contable suscriptor.
  - `Company` (`companies`): Razón social (RUT empleador) perteneciente a un `Tenant`.

---

## 2. Esquema Base – Fase 1: Tenancy, Indicadores Previsionales y Catálogo LRE

### 2.1. Tabla `tenants` (`Modules/Core`)
- `id`: bigint unsigned PK
- `name`: string
- `rut`: string(20), nullable, unique
- `slug`: string, unique
- `status`: string(20) (`active`, `suspended`, `trial`)
- `settings`: json, nullable
- `timestamps()`, `softDeletes()`

### 2.2. Tabla `companies` (`Modules/Companies`)
- `tenant_id`: foreignId -> `tenants.id` (nullable on transition, indexed)
- Atributos de razón social: `name`, `razon_social`, `rut`, `giro`, `economic_activity_id`, `ccaf_id`, `mutual_id`, `gratification_system`, `weekly_hours`, `allows_overtime`, `payment_frequency`.

### 2.3. Tabla `monthly_indicators` y Subtablas (`Modules/Core`)
Tabla global (sin `tenant_id`) que almacena la fotografía previsional y tributaria de cada período mensual (`YYYY-MM`):
- `period`: string(7) unique (`YYYY-MM`)
- `uf_value`, `uf_previous_month`, `utm_value`, `uta_value`, `ipc_value`
- `imm_value`, `imm_under_18_over_65`, `imm_non_remunerational`
- `cap_afp_uf` (84.3 UF), `cap_ips_uf` (60 UF), `cap_afc_uf` (126.6 UF)
- `sis_rate`, `afc_indefinite_employee_rate` (0.6%), `afc_indefinite_employer_rate` (2.4%), `afc_fixed_employer_rate` (3.0%), `mutual_base_rate` (0.90%), `sanna_rate` (0.03%)
- `legal_weekly_hours` (42h en 2026, 40h en 2028 - Ley 21.561)
- Tablas hijas y caché JSON:
  - `monthly_afp_rates` / `afp_rates`: tasas por AFP (10% obligatorio + comisión).
  - `monthly_iut_brackets` / `iut_brackets`: 8 tramos de Impuesto Único de Segunda Categoría en UTM.
  - `monthly_family_allowance_brackets` / `family_allowance_brackets`: tramos A, B, C y D de Asignación Familiar.

### 2.4. Tabla `lre_concepts_dictionary` (`Modules/Core`)
Catálogo normativo de la Dirección del Trabajo para el Libro de Remuneraciones Electrónico (LRE):
- `code`: string(10) unique (`1101`, `1102`, ..., `2101`, ..., `3101`, ..., `4101`)
- `name`: string(150)
- `classification`: enum (`haber_imponible`, `haber_no_imponible`, `descuento_legal`, `descuento_judicial`, `descuento_voluntario`, `aporte_empleador`)
- `legal_article`: string(60)
- Banderas: `is_taxable`, `is_taxable_afc`, `is_tributable`, `is_gratification_base`, `is_overtime_base`, `is_severance_base`
- `previred_code`: string(10)

---

## 3. Roadmap de Fases

- **Fase 1:** Fundaciones Multi-Tenant (`Tenant`, `Company`, `BelongsToTenant`), Indicadores Previsionales Mensuales (`monthly_indicators`), Diccionario LRE (`lre_concepts_dictionary`) y Sincronización API (`mindicador.cl` / CMF).
- **Fase 2:** Fichas de Colaboradores, Contratos (`employment_contracts`) y Conceptos de Nómina (`payroll_items`) vinculados a `lre_concepts_dictionary`.
- **Fase 3:** Motor de Cálculo de Liquidaciones (`PayrollCalculationService`) leyendo exclusivamente desde `monthly_indicators` con redondeo CLP a 0 decimales.
- **Fase 4:** Exportadores Oficiales de Cierre Mensual (Archivo Previred 105 campos y Libro de Remuneraciones Electrónico LRE DT).

