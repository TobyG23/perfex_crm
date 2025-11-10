# 📡 Instalación API Backend - Intergraphics CRM

Guía completa para configurar el API REST en Perfex CRM

## 📋 Requisitos

- Perfex CRM instalado y funcionando
- PHP >= 7.4
- MySQL/MariaDB
- Acceso FTP/SSH al servidor
- mod_rewrite habilitado en Apache (o nginx configurado)

## 🚀 Instalación Paso a Paso

### 1. Verificar Instalación de Perfex

Asegúrate de que Perfex CRM esté instalado y funcionando correctamente en:
```
https://tu-dominio.com/
```

### 2. Copiar el Archivo API

El archivo API ya está incluido en:
```
perfex_crm/application/controllers/api/Api.php
```

Este archivo contiene todos los endpoints necesarios para la app móvil.

### 3. Configurar .htaccess

Verifica que el archivo `.htaccess` en la raíz de Perfex contenga:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php/$1 [L]
```

### 4. Configurar CORS (si es necesario)

El archivo `Api.php` ya incluye headers CORS:

```php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
```

Para producción, es recomendable cambiar `*` por el dominio específico de tu app.

### 5. Verificar Permisos

Asegúrate de que el directorio tenga los permisos correctos:

```bash
chmod 755 application/controllers/api
chmod 644 application/controllers/api/Api.php
```

## 🧪 Probar la API

### Test de Login

```bash
curl -X POST https://tu-dominio.com/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@tudominio.com",
    "password": "tu_password"
  }'
```

Respuesta esperada:
```json
{
  "success": true,
  "data": {
    "token": "1",
    "user": {
      "id": 1,
      "firstname": "Admin",
      "lastname": "User",
      "email": "admin@tudominio.com",
      "role": "admin"
    }
  }
}
```

### Test de Dashboard (requiere autenticación)

```bash
curl -X GET https://tu-dominio.com/api/dashboard \
  -H "Authorization: Bearer 1"
```

## 📚 Endpoints Disponibles

### Autenticación

#### POST /api/login
Login de usuario

**Request:**
```json
{
  "email": "usuario@ejemplo.com",
  "password": "contraseña"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "token": "123",
    "user": { ... }
  }
}
```

---

### Dashboard

#### GET /api/dashboard
Obtener estadísticas del dashboard

**Headers:** `Authorization: Bearer {token}`

**Response:**
```json
{
  "success": true,
  "data": {
    "total_clients": 50,
    "total_projects": 25,
    "total_invoices": 100,
    "open_tickets": 5,
    "recent_projects": [...],
    "recent_tickets": [...]
  }
}
```

---

### Proyectos

#### GET /api/projects
Listar proyectos

**Parámetros de Query:**
- `status` (opcional): Filtrar por estado
- `limit` (opcional): Límite de resultados (default: 20)
- `page` (opcional): Página (default: 1)

#### GET /api/projects/:id
Detalle de un proyecto específico

**Response incluye:**
- Información del proyecto
- Tareas asociadas
- Miembros del equipo
- Archivos adjuntos

---

### Tareas

#### GET /api/tasks
Listar tareas

**Parámetros:**
- `status`: Filtrar por estado
- `project_id`: Filtrar por proyecto
- `assigned_to_me`: Solo tareas asignadas al usuario

#### GET /api/tasks/:id
Detalle de una tarea

---

### Facturas

#### GET /api/invoices
Listar facturas

**Parámetros:**
- `status`: Filtrar por estado (1=Pendiente, 2=Pagada, etc.)
- `client_id`: Filtrar por cliente

#### GET /api/invoices/:id
Detalle de factura con items y pagos

---

### Propuestas

#### GET /api/proposals
Listar propuestas

#### GET /api/proposals/:id
Detalle de propuesta

#### POST /api/proposals/:id/accept
Aceptar una propuesta

#### POST /api/proposals/:id/decline
Rechazar una propuesta

**Body (opcional):**
```json
{
  "reason": "Razón del rechazo"
}
```

---

### Archivos

#### GET /api/files
Listar archivos y diseños

**Parámetros:**
- `rel_type`: Tipo de relación (project, task, etc.)
- `rel_id`: ID de la relación

---

### Clientes

#### GET /api/clients
Listar clientes

**Parámetros:**
- `search`: Búsqueda por nombre

#### GET /api/clients/:id
Detalle de cliente con proyectos y facturas

---

## 🔐 Seguridad

### Sistema de Autenticación

La API utiliza un sistema simple de Bearer Token:

1. Usuario hace login con email/password
2. API retorna `token` (en este caso, el staffid del usuario)
3. Cada request debe incluir: `Authorization: Bearer {token}`
4. API verifica el token antes de procesar requests

### Mejoras de Seguridad Recomendadas

Para producción, considera:

1. **JWT Tokens**: Implementar tokens JWT con expiración
2. **Rate Limiting**: Limitar requests por IP
3. **HTTPS**: Usar siempre HTTPS en producción
4. **Validación de Input**: Validar y sanitizar todos los inputs
5. **Logs de Auditoría**: Registrar accesos y cambios

## 🐛 Troubleshooting

### Error 404 en API

**Problema:** `/api/login` retorna 404

**Solución:**
1. Verificar que mod_rewrite esté habilitado
2. Verificar .htaccess
3. Verificar que la ruta del controlador sea correcta

### Error CORS

**Problema:** Error de CORS en el navegador

**Solución:**
Agregar el dominio específico en los headers:
```php
header('Access-Control-Allow-Origin: https://tu-app-movil.com');
```

### Error 401 Unauthorized

**Problema:** Token no válido

**Solución:**
1. Verificar que el token se esté enviando correctamente
2. Verificar que el usuario exista y esté activo
3. Limpiar localStorage en la app móvil

### Error 500

**Problema:** Error interno del servidor

**Solución:**
1. Revisar logs de PHP: `application/logs/`
2. Habilitar display_errors en desarrollo
3. Verificar permisos de archivos

## 📊 Monitoring

### Logs

Los logs se guardan en:
```
application/logs/log-YYYY-MM-DD.php
```

### Debugging

Para habilitar debugging en desarrollo:

```php
// En config.php
define('ENVIRONMENT', 'development');
```

## 🔄 Actualizaciones

Al actualizar Perfex CRM:

1. Hacer backup del archivo `Api.php`
2. Actualizar Perfex normalmente
3. Restaurar `Api.php`
4. Verificar compatibilidad

## 📞 Soporte

Si encuentras problemas:

1. Revisa los logs de PHP
2. Verifica la configuración de Apache/Nginx
3. Prueba los endpoints con Postman
4. Contacta al equipo de desarrollo

---

**Importante:** Este API está diseñado específicamente para la app móvil de Intergraphics. No debe ser usado como API pública sin las medidas de seguridad adicionales apropiadas.
