# Remsis – Copilot Instructions

Remsis is a modular payroll management system for Chile, built with Laravel 12 and `nwidart/laravel-modules`. All business logic is organized into self-contained modules under `Modules/`.

## Build & Test Commands

```bash
# Start all dev services (server, queue, logs, Vite) concurrently
composer dev

# Run all tests
composer test

# Run a single test file
php artisan test --filter TestClassName
php artisan test tests/Feature/ExampleTest.php

# Frontend assets
npm run dev      # Vite dev server
npm run build    # Production build
```

Tests use an in-memory SQLite database. No `.env` changes needed for testing.

## Architecture

### Module System

All features live in `Modules/{ModuleName}/`. Active modules are listed in `modules_statuses.json`. Vite loads module assets dynamically via `vite-module-loader.js`.

Each module follows this structure:
```
Modules/{Name}/
  app/
    Http/Controllers/
    Models/
    Services/          # Business logic (calculations, workflows)
    Providers/         # ServiceProvider, RouteServiceProvider, EventServiceProvider
  database/
    migrations/
    seeders/
  resources/views/
  routes/
    web.php
    api.php
  module.json
```

### Active Modules

| Module | Purpose |
|---|---|
| **Core** | Central system configuration and initialization |
| **AdminPanel** | Dashboard, auth, legal parameters (AFP, Isapre, CCAF, Banks) |
| **Users** | User management with Spatie roles/permissions (`super-admin`, `admin`, `payroll-manager`, `employee`) |
| **Employees** | Employee profiles: RUT, salary, AFP/Isapre/CCAF selections, bank info, 25+ fields |
| **Companies** | Company profiles and core company/accounting data such as bank and cost center information |
| **Payroll** | Core payroll: periods, calculation, line items, deductions |

### Key Models

- `App\Models\User` – Auth model with `company_id`, `user_type`, Spatie permissions
- `Modules\Employees\Models\Employee` – `$appends = ['full_name', 'completion_percentage']`, SoftDeletes
- `Modules\Payroll\Models\PayrollPeriod` – Lifecycle: `STATUS_DRAFT → STATUS_OPEN → STATUS_CLOSED → STATUS_PAID`
- `Modules\Payroll\Models\Payroll` – Canonical payroll record; wizard, calculation and history all persist on `payrolls`
- `Modules\Companies\Models\Company` – `gratification_system`, `weekly_hours`, `allows_overtime`
- `Modules\AdminPanel\Models\LegalParameter` – Configurable legal thresholds (UF, UTM, AFP rates)

### Service Layer

Business logic lives in `Modules/{Name}/app/Services/`. Key services:
- `PayrollCalculationService` – Calculates imponible, gratification (Art. 50 = 25% imponible, capped at 4.75×UF), AFP (10%), Isapre (7%), Cesantía (0.6%), Impuesto Único (progressive)
- `PayrollService` – Manages payroll workflow and period transitions

## Key Conventions

### Routing

- Root routes: `routes/web.php`, `routes/auth.php`
- Module routes: `Modules/{Name}/routes/web.php` and `api.php`
- Middleware used: `auth`, `role:super-admin|admin`, `blockEmployeeOnAdmin`
- Company-scoped flows are mostly nested under `companies.*` routes in `Modules/Companies/routes/web.php`.
- Payroll periods currently use nested route names like `companies.payroll-periods.*`.

### Shared company UI shell

- Company detail screens use the shared sidebar layout in `resources/views/layouts/company.blade.php`.
- Blade pages mounted in that shell typically use `<x-layouts.company :company="$company" activeTab="...">`.
- Keep `activeTab` values aligned with sidebar states when adding new company-scoped pages.
- The company edit screen uses `companies.edit` with query parameters such as `section=company-data|remunerations` and `tab=...` to split the UI without changing the underlying update route.

### Models

- Use `$fillable`, `$casts`, `$appends`
- Status constants as class constants (e.g., `STATUS_DRAFT`, `STATUS_OPEN`)
- Traits: `HasFactory`, `SoftDeletes`
- Business logic methods on the model when tightly coupled to state
- Shared-db multi-tenancy foundation uses `App\Support\Tenancy\TenantContext`, `App\Http\Middleware\ResolveTenantContext`, and the `App\Models\Concerns\BelongsToTenant` trait for company-owned models.
- `super-admin` bypasses tenant scoping; regular authenticated users resolve the active tenant from `company_id`.
- `App\Models\User` is a compatibility alias; the effective auth model remains `Modules\Users\Models\User`.
- Admin-facing company/user APIs should stay tenant-limited for non-`super-admin` users, even when routes are shared with admin screens.

### Adding a new feature

Always inspect the relevant module first. Match existing namespace patterns: `Modules\{Name}\Http\Controllers`, `Modules\{Name}\Models`, etc. When adding legal parameters or rates, store them in the `legal_parameters` table—not as hardcoded constants.

### Domain boundaries and navigation

- Prefer separating company accounting data from payroll/remuneration workflows.
- Company-level data such as bank details and cost centers should live under a company/accounting area rather than being treated as payroll records.
- For admin navigation, prefer a grouped company sidebar structure like `Contabilidad > Datos empresa` and `Contabilidad > Remuneraciones`.
- Be aware that the current code still couples several payroll-related flows under company routes such as `companies.employees.*` and `companies.employees.payroll.*`; treat that as current state, not the preferred long-term boundary.
- When refining company settings UI, prefer reusing `companies.edit` in sectioned modes before introducing new controllers or layouts.
- If refactoring this area, use the existing company sidebar as the integration point rather than introducing a separate layout for payroll screens inside a company.

## Frontend Stack

- **Tailwind CSS 3** + **UnoCSS** (Wind3 preset) – both configured; UnoCSS scans Blade/PHP/module files
- **Alpine.js 3** – reactivity for forms, modals, dropdowns (no Vue/React)
- **Vite 6** with `laravel-vite-plugin`

### UI Design System ("Aesthetic Minimal Light")

Defined in `.agent/skills/tailwind-aesthetic.md`. Key rules:
- Page background: `bg-slate-50`; surfaces: `bg-white`
- Borders: `border-slate-200`; text primary: `text-slate-900`; secondary: `text-slate-500`
- Cards: `bg-white rounded-xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow`
- Primary button: `bg-slate-900 text-white rounded-lg hover:bg-slate-800`
- Headings: `font-bold text-slate-900 tracking-tight`

## Chilean Payroll Domain

**Haberes (earnings):** Sueldo Base, Horas Extras, Gratificación (Art. 47 / Art. 50 / Convencional / Sin Gratificación)

**Descuentos legales:** AFP (~10%), Isapre (7%), Cesantía (0.6%), Impuesto Único a la Renta (progressive)

**Descuentos voluntarios:** Anticipos, créditos, sindicato, APV

**Gratification systems** (configured per company in `companies.gratification_system`):
- `art_47` – Manual
- `art_50` – 25% of imponible, capped at 4.75 × UF/month
- `convencional` – Per agreement
- `sin_gratificacion` – None

All legal thresholds (UF, UTM, AFP percentages) must come from `LegalParameter` records, not hardcoded values.

## Agent Behavior Notes

(From `agent.md`) Always inspect existing module code before proposing new models, migrations, controllers, or views. Adapt to the existing style: namespaces, service patterns, Blade+Alpine conventions. For large requests, proceed incrementally: model → service → controller → view. When a migration could break existing data, warn explicitly and suggest a safe migration path. Respond in Spanish when working in this project context.
