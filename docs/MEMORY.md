# Memoria Persistente del Proyecto (`personal` — MIK Software Control)

## Stack Tecnológico
- **Backend**: PHP ^8.3, Laravel ^13.8 (`laravel/framework`), `barryvdh/laravel-dompdf` ^3.1 para estados de cuenta en PDF.
- **Frontend**: Blade templates (`resources/views/`), Bootstrap Icons CDN, hoja de estilos principal `public/css/auth-mik.css`, JavaScript vanilla con elementos nativos `<dialog>` y Fetch API (AJAX para búsqueda en vivo, control remoto de módulos y gestión de contratos PDF).
- **Almacenamiento**: Disco privado `local` (`storage/app/private`) para archivos sensibles como contratos PDF por licencia; servidos mediante controlador autenticado con `response()->file(...)`.

## Arquitectura y Módulos
- **Multi-tenancy SaaS**: Aislamiento por usuario mediante el trait `App\Models\Traits\BelongsToUser` (`user_id` global scope + asignación automática al crear). Roles `admin` / `superadmin` gestionados en `Admin\UserController`.
- **Módulos principales** (`routes/web.php`):
  - `DashboardController` (`/dashboard`): métricas financieras del mes y saldos pendientes.
  - `ClientController` (`/clients`): clientes (`persona`/`empresa`, modelo `cliente_final`/`revendedor`).
  - `LicenseController` (`/licenses`): licencias de software, control remoto de sistema y módulos (`/system-status`, `/system-toggle`, `/system-modules`, `/system-modules/toggle`), y gestión de contratos PDF (`/licenses/{license}/contract`).
  - `DevelopmentController` (`/developments`): mejoras, proyectos y contratos de soporte.
  - `SaleController` (`/sales`): ventas de equipos y otros.
  - `PaymentController` (`/payments`): registro de ingresos hacia `BankAccount` o canje hacia `Credit`, con actualización automática de fechas de vencimiento de licencias.
  - `LoanController` (`/loans`), `CreditController` (`/credits`), `BankAccountController` (`/bank-accounts`), `IncomeController` (`/incomes`), `ExpenseController` (`/expenses`), `ReportController` (`/reports/estado-cuenta`), `DatabaseImportController` (`/db-import`).

## Modelos y Base de Datos Clave
- **`License` (`licenses`)**:
  - Campos principales: `user_id`, `client_id`, `url`, `block_token` (nullable), `status` (`activa`, `suspendida`, `vencida`), `billing_cycle` (`mensual`, `trimestral`, `semestral`, `anual`, `anual_total`), `monthly_fee`, `setup_fee`, `next_billing_date`, `next_setup_billing_date`, `is_free`, `contract_path`, `contract_original_name`, `contract_uploaded_at`.
  - Helpers de cálculo centralizados en `App\Models\License`: `isAnnualTotal()`, `hasContract()`, `cycle_months`, `service_amount`, `annual_fee`, `billing_breakdown`.
- **`Payment` (`payments`)**:
  - Vinculado opcionalmente a `development_id`, `license_id` (`license_payment_type`: `mensualidad`, `instalacion`, `total`, `anual_total`), `sale_id` y `bank_account_id`.

## Reglas de Negocio
- **5.ª licencia gratuita**: Cada 5.ª licencia creada para un mismo cliente (`count % 5 === 0`) se marca automáticamente con `is_free = true` y tarifa `0.00`.
- **Ciclos estándar vs. `anual_total`**:
  - En `mensual`, `trimestral`, `semestral` y `anual`, `service_amount = monthly_fee * cycle_months` (`1`, `3`, `6`, `12`) y puede coexistir una anualidad/instalación separada (`setup_fee` + `next_setup_billing_date`).
  - En `anual_total` (*Pago Anual Total*), `monthly_fee` almacena el valor total único anual (ej. `$900,000`) sin multiplicarse por 12, forzando `setup_fee = 0` y `next_setup_billing_date = null`. Al registrar un pago de tipo `anual_total`, `next_billing_date` avanza 1 año.
- **Total a pagar en licencias (`billing_breakdown`)**:
  - Cuando `next_billing_date <= hoy` se suma `service_amount`; cuando `setup_fee > 0` y `next_setup_billing_date <= hoy` se suma `annual_fee` de forma independiente o combinada (si ambos están vencidos/vencen hoy, se suman ambos conceptos y se detalla el desglose). Si ambos están al día, muestra el próximo cobro cronológico.
- **Contratos PDF de licencias**:
  - Archivos `.pdf` de hasta 10 MB almacenados en el disco privado `local` bajo `contracts/user_{id}`, con verificación de propiedad `user_id === Auth::id()`, vista previa embebida en modal `<dialog>` (`Content-Disposition: inline`), descarga, reemplazo y eliminación segura.

## Changelog
- `2026-11-24`: Rediseñada la gestión de licencias con cálculo exacto y desglose de vencimientos en `License::billing_breakdown`, agregada modalidad `anual_total` en `License`, `LicenseController`, `PaymentController`, `Payment`, `CreditController` y vistas; implementada subida, visor modal inline, descarga y eliminación de contratos PDF (`2026_11_24_000001_add_contract_to_licenses_table.php`, `routes/web.php`, `resources/views/licenses/index.blade.php`, `public/css/auth-mik.css`).
