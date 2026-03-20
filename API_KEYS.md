# 🔑 Gestión de API Keys - Guía Completa

Sistema profesional para generar, administrar y revocar claves API.

---

## 📋 Tabla de Contenidos

1. [Conceptos Básicos](#conceptos-básicos)
2. [Crear API Key](#crear-api-key)
3. [Usar API Key](#usar-api-key)
4. [Configuración Avanzada](#configuración-avanzada)
5. [Seguridad](#seguridad)
6. [Troubleshooting](#troubleshooting)
7. [Queries SQL](#queries-sql)

---

## 🎯 Conceptos Básicos

### ¿Qué es una API Key?

Una API Key es una cadena única que actúa como **contraseña** para acceder a la API. 

**Formato:**
```
sk_a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6...
     ↑
    "sk_" = Socket Key (identificador)
```

### Diferencia JWT vs API Key

| Característica | JWT | API Key |
|---|---|---|
| **Uso** | Autenticación de usuario | Acceso a aplicación |
| **Lifetime** | Corto (minutos/horas) | Largo (meses) |
| **Header** | Authorization | X-API-Key |
| **Mejor para** | Admin panel | Aplicaciones externas |

---

## 🔐 Crear API Key

### Requisito: Token JWT Admin

Primero obtener JWT token de administrador:

```bash
curl -X POST http://localhost:8000/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@wut.edu.co",
    "password": "tu_contraseña_admin"
  }'

# Respuesta:
{
  "exito": true,
  "datos": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "expires_in": 3600
  }
}
```

Guardar el `token` para los siguientes pasos.

### Crear API Key Básica

```bash
curl -X POST http://localhost:8000/admin/api-keys \
  -H "Authorization: Bearer eyJhbGc..." \
  -H "Content-Type: application/json" \
  -d '{
    "key_name": "Mi Primera API Key"
  }'

# Respuesta:
{
  "exito": true,
  "datos": {
    "api_key": "sk_a1b2c3d4e5f6...",
    "key_name": "Mi Primera API Key",
    "mensaje": "API Key creada exitosamente. Guarda la clave en un lugar seguro.",
    "expires_at": null
  }
}
```

### Crear API Key Avanzada (Recomendado)

```bash
curl -X POST http://localhost:8000/admin/api-keys \
  -H "Authorization: Bearer eyJhbGc..." \
  -H "Content-Type: application/json" \
  -d '{
    "key_name": "Mobile App v2.0",
    "client_name": "Aplicación Móvil",
    "client_email": "dev-team@mycompany.com",
    "client_phone": "+57 3001234567",
    "description": "API Key para la aplicación móvil iOS y Android",
    "rate_limit": 5000,
    "rate_limit_window": "hourly",
    "allowed_endpoints": "/contacts,/admissions",
    "allowed_methods": "GET,POST,PUT",
    "allowed_ips": "192.168.1.100,203.0.113.50",
    "expires_in_days": 365
  }'

# Respuesta completa:
{
  "exito": true,
  "datos": {
    "api_key": "sk_a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6...",
    "key_name": "Mobile App v2.0",
    "mensaje": "API Key creada exitosamente. Guarda la clave en un lugar seguro.",
    "expires_at": "2027-03-18 10:30:00"
  }
}
```

---

## 🚀 Usar API Key

### Solicitud Básica

```bash
curl -X POST http://localhost:8000/contacts \
  -H "X-API-Key: sk_a1b2c3d4e5f6..." \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Carlos",
    "lastname": "García",
    "phone": "3101234567",
    "email": "carlos@example.com",
    "affair": "Consulta",
    "message": "Mensaje de prueba"
  }'
```

### Con cURL en Script Bash

```bash
#!/bin/bash

API_KEY="sk_a1b2c3d4e5f6..."
ENDPOINT="http://localhost:8000"

# Crear contacto
curl -X POST "${ENDPOINT}/contacts" \
  -H "X-API-Key: ${API_KEY}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John",
    "lastname": "Doe",
    "phone": "1234567890",
    "email": "john@example.com",
    "affair": "Support",
    "message": "Help needed"
  }'

# Listar contactos
curl -X GET "${ENDPOINT}/contacts" \
  -H "X-API-Key: ${API_KEY}"
```

### Con Python

```python
import requests
import json

API_KEY = "sk_a1b2c3d4e5f6..."
BASE_URL = "http://localhost:8000"

headers = {
    "X-API-Key": API_KEY,
    "Content-Type": "application/json"
}

# Crear contacto
data = {
    "name": "Carlos",
    "lastname": "García",
    "phone": "3101234567",
    "email": "carlos@example.com",
    "affair": "Consulta",
    "message": "Mensaje de prueba"
}

response = requests.post(
    f"{BASE_URL}/contacts",
    headers=headers,
    json=data
)

print(response.json())

# Listar contactos
response = requests.get(
    f"{BASE_URL}/contacts",
    headers=headers
)

print(response.json())
```

### Con JavaScript/Node.js

```javascript
const API_KEY = "sk_a1b2c3d4e5f6...";
const BASE_URL = "http://localhost:8000";

const headers = {
  "X-API-Key": API_KEY,
  "Content-Type": "application/json"
};

// Crear contacto
const data = {
  name: "Carlos",
  lastname: "García",
  phone: "3101234567",
  email: "carlos@example.com",
  affair: "Consulta",
  message: "Mensaje de prueba"
};

fetch(`${BASE_URL}/contacts`, {
  method: "POST",
  headers: headers,
  body: JSON.stringify(data)
})
  .then(response => response.json())
  .then(data => console.log(data));

// Listar contactos
fetch(`${BASE_URL}/contacts`, {
  method: "GET",
  headers: headers
})
  .then(response => response.json())
  .then(data => console.log(data));
```

---

## ⚙️ Configuración Avanzada

### 1. Restricción de Endpoints

**Permitir todos los endpoints:**
```
"allowed_endpoints": "*"
```

**Permitir endpoints específicos:**
```
"allowed_endpoints": "/contacts,/admissions"
```

**Permitir solo lectura:**
```
"allowed_methods": "GET"
"allowed_endpoints": "/contacts,/admissions"
```

### 2. Restricción de Métodos HTTP

```
"allowed_methods": "*"                    # Todos
"allowed_methods": "GET,POST"             # Lectura y creación
"allowed_methods": "GET"                  # Solo lectura
"allowed_methods": "POST,PUT,DELETE"      # Solo escritura
```

### 3. Restricción de IP (Whitelist)

```
"allowed_ips": "*"                        # Todas las IPs
"allowed_ips": "192.168.1.100"            # Una sola IP
"allowed_ips": "192.168.1.100,203.0.113.50"  # Múltiples
```

### 4. Bloqueo de IP (Blacklist)

```
"blocked_ips": "192.168.1.50,10.0.0.1"
```

### 5. Rate Limiting

```
"rate_limit": 100                         # Máx 100 peticiones
"rate_limit_window": "hourly"             # Por hora
```

### 6. Expiración

```
"expires_in_days": 90                     # Expira en 90 días
"expires_in_days": 365                    # Expira en 1 año
"expires_in_days": null                   # Nunca expira
```

---

## 🔒 Seguridad

### Mejores Prácticas

#### 1. Nunca Compartas Públicamente

❌ **Malo:**
```bash
# En GitHub público
curl -H "X-API-Key: sk_abc123..." https://api.wut.edu.co/contacts
```

✅ **Bueno:**
```bash
# Usar variable de entorno
curl -H "X-API-Key: ${API_KEY}" https://api.wut.edu.co/contacts
```

#### 2. Usar Variables de Entorno

```bash
# En .env
API_KEY="sk_a1b2c3d4e5f6..."

# En bash
export API_KEY="sk_a1b2c3d4e5f6..."
source .env

# Usar en script
curl -H "X-API-Key: ${API_KEY}" ...
```

#### 3. Rotar Claves Regularmente

- Crear nueva API Key cada 90 días
- Desactivar clave antigua
- Informar a clientes sobre la rotación

#### 4. Usar Whitelisting de IP

```bash
# En lugar de permitir todas las IPs
"allowed_ips": "192.168.1.100"  # Específica para tu aplicación
```

#### 5. Registrar Acceso

Ver logs en tabla `api_key_logs`:
```sql
SELECT * FROM api_key_logs 
WHERE api_key = 'sk_...' 
ORDER BY created_at DESC;
```

#### 6. Monitorear Uso Anómalo

```bash
# Ver si API Key se usa desde IP inesperada
curl -X GET http://localhost:8000/admin/fraud/analytics \
  -H "Authorization: Bearer JWT_TOKEN"
```

---

## 🆘 Troubleshooting

### Problema: "X-API-Key header requerido"

**Causa:** No envió el header X-API-Key

**Solución:**
```bash
# Agregar header
curl -H "X-API-Key: sk_..." ...
```

### Problema: "API Key inválida o expirada"

**Causa:** 
- API Key no existe
- API Key ha expirado
- Typo en la clave

**Solución:**
```bash
# Verificar que la clave sea correcta
mysql> SELECT api_key, is_active, expires_at 
       FROM api_keys 
       WHERE api_key LIKE 'sk_%' 
       LIMIT 5;

# Si expiró, crear nueva clave
```

### Problema: "Tu IP no está autorizada"

**Causa:** IP actual no está en whitelist

**Solución:**
```bash
# Obtener IP actual
curl https://api.ipify.org

# Actualizar allowed_ips
mysql> UPDATE api_keys 
       SET allowed_ips = '192.168.1.100,203.0.113.50' 
       WHERE api_key = 'sk_...';
```

### Problema: "No tienes acceso a este endpoint"

**Causa:** API Key no permite este endpoint

**Solución:**
```bash
# Ver endpoints permitidos
mysql> SELECT allowed_endpoints 
       FROM api_keys 
       WHERE api_key = 'sk_...';

# Actualizar si es necesario
mysql> UPDATE api_keys 
       SET allowed_endpoints = '/contacts,/admissions,/registration' 
       WHERE api_key = 'sk_...';
```

### Problema: "Método HTTP no permitido"

**Causa:** API Key no permite este método

**Solución:**
```bash
# Ver métodos permitidos
mysql> SELECT allowed_methods 
       FROM api_keys 
       WHERE api_key = 'sk_...';

# Actualizar
mysql> UPDATE api_keys 
       SET allowed_methods = 'GET,POST,PUT,DELETE' 
       WHERE api_key = 'sk_...';
```

---

## 📊 Queries SQL

### Ver todas las API Keys

```sql
SELECT 
  id,
  api_key AS 'Clave (ocultada)',
  key_name,
  client_name,
  is_active,
  rate_limit,
  expires_at,
  created_at
FROM api_keys
ORDER BY created_at DESC;
```

### Desactivar una API Key

```sql
UPDATE api_keys 
SET is_active = FALSE 
WHERE api_key = 'sk_a1b2c3d4e5f6...';
```

### Activar una API Key

```sql
UPDATE api_keys 
SET is_active = TRUE 
WHERE api_key = 'sk_a1b2c3d4e5f6...';
```

### Ver uso de API Key

```sql
SELECT 
  api_key,
  endpoint,
  method,
  ip_address,
  status_code,
  COUNT(*) as total_requests
FROM api_key_logs
WHERE api_key = 'sk_a1b2c3d4e5f6...'
GROUP BY endpoint, method
ORDER BY total_requests DESC;
```

### Ver últimas peticiones

```sql
SELECT 
  api_key,
  endpoint,
  method,
  ip_address,
  status_code,
  created_at
FROM api_key_logs
WHERE api_key = 'sk_a1b2c3d4e5f6...'
ORDER BY created_at DESC
LIMIT 20;
```

### Cambiar rate limit

```sql
UPDATE api_keys 
SET rate_limit = 5000 
WHERE api_key = 'sk_a1b2c3d4e5f6...';
```

### Ver claves a punto de expirar

```sql
SELECT 
  api_key,
  key_name,
  client_name,
  expires_at,
  DATEDIFF(expires_at, NOW()) as dias_restantes
FROM api_keys
WHERE expires_at IS NOT NULL
  AND expires_at < DATE_ADD(NOW(), INTERVAL 7 DAY)
ORDER BY expires_at ASC;
```

### Expirar clave manualmente

```sql
UPDATE api_keys 
SET expires_at = NOW() 
WHERE api_key = 'sk_a1b2c3d4e5f6...';
```

---

## 📈 Estadísticas

### Peticiones por endpoint

```sql
SELECT 
  endpoint,
  COUNT(*) as total,
  SUM(CASE WHEN success=1 THEN 1 ELSE 0 END) as exitosas,
  SUM(CASE WHEN success=0 THEN 1 ELSE 0 END) as fallidas,
  AVG(response_time) as tiempo_promedio
FROM api_key_logs
GROUP BY endpoint
ORDER BY total DESC;
```

### Top clientes por peticiones

```sql
SELECT 
  a.key_name,
  a.client_name,
  a.client_email,
  COUNT(l.id) as total_peticiones,
  MAX(l.created_at) as ultima_peticion
FROM api_keys a
LEFT JOIN api_key_logs l ON a.api_key = l.api_key
GROUP BY a.api_key
ORDER BY total_peticiones DESC
LIMIT 10;
```

---

## 🔗 Enlaces Útiles

- [Documentación de Seguridad](./SECURITY.md)
- [README Principal](./README.md)
- [Estructura del Proyecto](./STRUCTURE.md)

---

**Última actualización:** Marzo 18, 2026  
**Versión:** 1.0.0

🚀 **Gestión profesional de API Keys**
