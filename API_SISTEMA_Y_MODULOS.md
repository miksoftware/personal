# Documentación: API de Sistema, Licencias y Módulos

Esta API permite gestionar de manera remota y centralizada el estado del sistema (bloqueo/licencia) y la activación o desactivación de módulos funcionales (como **Contabilidad** y sus reportes), sin necesidad de editar manualmente archivos `.env` en cada proyecto.

---

## 1. Autenticación y Token de Administración

El acceso a todos los endpoints de `/api/system/*` está protegido por un token de administración que se almacena y consulta directamente en la base de datos en la tabla `system_tokens`.

### Token Maestro por Defecto
- **Token por defecto:** `adminmikpos123`
- **Nombre en BD:** `default`
- **Tabla:** `system_tokens`

### Despliegue Automático (`deploy.sh`)
Cuando se ejecuta el script de despliegue `deploy.sh`:
1. El paso **3** corre `php artisan migrate --force`, creando la tabla `system_tokens` si no existe.
2. El paso **3.5** corre `php artisan db:seed-pending --force`, el cual detecta y ejecuta automáticamente el seeder `SystemTokenSeeder`, registrando el token `adminmikpos123` de forma predeterminada e idempotente.

> **Nota:** No es necesario configurar ninguna variable en el archivo `.env` para que el token funcione.
> 
> ### ¿Qué pasa si un proyecto ya tiene `SYSTEM_ADMIN_TOKEN` en el `.env`?
> - **No pasa nada negativo; no se rompe nada.**
> - El sistema cuenta con **doble validación (Fallback)**:
>   1. Si envías el token que ya estaba en el `.env`, **sigue funcionando con total normalidad**.
>   2. Si envías el token maestro estándar `adminmikpos123`, **también funciona**.
>   3. Además, al ejecutar `deploy.sh`, el seeder detecta si había un token previo en `.env` y lo almacena automáticamente en la tabla `system_tokens` para preservarlo.
> - **Uso unificado:** El token (sea el del `.env` o `adminmikpos123`) sirve **indistintamente para ambas operaciones**:
>   - **Licencias y Control del Sistema:** `/api/system/toggle` y `/api/system/status` (bloqueo/desbloqueo global).
>   - **Gestión de Módulos:** `/api/system/modules` y `/api/system/modules/toggle` (activar/desactivar Contabilidad u otros módulos).

### Formas de Enviar el Token en las Peticiones
El token puede enviarse en cualquiera de las siguientes 3 formas:

1. **Parámetro en Query o Body:**
   - Query string: `?token=adminmikpos123`
   - Body JSON: `{"token": "adminmikpos123", ...}`
2. **Cabecera Personalizada:**
   - `X-System-Token: adminmikpos123`
3. **Cabecera de Autorización Estándar:**
   - `Authorization: Bearer adminmikpos123`

---

## 2. Módulos del Sistema

| Clave del Módulo | Alias Aceptados | Nombre | Estado Inicial | Descripción |
| :--- | :--- | :--- | :--- | :--- |
| `accounting` | `contabilidad` | Contabilidad | **Deshabilitado (`false`)** | Plan de cuentas (PUC), comprobantes, asientos y reportes contables oficiales (Libro Diario, Mayor y Balances, Auxiliar). |
| `ecommerce` | `tienda`, `tiendas`, `shop`, `ecommerce_orders` | Tienda Virtual | **Activo por defecto si ya está activada en el cliente (`true`)** | Catálogo público web, carrito de compras, pedidos virtuales y checkout. Se mantiene activo automáticamente en clientes que ya tienen la tienda en uso. |

> **Comportamientos Clave:**
> - El módulo `accounting` inicia **deshabilitado por defecto** y solo se activa cuando se solicita explícitamente vía API.
> - El módulo `ecommerce` (Tienda Virtual) **mantiene su estado activo por defecto en clientes que ya tienen la tienda activada** (detecta automáticamente si existen sucursales con tienda o si ya estaba activa), garantizando que ningún cliente pierda su tienda al actualizar. Puede ser desactivado o activado de forma remota en cualquier momento mediante los endpoints de la API.
> - **Módulo de Configuración Visual y Operativo:** Cuenta con una interfaz administrativa en `/ecommerce/settings` donde se visualiza el estado del módulo, se configura la modalidad (unificada vs links independientes), visibilidad de stock, paleta de 10 colores con vista previa en vivo y modo de registro (al checkout vs desde el inicio), todo configurable de forma global o individualmente por sucursales.

---

## 3. Catálogo de Endpoints

### 3.0. Endpoints Dedicados de Tienda Virtual (Atajos Directos)
Para mayor facilidad y rapidez, existen endpoints dedicados específicos para gestionar la tienda sin necesidad de pasar el parámetro `module`:

- **Alternar / Activar / Desactivar Tienda:** `POST/GET /api/system/shop/toggle` (o `/api/system/tienda/toggle`)
- **Consultar Estado de la Tienda:** `GET /api/system/shop/status` (o `/api/system/tienda/status`, `/api/system/shop`)

#### Ejemplo: Habilitar Tienda Virtual directamente
```bash
curl -X POST "https://tu-dominio.test/api/system/shop/toggle" \
     -H "Content-Type: application/json" \
     -H "X-System-Token: adminmikpos123" \
     -d '{
       "action": "enable"
     }'
```

#### Ejemplo: Deshabilitar Tienda Virtual directamente
```bash
curl -X POST "https://tu-dominio.test/api/system/shop/toggle" \
     -H "Content-Type: application/json" \
     -H "X-System-Token: adminmikpos123" \
     -d '{
       "action": "disable"
     }'
```

#### Ejemplo: Consultar Estado detallado de la Tienda Virtual
```bash
curl -X GET "https://tu-dominio.test/api/system/shop/status" \
     -H "X-System-Token: adminmikpos123"
```
**Respuesta (`200 OK`):**
```json
{
  "success": true,
  "module": {
    "key": "ecommerce",
    "name": "Tienda Virtual",
    "description": "Módulo de tienda en línea / e-commerce (catálogo web público, pedidos virtuales y checkout).",
    "enabled": true,
    "status": "enabled"
  },
  "details": {
    "branches_with_ecommerce": 1,
    "total_active_branches": 2,
    "shop_url": "https://tu-dominio.test/shop",
    "branches": [
      {
        "id": 1,
        "name": "Principal",
        "code": "SUC-01",
        "slug": "principal",
        "ecommerce_enabled": true
      }
    ]
  }
}
```

---

### 3.1. Consultar Estado de Módulos
Obtiene la lista de módulos registrados con su estado actual (`enabled` o `disabled`), o el detalle de un módulo específico.

- **Método:** `GET`
- **Ruta:** `/api/system/modules`
- **Parámetros Opcionales:**
  - `module`: Clave o alias del módulo (ej. `accounting` o `contabilidad`).

#### Ejemplo: Consultar todos los módulos
```bash
curl -X GET "https://tu-dominio.test/api/system/modules" \
     -H "X-System-Token: adminmikpos123"
```
**Respuesta (`200 OK`):**
```json
{
  "success": true,
  "modules": {
    "accounting": {
      "key": "accounting",
      "name": "Contabilidad",
      "description": "Módulo de contabilidad (Plan de cuentas PUC, comprobantes, asientos y reportes contables oficiales).",
      "enabled": false,
      "status": "disabled"
    }
  }
}
```

#### Ejemplo: Consultar un módulo específico
```bash
curl -X GET "https://tu-dominio.test/api/system/modules?module=accounting" \
     -H "X-System-Token: adminmikpos123"
```
**Respuesta (`200 OK`):**
```json
{
  "success": true,
  "module": {
    "key": "accounting",
    "name": "Contabilidad",
    "description": "Módulo de contabilidad (Plan de cuentas PUC, comprobantes, asientos y reportes contables oficiales).",
    "enabled": false,
    "status": "disabled"
  }
}
```

---

### 3.2. Activar / Desactivar / Alternar un Módulo
Permite encender o apagar un módulo individual.

- **Método:** `POST`
- **Ruta:** `/api/system/modules/toggle`
- **Parámetros (JSON o Form-Data):**
  - `module` *(requerido)*: `accounting` o `contabilidad`.
  - `action` *(opcional)*:
    - `"enable"`: Fuerza la activación del módulo.
    - `"disable"`: Fuerza la desactivación del módulo.
    - Omitir o `null`: Invierte el estado actual (`toggle`).
  - `enabled` *(opcional alternativa)*: `true` o `false`.

#### Ejemplo: Habilitar el módulo de Contabilidad
```bash
curl -X POST "https://tu-dominio.test/api/system/modules/toggle" \
     -H "Content-Type: application/json" \
     -H "X-System-Token: adminmikpos123" \
     -d '{
       "module": "accounting",
       "action": "enable"
     }'
```
**Respuesta (`200 OK`):**
```json
{
  "success": true,
  "module": "accounting",
  "name": "Contabilidad",
  "status": "enabled",
  "enabled": true,
  "message": "Módulo 'Contabilidad' habilitado correctamente."
}
```

#### Ejemplo: Deshabilitar el módulo de Contabilidad
```bash
curl -X POST "https://tu-dominio.test/api/system/modules/toggle" \
     -H "Content-Type: application/json" \
     -H "X-System-Token: adminmikpos123" \
     -d '{
       "module": "accounting",
       "action": "disable"
     }'
```
**Respuesta (`200 OK`):**
```json
{
  "success": true,
  "module": "accounting",
  "name": "Contabilidad",
  "status": "disabled",
  "enabled": false,
  "message": "Módulo 'Contabilidad' deshabilitado correctamente."
}
```

---

### 3.3. Actualización Masiva o Simple de Módulos
Permite actualizar el estado de varios módulos en una sola petición.

- **Método:** `POST`
- **Ruta:** `/api/system/modules`
- **Parámetros:**
  - `modules`: Objeto clave-valor con el estado booleano de cada módulo.

#### Ejemplo:
```bash
curl -X POST "https://tu-dominio.test/api/system/modules" \
     -H "Content-Type: application/json" \
     -H "X-System-Token: adminmikpos123" \
     -d '{
       "modules": {
         "accounting": true
       }
     }'
```
**Respuesta (`200 OK`):**
```json
{
  "success": true,
  "message": "Módulos actualizados correctamente.",
  "modules": {
    "accounting": {
      "key": "accounting",
      "name": "Contabilidad",
      "description": "Módulo de contabilidad (Plan de cuentas PUC, comprobantes, asientos y reportes contables oficiales).",
      "enabled": true,
      "status": "enabled"
    }
  }
}
```

---

### 3.4. Estado General del Sistema (Licencia y Módulos)
Devuelve el estado general del sistema (si está bloqueado por falta de pago o activo) junto con el mapa de estado de todos los módulos.

- **Método:** `GET`
- **Ruta:** `/api/system/status`

#### Ejemplo:
```bash
curl -X GET "https://tu-dominio.test/api/system/status" \
     -H "X-System-Token: adminmikpos123"
```
**Respuesta (`200 OK`):**
```json
{
  "success": true,
  "status": "enabled",
  "modules": {
    "accounting": true
  }
}
```

---

### 3.5. Bloqueo / Desbloqueo Maestro del Sistema (Toggle Sistema)
Permite deshabilitar el sistema completo (mostrando una pantalla de mantenimiento 503 para todos los usuarios) o reactivarlo.

- **Método:** `POST`
- **Ruta:** `/api/system/toggle`
- **Parámetros:**
  - `action`: `"disable"`, `"enable"`, o dejar vacío para alternar (`toggle`).

#### Ejemplo: Bloquear el sistema completo
```bash
curl -X POST "https://tu-dominio.test/api/system/toggle" \
     -H "Content-Type: application/json" \
     -H "X-System-Token: adminmikpos123" \
     -d '{"action": "disable"}'
```
**Respuesta (`200 OK`):**
```json
{
  "success": true,
  "status": "disabled",
  "message": "Sistema deshabilitado correctamente."
}
```

---

## 4. Efectos en la Interfaz y Rutas cuando un Módulo está Deshabilitado

### 4.1. Módulo de Contabilidad (`accounting`)
Aun cuando un usuario tenga el rol `super_admin` o tenga asignados todos los permisos contables (`accounting.view`, `accounting.accounts`, `accounting.entries`, `accounting.ledger`, `accounting.settings`):

1. **Barra Lateral y Navegación:**
   - La sección **Contabilidad** (Plan de Cuentas y Configuración) **no se muestra** en la barra lateral ni en la versión móvil.
   - El submenú de **Reportes Contables** (Libro Diario, Libro Mayor y Balances, Libro Auxiliar) **no se muestra** dentro de la sección de Reportes.

2. **Protección de Rutas (Middleware `module:accounting`):**
   - Si se intenta ingresar directamente por URL a cualquiera de las siguientes rutas:
     - `/accounting/accounts`
     - `/accounting/settings`
     - `/reports/accounting/entries`
     - `/reports/accounting/general-ledger`
     - `/reports/accounting/ledger`
     - `/accounting-receipt/{entry}`
   - El sistema aborta inmediatamente con error HTTP `403 Forbidden`:
     `"El módulo 'Contabilidad' no está habilitado en su licencia."`

3. **Cálculos y Asientos en Segundo Plano:**
   - `AccountingService` detiene la generación de asientos contables automáticos en ventas, pagos de cartera y devoluciones, evitando sobrecarga y escrituras innecesarias en la base de datos.

### 4.2. Módulo de Tienda Virtual (`ecommerce`)
Cuando el módulo de Tienda Virtual es deshabilitado vía API:

1. **Rutas Públicas de la Tienda (Clientes y Visitantes):**
   - El ingreso a `/shop`, `/shop/cart`, `/shop/checkout`, `/shop/catalog` y cualquier URL por sucursal `/{branch_slug}/shop` responde inmediatamente con HTTP `503 Service Unavailable`:
     `"La tienda en línea no está disponible en este momento."`

2. **Rutas del Panel Administrativo (Personal y Administradores):**
   - El acceso a la gestión de pedidos web (`/ecommerce-orders`) y a los reportes de pedidos en PDF/Excel aborta con HTTP `403 Forbidden`:
     `"El módulo 'Tienda Virtual' no está habilitado en su licencia."`

3. **Barra Lateral y Menús:**
   - El acceso a **"Pedidos Tienda"** se oculta automáticamente de la barra lateral tanto en escritorio como en móviles.

4. **Gestión de Sucursales:**
   - La opción para configurar o activar la tienda en línea dentro del formulario de Sucursales se oculta del panel.
   - Las insignias de enlace directo a tienda pública y el banner global de tiendas se desactivan visualmente.

5. **Notificaciones y Productos:**
   - La campana de notificaciones del sistema desactiva las consultas en segundo plano para pedidos web pendientes.
   - El catálogo de productos en el POS/Administración oculta los filtros y badges exclusivos de tienda virtual.

---

## 5. Respuestas de Error Comunes

| Código HTTP | Causa | Ejemplo de Respuesta |
| :---: | :--- | :--- |
| `401 Unauthorized` | Token ausente, incorrecto o inactivo en BD | `{"success": false, "message": "No autorizado."}` |
| `404 Not Found` | El módulo solicitado no existe | `{"success": false, "message": "El módulo 'xyz' no está registrado."}` |
| `422 Unprocessable` | Faltan campos requeridos (ej. falta `module`) | `{"success": false, "message": "El campo 'module' es requerido."}` |
| `403 Forbidden` | Usuario intenta acceder a una ruta de módulo inactivo | `{"message": "El módulo 'Contabilidad' no está habilitado en su licencia."}` |

---

## 6. Estructura de la Tabla `system_tokens`

```sql
CREATE TABLE `system_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT 'default',
  `token` varchar(255) NOT NULL UNIQUE,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `description` varchar(255) DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
);
```

Si en el futuro deseas crear tokens adicionales (por ejemplo, para servidores de monitoreo o soporte), puedes insertar un nuevo registro con su propio `token` y `name`, y tendrá acceso inmediato sin alterar el token por defecto.
