# MIK Software Control - Sistema de Gestión Personal y Financiero

![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel)
![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-4-06B6D4?logo=tailwindcss)
![Vite](https://img.shields.io/badge/Vite-8-646CFF?logo=vite)
![License](https://img.shields.io/badge/License-MIT-green)

---

## 📋 Descripción General

**MIK Software Control** es un sistema ERP/sistema de gestión integral construido con **Laravel 13** diseñado para que un **desarrollador/freelancer** o una **pequeña empresa de software** (MIK Software) pueda gestionar y controlar las finanzas personales y del negocio.

El sistema permite administrar de forma centralizada: **clientes, licencias SaaS vendidas, desarrollos a medida, pagos e ingresos, préstamos, créditos a pagar, cuentas bancarias y gastos**, todo con un panel de control moderno y reportes detallados.

---

## 🚀 Stack Tecnológico

| Tecnología | Versión | Propósito |
|------------|---------|-----------|
| **Laravel** | ^13.8 | Framework PHP backend |
| **PHP** | ^8.3 | Lenguaje de programación |
| **Tailwind CSS** | ^4.0 | Framework CSS utility-first |
| **Vite** | ^8.0 | Bundler y dev server |
| **MySQL** | - | Base de datos relacional |
| **DomPDF** | ^3.1 | Generación de PDFs |
| **Bootstrap Icons** | ^1.11 | Librería de iconos |

---

## ✨ Funcionalidades Principales

### 📊 Dashboard
Panel principal con métricas resumidas: total de clientes, licencias activas, ingresos del mes y saldo pendiente. Vista rápida de clientes recientes y últimos pagos registrados.

### 👥 Clientes
CRUD completo de clientes clasificados por:
- **Tipo**: Persona / Empresa
- **Modelo de negocio**: Revendedor / Cliente Final

### 🔑 Licencias de Software
Gestión de licencias SaaS vendidas con:
- URL del sistema, token de bloqueo para control remoto
- Ciclos de facturación: mensual, trimestral, semestral, anual
- Cuota mensual y costo de instalación
- Estados: Activa, Suspendida, Vencida
- **Regla de negocio**: Cada 5ª licencia de un cliente es automáticamente GRATUITA ($0.00)
- **Control remoto**: Consulta de estado y activación/desactivación del sistema remoto vía API proxy (evita CORS)

### 💻 Desarrollos a Medida
Gestión de trabajos de desarrollo clasificados en:
- **Proyectos**: Desarrollo completo con monto fijo y fechas
- **Mejoras**: Modificaciones o features adicionales con seguimiento de pago FIFO
- **Soportes**: Contratos de soporte mensual con período definido y ciclo de facturación

Jerarquía padre-hijo para mejoras dentro de proyectos.

### 💰 Pagos y Abonos
Registro de ingresos con:
- Asociación a cliente, desarrollo, licencia o cuenta global
- Métodos de pago: Efectivo, Nequi, Bancolombia, Transferencia
- **Actualización automática** del saldo de la cuenta bancaria destino
- Lógica de distribución FIFO para pagos no específicos

### 🏦 Cuentas Bancarias
Gestión de cuentas con saldo contable que se actualiza automáticamente al:
- Registrar un pago (incrementa saldo)
- Eliminar un pago (decrementa saldo)
- Registrar un gasto (decrementa saldo)

### 💰 Ingresos Generales
Registro de ingresos personales y del negocio NO asociados a clientes específicos (salario, freelance, ventas varias, transferencias recibidas, etc.). Funciona como la contraparte de Gastos:
- Asociación a una cuenta bancaria de destino
- Categorización por tipo de ingreso (Salario, Freelance, Venta, etc.)
- **Actualización automática**: incrementa el saldo de la cuenta bancaria seleccionada
- Al editar o eliminar un ingreso, el saldo se reajusta automáticamente
- Tarjetas de resumen con totales del mes y del año

### 💸 Gastos / Compras
Registro de egresos categorizados con descuento automático del saldo de la cuenta bancaria seleccionada.

### 🤝 Préstamos
Gestión de préstamos clasificados como:
- **Recibido** (Me prestaron dinero a mí)
- **Entregado** (Yo presté dinero a alguien)
- Estados: Pendiente, Devuelto, Canjeado

### 📝 Créditos (Cuentas por Pagar)
Gestión de créditos y financiamiento con:
- Tipo: Proveedor (Canje) / Personal (Cuotas)
- Seguimiento de cuotas pagadas automático
- Progreso porcentual de pago
- Cálculo automático de saldo restante

### 📄 Reportes - Estado de Cuentas
Reportes financieros detallados por cliente con:
- Desglose de desarrollos (proyectos, mejoras, soportes)
- Pagos con distribución FIFO
- Préstamos y créditos asociados
- Totales por método de pago
- **Exportación a PDF** (DomPDF)
- Progreso de pago y saldos pendientes

### 🔄 Importación de Base de Datos
Herramienta para importar datos desde archivos SQL con validaciones de seguridad:
- Solo permite sentencias INSERT
- Restringido a tablas específicas: `clients`, `licenses`, `developments`, `payments`
- Modo seco (previsualización) antes de ejecutar

### 🔐 Autenticación
Sistema de autenticación personalizado con login, registro y logout. Diseño moderno con fondo animado y partículas (particles.js).

---

## 🗄️ Estructura de la Base de Datos

| Tabla | Descripción |
|-------|-------------|
| `users` | Usuarios del sistema |
| `clients` | Clientes (personas/empresas, revendedores/cliente final) |
| `licenses` | Licencias SaaS con ciclo de facturación y control remoto |
| `developments` | Desarrollos (proyectos, mejoras, soportes) con jerarquía padre-hijo |
| `payments` | Pagos recibidos con método y asociación a desarrollo/licencia/cuenta bancaria |
| `loans` | Préstamos (recibidos y entregados) |
| `credits` | Créditos a pagar con cuotas |
| `credit_payments` | Abonos realizados a créditos |
| `bank_accounts` | Cuentas bancarias con saldo actualizable automáticamente |
| `incomes` | Ingresos generales (salario, freelance, ventas) vinculados a cuentas bancarias |
| `expenses` | Gastos categorizados vinculados a cuentas bancarias |

---

## 📁 Estructura del Proyecto

```
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php           # Login, registro y logout
│   │   │   ├── ClientController.php         # CRUD de clientes
│   │   │   ├── LicenseController.php        # CRUD de licencias + control remoto
│   │   │   ├── DevelopmentController.php    # CRUD de desarrollos (proyectos/mejoras/soporte)
│   │   │   ├── PaymentController.php        # CRUD de pagos con actualización de saldo
│   │   │   ├── CreditController.php         # CRUD de créditos + pagos de créditos
│   │   │   ├── LoanController.php           # CRUD de préstamos
│   │   │   ├── BankAccountController.php    # CRUD de cuentas bancarias
│   │   │   ├── IncomeController.php         # CRUD de ingresos generales
│   │   │   ├── ExpenseController.php        # CRUD de gastos
│   │   │   ├── ReportController.php         # Reportes y PDFs
│   │   │   └── DatabaseImportController.php # Importación controlada de SQL
│   │   └── ...
│   ├── Models/
│   │   ├── User.php
│   │   ├── Client.php                       # Clientes (persona/empresa, revendedor/cliente final)
│   │   ├── License.php                      # Licencias SaaS con regla de 5ta gratis
│   │   ├── Development.php                  # Desarrollos con jerarquía y tipos
│   │   ├── Payment.php                      # Pagos multi-método
│   │   ├── Loan.php                         # Préstamos recibidos/entregados
│   │   ├── Credit.php                       # Créditos a pagar con cuotas
│   │   ├── CreditPayment.php                # Abonos a créditos
│   │   ├── BankAccount.php                  # Cuentas bancarias con saldo
│   │   ├── Income.php                       # Ingresos generales
│   │   └── Expense.php                      # Gastos categorizados
│   └── ...
├── config/                                  # Configuraciones de Laravel
├── database/
│   ├── migrations/                          # Migraciones de BD
│   └── seeders/                             # Datos de prueba
├── resources/
│   └── views/
│       ├── layouts/
│       │   ├── app.blade.php                # Layout principal con sidebar
│       │   └── auth.blade.php               # Layout para páginas de autenticación
│       ├── auth/
│       │   ├── login.blade.php              # Página de inicio de sesión
│       │   └── register.blade.php           # Página de registro
│       ├── dashboard.blade.php              # Panel principal con métricas
│       ├── clients/index.blade.php          # Gestión de clientes
│       ├── licenses/index.blade.php         # Gestión de licencias
│       ├── developments/index.blade.php     # Gestión de desarrollos
│       ├── payments/index.blade.php         # Gestión de pagos
│       ├── loans/index.blade.php            # Gestión de préstamos
│       ├── credits/index.blade.php          # Gestión de créditos
│       ├── credits/show.blade.php           # Detalle de crédito con pagos
│       ├── bank_accounts/index.blade.php    # Gestión de cuentas bancarias
│       ├── incomes/index.blade.php          # Gestión de ingresos
│       ├── expenses/index.blade.php         # Gestión de gastos
│       ├── reports/index.blade.php          # Reportes - lista de clientes
│       ├── reports/show.blade.php           # Estado de cuenta detallado
│       ├── reports/pdf.blade.php            # Plantilla PDF de estado de cuenta
│       ├── db-import/index.blade.php        # Importador SQL
│       └── partials/logo.blade.php          # Componente de logo
├── public/
│   └── css/
│       └── auth-mik.css                     # Estilos personalizados del sistema
├── routes/
│   └── web.php                              # Todas las rutas web del sistema
├── composer.json                            # Dependencias PHP
└── package.json                             # Dependencias Node.js
```

---

## 🧠 Lógica de Negocio Destacada

### Regla de la 5ª Licencia Gratis
Cuando un cliente acumula 5, 10, 15... licencias (múltiplos de 5), la siguiente licencia se marca automáticamente como gratuita ($0.00 de cuota mensual). Si se cambia el cliente de una licencia, se recalcula automáticamente.

### Distribución FIFO de Pagos
Los pagos no asociados a un desarrollo específico se distribuyen automáticamente sobre los desarrollos pendientes más antiguos primero (FIFO - First In, First Out), permitiendo un seguimiento preciso de la deuda real de cada cliente.

### Actualización Automática de Saldos
Cada pago registrado incrementa automáticamente el saldo de la cuenta bancaria destino. Cada gasto registrado lo decrementa. Al eliminar un pago o gasto, se hace la operación inversa para mantener la consistencia contable.

### Control Remoto de Sistemas
Las licencias incluyen un token de seguridad que permite, desde el panel de control, consultar el estado y activar/desactivar remotamente el sistema licenciado a través de una API proxy que evita problemas de CORS.

---

## 🔧 Instalación y Configuración

### Requisitos
- PHP ^8.3
- Composer
- Node.js
- MySQL

### Instalación

```bash
# Clonar el repositorio
git clone https://github.com/miksoftware/personal.git
cd personal

# Instalar dependencias PHP
composer install

# Configurar variables de entorno
cp .env.example .env
php artisan key:generate

# Configurar la base de datos en .env y luego migrar
php artisan migrate

# Instalar dependencias frontend
npm install

# Compilar assets
npm run build
```

### Desarrollo

```bash
# Iniciar servidor de desarrollo con Vite
npm run dev

# En otra terminal, iniciar Laravel
php artisan serve
```

---

## 🧪 Comandos Disponibles

```bash
# Instalación completa del proyecto
composer run setup

# Iniciar entorno de desarrollo
composer run dev

# Ejecutar tests
composer run test
```

---

## 🛣️ Rutas Principales

| Ruta | Método | Descripción |
|------|--------|-------------|
| `/` | GET | Redirección al dashboard o login |
| `/login` | GET/POST | Inicio de sesión |
| `/register` | GET/POST | Registro de usuario |
| `/dashboard` | GET | Panel principal |
| `/clients` | GET/POST/PUT/DELETE | CRUD de clientes |
| `/licenses` | GET/POST/PUT/DELETE | CRUD de licencias |
| `/licenses/{id}/system-status` | GET | Estado del sistema remoto |
| `/licenses/{id}/system-toggle` | POST | Activar/desactivar sistema remoto |
| `/developments` | GET/POST/PUT/DELETE | CRUD de desarrollos |
| `/payments` | GET/POST/DELETE | CRUD de pagos |
| `/loans` | GET/POST/PUT/DELETE | CRUD de préstamos |
| `/credits` | GET/POST/PUT | CRUD de créditos |
| `/credits/{credit}/payments` | POST/DELETE | Abonos a créditos |
| `/bank-accounts` | GET/POST/PUT/DELETE | CRUD de cuentas bancarias |
| `/incomes` | GET/POST/PUT/DELETE | CRUD de ingresos generales |
| `/incomes/{income}` | GET/POST/PUT/DELETE | CRUD de ingresos generales |
| `/expenses` | GET/POST/PUT/DELETE | CRUD de gastos |
| `/reports/estado-cuenta` | GET | Reportes financieros |
| `/reports/estado-cuenta/{client}/pdf` | GET | Descarga PDF de estado de cuenta |
| `/db-import` | GET/POST | Importador de base de datos |
| `/logout` | POST | Cerrar sesión |

---

## 📄 Licencia

Este proyecto es software de código abierto licenciado bajo la [MIT License](https://opensource.org/licenses/MIT).

---

*Desarrollado por MIK Software &copy; 2025 - 2026*