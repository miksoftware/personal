# Memoria Persistente del Proyecto (`personal` — MIK Software Control)

## Stack Tecnológico
- **Backend**: PHP ^8.3, Laravel ^13.8 (`laravel/framework`), `barryvdh/laravel-dompdf` ^3.1 para estados de cuenta en PDF.
- **Frontend**: Blade templates (`resources/views/`), Bootstrap Icons CDN, hoja de estilos principal `public/css/auth-mik.css`, JavaScript vanilla con elementos nativos `<dialog>` y Fetch API (AJAX para búsqueda en vivo, control remoto de módulos y gestión de contratos PDF).
- **Almacenamiento**: Disco privado `local` (`storage/app/private`) para archivos sensibles como contratos PDF por licencia; servidos mediante controlador autenticado con `response()->file(...)`.

## Arquitectura y Módulos
- **Multi-tenancy SaaS y Licencias Compartidas**:
  - Aislamiento por usuario mediante el trait `App\Models\Traits\BelongsToUser` (`user_id` global scope + asignación automática al crear). Roles `admin` / `superadmin` gestionados en `Admin\UserController`.
  - Un usuario SaaS (`users.linked_client_id`) puede vincularse desde `/admin/users` a un cliente revendedor del administrador para visualizar las licencias de dicho cliente (alcance definido en `License::booted()`).
  - En licencias compartidas, el usuario vinculado solo ve el estado de los módulos en modo lectura (consultados vía proxy backend usando el token del propietario sin exponer `block_token` ni permitir alternar módulos), puede ver contratos PDF existentes en solo lectura y únicamente puede personalizar: (1) su nombre local de cliente en `license_user_overrides` (`custom_client_name`, sin alterar el registro del propietario) y (2) la modalidad `anual_total` vs plan estándar (`standard_*` snapshot en `licenses`), sincronizada con el propietario.
- **Módulos principales** (`routes/web.php`):
  - `DashboardController` (`/dashboard`): métricas financieras del mes y saldos pendientes.
  - `ClientController` (`/clients`): clientes (`persona`/`empresa`, modelo `cliente_final`/`revendedor`).
  - `LicenseController` (`/licenses`): licencias de software, control remoto de sistema y módulos (`/system-status`, `/system-toggle`, `/system-modules`, `/system-modules/toggle`), y gestión de contratos PDF (`/licenses/{license}/contract`).
  - `DevelopmentController` (`/developments`): mejoras, proyectos y contratos de soporte.
  - `SaleController` (`/sales`): ventas de equipos y otros.
  - `PaymentController` (`/payments`): registro de ingresos hacia `BankAccount` o canje hacia `Credit`, con actualización automática de fechas de vencimiento de licencias.
  - `LoanController` (`/loans`), `CreditController` (`/credits`), `BankAccountController` (`/bank-accounts`), `IncomeController` (`/incomes`), `ExpenseController` (`/expenses`), `ReportController` (`/reports/estado-cuenta`), `DatabaseImportController` (`/db-import`).

## Modelos y Base de Datos Clave
- **`License` (`licenses`) y `LicenseUserOverride` (`license_user_overrides`)**:
  - Campos principales de `licenses`: `user_id`, `client_id`, `url`, `block_token` (nullable), `status` (`activa`, `suspendida`, `vencida`), `billing_cycle` (`mensual`, `trimestral`, `semestral`, `anual`, `anual_total`), `monthly_fee`, `setup_fee`, `next_billing_date`, `next_setup_billing_date`, `standard_billing_cycle`, `standard_monthly_fee`, `standard_setup_fee`, `standard_next_setup_billing_date`, `is_free`, `contract_path`, `contract_original_name`, `contract_uploaded_at`.
  - `license_user_overrides`: `license_id`, `user_id`, `custom_client_name` (único por `[license_id, user_id]`).
  - Helpers clave en `App\Models\License`: `isAnnualTotal()`, `hasContract()`, `isOwnedBy()`, `isSharedForUser()`, `canViewBy()`, `displayClientNameFor()`, `switchToAnnualTotal()`, `restoreStandardPlan()`, `cycle_months`, `service_amount`, `annual_fee`, `billing_breakdown`.
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
- `2026-11-24`: Compactada la vista `/licenses` sin scroll externo de página (`app-layout--viewport-lock`) conservando el scroll interno de la tabla; implementadas licencias SaaS compartidas vía `users.linked_client_id`, `license_user_overrides` y snapshot `standard_*` para alternar `anual_total` de forma sincronizada y renombrar clientes localmente (`2026_11_24_000002_add_saas_sharing_to_users_and_licenses.php`, `LicenseUserOverride.php`, `License.php`, `User.php`, `LicenseController.php`, `Admin\UserController.php`, `admin/users/index.blade.php`, `licenses/index.blade.php`, `layouts/app.blade.php`, `public/css/auth-mik.css`).
- `2026-11-24`: Rediseñada la gestión de licencias con cálculo exacto y desglose de vencimientos en `License::billing_breakdown`, agregada modalidad `anual_total` en `License`, `LicenseController`, `PaymentController`, `Payment`, `CreditController` y vistas; implementada subida, visor modal inline, descarga y eliminación de contratos PDF (`2026_11_24_000001_add_contract_to_licenses_table.php`, `routes/web.php`, `resources/views/licenses/index.blade.php`, `public/css/auth-mik.css`).
