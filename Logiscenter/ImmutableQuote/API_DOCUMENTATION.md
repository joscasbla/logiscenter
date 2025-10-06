# API Logiscenter Immutable Quote Module

## 📋 Descripción General

El **módulo Logiscenter Immutable Quote** proporciona una **API REST avanzada** para la gestión de cotizaciones **inmutables** en Magento 2.  
Estas cotizaciones, una vez activadas, no pueden modificarse, asegurando la **integridad de precios y condiciones comerciales**.

Todas las rutas de la API comienzan con el prefijo:

/V1/immutable-quotes


Las respuestas siguen el estándar **JSON** y están protegidas por **ACLs** y **autenticación mediante tokens** (Admin o Customer).

---

## 🧩 Arquitectura de la API

La API sigue una estructura basada en contratos (`Interfaces`) y servicios (`Management`, `Repository`) definidos en `etc/webapi.xml`.

| Tipo de Componente | Descripción |
|--------------------|-------------|
| **Management Interfaces** | Definen operaciones de negocio (crear, habilitar, convertir, etc.) |
| **Repository Interfaces** | Implementan operaciones CRUD y consultas complejas |
| **CustomerQuoteManagement** | API orientada a clientes autenticados |
| **webapi.xml** | Define los endpoints REST y su mapeo a interfaces PHP |

---

## 🔒 Autenticación y Seguridad

| Tipo | Descripción |
|------|--------------|
| **Admin Token** | Requerido para endpoints administrativos (`/V1/immutable-quotes/...`) |
| **Customer Token** | Requerido para endpoints personales (`/V1/customers/me/...`) |

**Mecanismos de Seguridad:**
- Control de acceso mediante **ACLs (Access Control Lists)**
- **Rate limiting configurable** (`100 req/h por usuario`, ajustable en `system.xml`)
- Registro de auditoría en todos los endpoints críticos
- Validación estricta de parámetros y estructura de requests

---

## 📡 Estructura de Endpoints

### Categorías Principales
1. **Administrativos (Admin Token)**
    - Crear, listar, eliminar, habilitar o deshabilitar cotizaciones.
    - Operaciones globales de gestión.
2. **De Cliente (Customer Token)**
    - Consultar y activar solo las cotizaciones propias (`customers/me/...`).
3. **Conversión a Pedido**
    - Generar un pedido (`order`) desde una cotización inmutable habilitada.

---

## ⚙️ Endpoints Principales

### 🔹 Crear Cotización Inmutable
**POST** `/V1/immutable-quotes`  
Crea una cotización inmutable desde una existente.

- **ACL:** `Logiscenter_ImmutableQuote::create`
- **Auth:** Admin Token

---

### 🔹 Obtener Cotización por ID
**GET** `/V1/immutable-quotes/:quoteId`  
Devuelve los detalles completos de una cotización inmutable.

- **ACL:** `Logiscenter_ImmutableQuote::view`
- **Auth:** Admin o Customer

---

### 🔹 Activar / Desactivar Cotización
**POST** `/V1/immutable-quotes/:quoteId/enable`  
**POST** `/V1/immutable-quotes/:quoteId/disable`

Permiten alternar el estado de una cotización entre *enabled* e *immutable*.

- **ACL:** `Logiscenter_ImmutableQuote::enable` o `Logiscenter_ImmutableQuote::manage`

---

### 🔹 Eliminar Cotización
**DELETE** `/V1/immutable-quotes/:quoteId`  
Borra una cotización inmutable existente.

- **ACL:** `Logiscenter_ImmutableQuote::delete`
- **Auth:** Admin

---

### 🔹 Agregar Ítems
**POST** `/V1/immutable-quotes/:quoteId/items`  
Agrega productos a la cotización antes de volverla inmutable.

- **ACL:** `Logiscenter_ImmutableQuote::manage`

---

### 🔹 Listar Cotizaciones
**GET** `/V1/immutable-quotes`  
Devuelve una lista paginada de cotizaciones inmutables.

Parámetros comunes:

searchCriteria[pageSize]=10
searchCriteria[currentPage]=1


---

### 🔹 Cotizaciones por Cliente
**GET** `/V1/customers/:customerId/immutable-quotes`  
Devuelve todas las cotizaciones inmutables asociadas a un cliente.

**GET** `/V1/customers/:customerId/immutable-quotes/active`  
Devuelve la cotización activa del cliente.

---

### 🔹 Convertir a Pedido
**POST** `/V1/immutable-quotes/:quoteId/order`  
Convierte una cotización activa en una orden Magento.

- **ACL:** `Logiscenter_ImmutableQuote::checkout`

---

## 👤 Endpoints para Clientes Autenticados

Los clientes autenticados pueden acceder exclusivamente a sus propias cotizaciones.

| Endpoint | Método | Descripción |
|-----------|--------|--------------|
| `/V1/customers/me/immutable-quotes` | GET | Listar todas las cotizaciones del cliente |
| `/V1/customers/me/immutable-quotes/:quoteId` | GET | Ver detalles de una cotización específica |
| `/V1/customers/me/immutable-quotes/:quoteId/enable` | POST | Activar una cotización inmutable |

---

## ⚠️ Códigos de Error y Respuestas

| Código | Descripción | Ejemplo |
|--------|--------------|----------|
| **400** | Datos inválidos o formato incorrecto | `"Invalid quoteId"` |
| **401** | No autenticado | `"Authentication required"` |
| **403** | Permisos insuficientes (ACL) | `"Access denied"` |
| **404** | No se encontró la cotización | `"Quote not found"` |
| **409** | Estado conflictivo (ej. ya activa) | `"Immutable quote already enabled"` |
| **429** | Límite de peticiones excedido (rate limit) | `"Too many requests"` |
| **500** | Error interno del servidor | `"Unexpected error occurred"` |

---

## 🧱 Modelos de Datos

### `ImmutableQuoteInterface`

| Campo | Tipo | Descripción |
|--------|------|-------------|
| `immutable_quote_id` | int | ID interno de la cotización inmutable |
| `quote_id` | int | ID de la cotización original |
| `customer_id` | int | ID del cliente |
| `status` | string | Estado (`immutable`, `enabled`, `disabled`) |
| `created_at` | string | Fecha de creación |
| `updated_at` | string | Última modificación |
| `expires_at` | string | Fecha de expiración |
| `notes` | string | Notas opcionales |

---

## 🚦 Rate Limiting y Auditoría

- **Límite predeterminado:** 100 peticiones/hora por usuario
- **Archivo de configuración:** `etc/adminhtml/system.xml`
- **Respuesta HTTP:** `429 Too Many Requests`

**Auditoría Interna:**
- Registro de creación, habilitación y eliminación de cotizaciones
- Detección de intentos de modificación no autorizados
- Logs disponibles en `var/log/immutable_quote_audit.log`

---

## 🧠 Buenas Prácticas de Implementación

- Usar **tokens de vida corta** (`integration` o `customer tokens`)
- Validar siempre la existencia del `quote_id` antes de crear una versión inmutable
- Cachear respuestas **GET** con `CacheServiceForImmutableQuotes`
- Revisar logs de auditoría ante errores de autorización o seguridad

---

## 🧩 Integración con Magento

El sistema utiliza componentes nativos de Magento 2:

| Componente | Rol |
|-------------|-----|
| **Webapi Framework** | Exposición de endpoints REST |
| **DI (Dependency Injection)** | Inyección de servicios y factories |
| **Repositories** | Persistencia y consultas en base de datos |
| **ACL System** | Control de permisos basado en roles |
| **Events/Observers** | Integración con otros módulos o sistemas externos |

---

## 🧱 Archivos Relacionados

| Archivo | Propósito |
|----------|-----------|
| `etc/webapi.xml` | Define las rutas REST y permisos ACL |
| `Api/ImmutableQuoteManagementInterface.php` | Contrato principal de operaciones de negocio |
| `Api/ImmutableQuoteRepositoryInterface.php` | Contrato de persistencia y lectura de datos |
| `Model/CustomerQuoteManagement.php` | Lógica de API para clientes autenticados |
| `Model/ImmutableQuoteManagementService.php` | Implementación del flujo completo de gestión |
| `Model/ImmutableQuoteRepository.php` | Implementación de acceso a datos |
| `Service/RateLimitingService.php` | Control de peticiones y prevención de abuso |
| `Service/AuditLoggerService.php` | Registro de auditoría y eventos críticos |

---

## 📖 Ejemplo de Flujo Completo

1. **Admin crea una cotización inmutable** → `POST /V1/immutable-quotes`
2. **Cliente la activa** → `POST /V1/customers/me/immutable-quotes/:quoteId/enable`
3. **Cliente genera orden** → `POST /V1/immutable-quotes/:quoteId/order`
4. **Sistema registra auditoría** → `AuditLoggerService`
