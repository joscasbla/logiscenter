# README - Módulo Logiscenter Immutable Quote

## 📋 Descripción General

El **módulo Logiscenter Immutable Quote** es un sistema avanzado de cotizaciones immutables para Magento 2 que permite crear cotizaciones que no pueden ser modificadas una vez activadas. Esto asegura la integridad de los precios y condiciones acordadas con los clientes.

## 📁 Estructura de Archivos y Propósito

### 🔗 **Api/** - Interfaces de API

#### **Data/**
- **`ImmutableQuoteInterface.php`**: Define el contrato de datos para una cotización immutable. Especifica todos los métodos getter y setter necesarios para manejar los datos de la cotización.

#### **Interfaces Principales**
- **`ImmutableQuoteManagmentInterface.php`**: Interfaz principal para operaciones de negocio de cotizaciones immutables (crear, habilitar, deshabilitar, convertir a orden).
- **`ImmutableQuoteRepositoryInterface.php`**: Interfaz completa del patrón Repository para CRUD y consultas avanzadas de cotizaciones immutables.

### 🎨 **Block/** - Bloques de Presentación

#### **Customer/**
- **`QuoteListBlock.php`**: Bloque que maneja la lógica de presentación para mostrar la lista de cotizaciones immutables del cliente en el frontend.

### 🎮 **Controller/** - Controladores

#### **Customer/**
- **`CustomerImmutableQuotesController.php`**: Controlador que maneja las solicitudes del cliente para ver sus cotizaciones immutables.
- **`EnableImmutableQuoteController.php`**: Controlador específico para que los clientes puedan activar sus cotizaciones immutables.

### ⚙️ **etc/** - Configuración del Módulo

#### **adminhtml/**
- **`system.xml`**: Configuración del panel de administración para ajustes del módulo.

#### **frontend/**
- **`ImmutableQuoteRoutes.xml`**: Define las rutas específicas del frontend para las cotizaciones immutables.

#### **Archivos de Configuración Principal**
- **`acl.xml`**: Define los permisos y recursos ACL para diferentes roles de usuario.
- **`db_schema.xml`**: Schema de base de datos que define las tablas y estructura de datos.
- **`di.xml`**: Configuración de inyección de dependencias y servicios.
- **`module.xml`**: Archivo de definición del módulo y sus dependencias.
- **`webapi.xml`**: Define los endpoints de la API REST para operaciones remotas.

### 📊 **Model/** - Modelos y Lógica de Negocio

#### **Event/** - Eventos del Dominio
- **`ImmutableQuoteCreatedEvent.php`**: Evento que se dispara cuando se crea una cotización immutable.
- **`ImmutableQuoteEnabledEvent.php`**: Evento que se dispara cuando se habilita una cotización immutable.
- **`ImmutableQuoteModificationAttemptedEvent.php`**: Evento de seguridad cuando alguien intenta modificar una cotización immutable.

#### **ResourceModel/**
- **`AuditLogResourceModel.php`**: Modelo de recursos para manejar el registro de auditoría en base de datos.

#### **Service/** - Servicios de Aplicación
- **`AuditLoggerService.php`**: Servicio para registrar todas las acciones en el sistema de auditoría.
- **`CacheServiceForImmutableQuotes.php`**: Servicio de caché especializado para optimizar las consultas de cotizaciones.
- **`QuoteValidationService.php`**: Servicio que valida si una cotización puede hacerse immutable.
- **`RateLimitingService.php`**: Servicio de rate limiting para prevenir abuso de la API.
- **Interfaces y implementaciones de validación**: Definen estrategias de validación reutilizables.

#### **Modelos Principales**
- **`CustomerQuoteManagement.php`**: Servicio orientado al cliente con autenticación y rate limiting.
- **`ImmutableQuoteManagementService.php`**: Servicio principal de gestión con arquitectura orientada a eventos.
- **`ImmutableQuoteModel.php`**: Modelo rico del dominio con reglas de negocio.
- **`ImmutableQuoteRepository.php`**: Implementación completa del patrón Repository.

### 🔌 **Plugin/** - Plugins de Interceptación

#### **Quote/**
- **`PreventQuoteModificationsPlugin.php`**: Plugin que intercepta y previene modificaciones no autorizadas a cotizaciones immutables.

### 🎨 **view/** - Vistas y Recursos Frontend

#### **frontend/**
- **`layout/ImmutableQuotesPageLayout.xml`**: Layout XML para las páginas de cotizaciones immutables.
- **`templates/ImmutableQuotesList.php`**: Template PHP para renderizar la lista de cotizaciones.

#### **web/css/**
- **`ImmutableQuotesStyles.css`**: Estilos CSS específicos para las cotizaciones immutables.

#### **web/js/**
- **`quotes-list.js`**: JavaScript para manejar la funcionalidad interactiva de la lista de cotizaciones.

### 📚 **Documentación y Configuración**

- **`ARCHITECTURE.md`**: Documentación completa de la arquitectura del sistema y decisiones de diseño.
- **`composer.json`**: Configuración del paquete Composer con dependencias y metadatos.
- **`registration.php`**: Archivo de registro del módulo en Magento 2.

## 🚀 Funcionalidades Principales

### ✅ **Para Administradores**
- Crear cotizaciones immutables desde cotizaciones existentes
- Gestionar permisos granulares mediante ACL
- Monitorear actividad mediante logs de auditoría
- Configurar rate limiting y seguridad

### ✅ **Para Clientes**
- Ver sus cotizaciones immutables disponibles
- Activar cotizaciones para uso
- Proceder al checkout con cotizaciones válidas
- Interfaz user-friendly

### ✅ **Características Técnicas**
- **API REST completa** para integración externa
- **Sistema de eventos** para extensibilidad
- **Caché multicapa** para rendimiento óptimo
- **Validación robusta** con estrategias personalizables
- **Seguridad avanzada** con rate limiting y auditoría
- **Arquitectura SOLID** con separación clara de responsabilidades

## 🔒 Seguridad y Auditoría

El módulo incluye un sistema completo de auditoría que registra:
- Todas las creaciones de cotizaciones immutables
- Habilitaciones y deshabilitaciones
- Intentos de modificación no autorizados
- Alertas de seguridad automáticas

## 🏗️ Arquitectura

El módulo sigue principios de **Clean Architecture** y **Domain Driven Design**:
- **Capa de Aplicación**: Controllers y Services
- **Capa de Dominio**: Models y Events con reglas de negocio
- **Capa de Infraestructura**: ResourceModels y external services
- **Capa de Presentación**: Blocks, Templates y JavaScript

Este diseño asegura mantenibilidad, testabilidad y extensibilidad a largo plazo.
