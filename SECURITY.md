# 🔐 Sistema de Seguridad Avanzado - WUT Admission API

Documentación completa del sistema de seguridad empresarial implementado.

---

## 📋 Índice

1. [Descripción General](#descripción-general)
2. [Autenticación y Autorización](#autenticación-y-autorización)
3. [Validación y Sanitización](#validación-y-sanitización)
4. [Rate Limiting](#rate-limiting)
5. [Detección de Fraude](#detección-de-fraude)
6. [API Keys](#api-keys)
7. [Headers de Seguridad](#headers-de-seguridad)
8. [Base de Datos](#base-de-datos)
9. [Ejemplos de Uso](#ejemplos-de-uso)
10. [Troubleshooting](#troubleshooting)

---

## 🎯 Descripción General

Este sistema implementa **5 capas de seguridad** para proteger la API contra:

- ✅ **XSS** (Cross-Site Scripting)
- ✅ **SQL Injection**
- ✅ **CSRF** (Cross-Site Request Forgery)
- ✅ **DDoS** (Distributed Denial of Service)
- ✅ **Fuerza Bruta**
- ✅ **Acceso No Autorizado**
- ✅ **Fraude y Anomalías**

### Arquitectura de Seguridad

```
┌─────────────────────────────────────────────────────────┐
│ Solicitud HTTP Entrante                                  │
└────────────────────┬────────────────────────────────────┘
                     ↓
┌──────────────────────────────────────────────────────────┐
│ 1. RequestValidationMiddleware                           │
│    - Valida método HTTP                                  │
│    - Valida Content-Type                                 │
│    - Valida tamaño (máx 10MB)                            │
│    - Detecta patrones maliciosos en URI                  │
│    - Valida headers sospechosos                          │
└────────────────────┬────────────────────────────────────┘
                     ↓
┌──────────────────────────────────────────────────────────┐
│ 2. ApiKeyMiddleware                                      │
│    - Valida X-API-Key header                             │
│    - Verifica IP permitidas/bloqueadas                   │
│    - Valida endpoints permitidos                         │
│    - Valida métodos HTTP permitidos                      │
│    - Verifica expiración de clave                        │
└────────────────────┬────────────────────────────────────┘
                     ↓
┌──────────────────────────────────────────────────────────┐
│ 3. RateLimitMiddleware                                   │
│    - Limita peticiones por IP                            │
│    - Bloquea después de 3 peticiones                     │
│    - Bloquea por 24 horas                                │
│    - Rastrea intentos de ataque                          │
└────────────────────┬────────────────────────────────────┘
                     ↓
┌──────────────────────────────────────────────────────────┐
│ 4. Action (CreateContact/CreateAdmission)                │
│    - Sanitiza entrada (Sanitizer class)                  │
│    - Detecta patrones maliciosos                         │
│    - Valida reglas de negocio                            │
│    - Logging de auditoría                                │
│    - Logs de IP/fraude                                   │
└────────────────────┬────────────────────────────────────┘
                     ↓
┌──────────────────────────────────────────────────────────┐
│ 5. SecurityHeadersMiddleware                             │
│    - CSP (Content Security Policy)                       │
│    - X-Frame-Options (Previene clickjacking)             │
│    - X-Content-Type-Options (MIME sniffing)              │
│    - HSTS (Fuerza HTTPS)                                 │
│    - Referrer-Policy                                     │
│    - Permissions-Policy                                  │
└────────────────────┬────────────────────────────────────┘
                     ↓
            ✅ Respuesta Segura
```

---

## 🔐 Autenticación y Autorización

### JWT (JSON Web Tokens)

**Para endpoints administrativos:**

```bash
curl -X GET http://localhost:8000/admin/contacts \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
```

**Middleware:** `JwtMiddleware`
**Ubicación:** `src/Middleware/JwtMiddleware.php`

### API Keys

**Para endpoints de aplicación:**

```bash
curl -X POST http://localhost:8000/contacts \
  -H "X-API-Key: sk_a1b2c3d4e5f6..." \
  -H "Content-Type: application/json" \
  -d '{"name":"John",...}'
```

**Middleware:** `ApiKeyMiddleware`
**Ubicación:** `src/Middleware/ApiKeyMiddleware.php`

---

## 📝 Validación y Sanitización

### Clase Sanitizer

**Ubicación:** `src/Shared/Sanitizer.php`

#### Métodos Principales:

```php
// Sanitizar string genérico
Sanitizer::sanitize("input", "string");

// Sanitizar email
Sanitizer::sanitize("test@example.com", "email");

// Sanitizar teléfono
Sanitizer::sanitize("1234567890", "phone");

// Sanitizar array completo
Sanitizer::sanitizeArray($data, [
    'name' => 'string',
    'email' => 'email',
    'phone' => 'phone'
]);

// Verificar si es seguro
if (!Sanitizer::isSafe($userInput)) {
    // Potencial amenaza
}

// Escapar para HTML
$safe = Sanitizer::escape($userInput);
```

#### Protecciones:

| Ataque | Detección |
|--------|-----------|
| `<script>alert('xss')</script>` | ✅ Bloqueado |
| `';DROP TABLE users;--` | ✅ Bloqueado |
| `javascript:alert('xss')` | ✅ Bloqueado |
| `<iframe src="evil.com">` | ✅ Bloqueado |
| `eval($_GET['cmd'])` | ✅ Bloqueado |
| `../../../etc/passwd` | ✅ Bloqueado |

---

## 🚀 Rate Limiting

### Configuración

**Ubicación:** `src/Shared/RateLimiter.php`

```php
const MAX_REQUESTS_PER_MINUTE = 3;      // Máx 3 peticiones
const BLOCK_DURATION_HOURS = 24;        // Bloqueo de 24h
const CLEANUP_INTERVAL_HOURS = 48;      // Limpieza de registros
```

### Límites por Endpoint

| Endpoint | Límite | Descripción |
|----------|--------|-------------|
| `/contacts` | 2 (estricto) | Más restrictivo |
| `/admissions` | 2 (estricto) | Más restrictivo |
| Otros | 3 (normal) | Límite normal |

### Flujo de Bloqueo

```
Petición 1: ✅ PERMITIDA (contador: 1)
Petición 2: ✅ PERMITIDA (contador: 2)
Petición 3: ⚠️  ÚLTIMA (mensaje de alerta)
Petición 4+: 🔴 BLOQUEADA POR 24 HORAS

Respuesta HTTP 429:
{
  "exito": false,
  "error": "Has excedido el límite de peticiones...",
  "code": "RATE_LIMIT_EXCEEDED",
  "retry_after": 86400,
  "retry_after_readable": "24 horas"
}
```

### Tabla de Datos

**Tabla:** `rate_limiting`

```sql
SELECT 
    ip_address,
    endpoint,
    request_count,
    blocked_until,
    is_blocked,
    last_request_at
FROM rate_limiting
WHERE is_blocked = TRUE
ORDER BY blocked_until DESC;
```

### APIs de Rate Limiting

```php
// Verificar límite
$result = RateLimiter::checkRateLimit($ip, $endpoint, $method);

// Ver IPs bloqueadas
$blocked = RateLimiter::getBlockedIps();

// Desbloquear IP
RateLimiter::unblock($ip, $endpoint = null);

// Estadísticas
$stats = RateLimiter::getStats();
```

---

## 🕵️ Detección de Fraude

### Clase IpIntelligence

**Ubicación:** `src/Shared/IpIntelligence.php`

#### Información Recopilada:

- 🌍 **Geolocalización**: País, ciudad, coordenadas
- 🏢 **ISP/Proveedor**: Nombre del proveedor de internet
- 🔍 **Detección de VPN**: Identifica VPN
- 🤖 **Detección de Bot**: Identifica bots y crawlers
- 📊 **Fraude Score**: Puntuación de 0-100

#### Tabla `ip_logs`

```sql
CREATE TABLE ip_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45),
    endpoint VARCHAR(255),
    method VARCHAR(10),
    country_code VARCHAR(5),
    country_name VARCHAR(100),
    isp_provider VARCHAR(255),
    is_vpn BOOLEAN,
    is_proxy BOOLEAN,
    is_bot BOOLEAN,
    fraud_score INT,
    is_suspicious BOOLEAN,
    threat_type VARCHAR(100),
    created_at TIMESTAMP
);
```

#### Cálculo de Fraude Score

```
VPN detectado:        +30 puntos
Proxy detectado:      +25 puntos
Bot detectado:        +20 puntos
País alto riesgo:     +15 puntos (Corea del Norte, Irán, etc)
Endpoint sensible:    +10 puntos (POST a /contacts)

Total máximo:         100 puntos
```

#### Interpretación del Score

| Score | Nivel | Acción |
|-------|-------|--------|
| 0-20 | ✅ Bajo | Permitir |
| 20-40 | ⚠️ Medio | Monitorear |
| 40-60 | 🟠 Alto | Revisar |
| 60-100 | 🔴 Crítico | Bloquear |

#### APIs

```php
// Obtener info de IP
$info = IpIntelligence::getIpInfo($ip);

// Registrar petición
IpIntelligence::logRequest(
    $ip, 
    $endpoint, 
    $method,
    $ipInfo,
    $userAgent,
    $apiKey
);

// Obtener logs sospechosos
$suspicious = IpIntelligence::getSuspiciousLogs(100);

// Estadísticas de fraude
$stats = IpIntelligence::getFraudStatistics();

// Incidentes por país
$byCountry = IpIntelligence::getIncidentsByCountry();
```

---

## 🔑 API Keys

### Tabla `api_keys`

```sql
CREATE TABLE api_keys (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    api_key VARCHAR(255) NOT NULL UNIQUE,
    key_name VARCHAR(100),
    client_name VARCHAR(150),
    client_email VARCHAR(150),
    is_active BOOLEAN DEFAULT TRUE,
    is_admin BOOLEAN DEFAULT FALSE,
    rate_limit INT DEFAULT 100,
    allowed_endpoints TEXT,
    allowed_methods VARCHAR(100),
    allowed_ips TEXT,
    blocked_ips TEXT,
    last_used_at TIMESTAMP,
    last_used_ip VARCHAR(45),
    expires_at TIMESTAMP,
    created_at TIMESTAMP
);
```

### Crear API Key

```bash
POST /admin/api-keys
Authorization: Bearer JWT_TOKEN
Content-Type: application/json

{
  "key_name": "Mobile App",
  "client_name": "Mi Aplicación",
  "client_email": "app@example.com",
  "description": "API Key para app móvil",
  "rate_limit": 1000,
  "allowed_endpoints": "/contacts,/admissions",
  "allowed_methods": "GET,POST",
  "expires_in_days": 90
}
```

**Respuesta:**
```json
{
  "exito": true,
  "datos": {
    "api_key": "sk_a1b2c3d4e5f6g7h8i9j0...",
    "key_name": "Mobile App",
    "mensaje": "API Key creada exitosamente",
    "expires_at": "2026-06-18 10:30:00"
  }
}
```

### Usar API Key

```bash
curl -X POST http://localhost:8000/contacts \
  -H "X-API-Key: sk_a1b2c3d4e5f6g7h8i9j0..." \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John",
    "lastname": "Doe",
    "phone": "1234567890",
    "email": "john@example.com",
    "affair": "Consulta",
    "message": "Mensaje de prueba"
  }'
```

### Listar API Keys

```bash
GET /admin/api-keys
Authorization: Bearer JWT_TOKEN
```

### Configuración Avanzada

#### Endpoints Permitidos

```
"allowed_endpoints": "*"                    # Todos
"allowed_endpoints": "/contacts"            # Solo uno
"allowed_endpoints": "/contacts,/admissions"  # Múltiples
```

#### Métodos HTTP

```
"allowed_methods": "*"                      # Todos
"allowed_methods": "GET"                    # Solo lectura
"allowed_methods": "GET,POST"               # Lectura y escritura
```

#### Restricción de IP (Whitelist)

```
"allowed_ips": "*"                          # Todas
"allowed_ips": "192.168.1.100"              # Una sola
"allowed_ips": "192.168.1.100,203.0.113.50" # Múltiples
```

#### Bloqueo de IP (Blacklist)

```
"blocked_ips": "192.168.1.50,10.0.0.1"      # Bloqueadas
```

---

## 🔒 Headers de Seguridad

### Content Security Policy (CSP)

```
Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self'; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'
```

**Protege contra:** XSS, inyección de scripts

### X-Frame-Options

```
X-Frame-Options: DENY
```

**Protege contra:** Clickjacking

### X-Content-Type-Options

```
X-Content-Type-Options: nosniff
```

**Protege contra:** MIME sniffing

### Strict-Transport-Security

```
Strict-Transport-Security: max-age=31536000; includeSubDomains; preload
```

**Protege contra:** Ataques man-in-the-middle

### Permissions-Policy

```
Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=(), usb=()
```

**Protege contra:** Acceso no autorizado a APIs del navegador

---

## 💾 Base de Datos

### Tablas Principales

#### 1. admission
- Registros de admisiones
- Campos: id, name, lastname, document, email, phone, country, city, etc.

#### 2. contact
- Solicitudes de contacto
- Campos: id, name, lastname, phone, email, affair, message

#### 3. rate_limiting
- Rastreo de solicitudes por IP
- Campos: ip_address, endpoint, request_count, blocked_until, is_blocked

#### 4. ip_logs
- Detección de fraude
- Campos: ip_address, country_code, isp_provider, fraud_score, is_suspicious

#### 5. api_keys
- Gestión de claves API
- Campos: api_key, key_name, client_name, is_active, allowed_endpoints, etc.

#### 6. api_key_logs
- Auditoría de uso de API Keys
- Campos: api_key, endpoint, method, ip_address, status_code, created_at

### Índices Principales

```sql
-- rate_limiting
INDEX idx_ip_endpoint (ip_address, endpoint)
INDEX idx_blocked (is_blocked)
INDEX idx_last_request (last_request_at)

-- ip_logs
INDEX idx_ip_address (ip_address)
INDEX idx_country (country_code)
INDEX idx_is_suspicious (is_suspicious)
INDEX idx_created_at (created_at)

-- api_keys
INDEX idx_api_key (api_key)
INDEX idx_is_active (is_active)
INDEX idx_expires_at (expires_at)

-- api_key_logs
INDEX idx_api_key (api_key)
INDEX idx_created_at (created_at)
```

---

## 📚 Ejemplos de Uso

### Ejemplo 1: Crear Contacto (Con API Key)

```bash
curl -X POST http://localhost:8000/contacts \
  -H "X-API-Key: sk_abc123..." \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Carlos",
    "lastname": "García",
    "phone": "3101234567",
    "email": "carlos@example.com",
    "affair": "Consulta de programa",
    "message": "Me interesa información sobre ingeniería"
  }'
```

**Validaciones aplicadas:**
1. ✅ RequestValidationMiddleware: Valida Content-Type, tamaño, headers
2. ✅ ApiKeyMiddleware: Valida X-API-Key es válida y activa
3. ✅ RateLimitMiddleware: Verifica límite de peticiones
4. ✅ CreateContactAction: Sanitiza entrada, valida reglas de negocio
5. ✅ IpIntelligence: Registra IP, país, calcula fraude_score
6. ✅ SecurityHeadersMiddleware: Agrega headers de seguridad

### Ejemplo 2: Intentar Ataque XSS (Será Bloqueado)

```bash
curl -X POST http://localhost:8000/contacts \
  -H "X-API-Key: sk_abc123..." \
  -H "Content-Type: application/json" \
  -d '{
    "name": "<script>alert(\"xss\")</script>",
    "lastname": "García",
    ...
  }'
```

**Respuesta:**
```json
{
  "exito": false,
  "error": "Datos inválidos detectados",
  "code": 400
}
```

**Log:**
```
Potential security threat detected in field 'name' - IP: 192.168.1.100
```

### Ejemplo 3: Exceder Rate Limit

```bash
# Petición 1 (permitida)
curl -X POST http://localhost:8000/contacts ... 
# HTTP 201 Created

# Petición 2 (permitida)
curl -X POST http://localhost:8000/contacts ...
# HTTP 201 Created

# Petición 3 (última, con advertencia)
curl -X POST http://localhost:8000/contacts ...
# HTTP 201 Created
# X-RateLimit-Message: "⚠️ Última petición permitida..."

# Petición 4 (bloqueada)
curl -X POST http://localhost:8000/contacts ...
# HTTP 429 Too Many Requests
# Retry-After: 86400
```

### Ejemplo 4: Ver Analytics de Fraude (Admin)

```bash
curl -X GET http://localhost:8000/admin/fraud/analytics \
  -H "Authorization: Bearer eyJhbGc..."
```

**Respuesta:**
```json
{
  "exito": true,
  "datos": {
    "suspicious_logs": [...],
    "statistics": {
      "total_logs": 1250,
      "suspicious_count": 42,
      "vpn_count": 18,
      "avg_fraud_score": 23.5
    },
    "incidents_by_country": [
      {
        "country_code": "CO",
        "country_name": "Colombia",
        "incident_count": 500
      }
    ]
  }
}
```

---

## 🛠️ Troubleshooting

### Problema: "X-API-Key header requerido"

**Causa:** Falta el header X-API-Key

**Solución:**
```bash
curl -X POST http://localhost:8000/contacts \
  -H "X-API-Key: sk_..." \  # Agregar este header
  ...
```

### Problema: "API Key inválida o expirada"

**Causa:** API Key no existe o ha expirado

**Solución:**
1. Verificar que la API Key sea correcta
2. Verificar que no haya expirado: `SELECT expires_at FROM api_keys WHERE api_key = '...'`
3. Crear una nueva API Key si es necesario

### Problema: "Tu IP no está autorizada"

**Causa:** IP actual no está en whitelist

**Solución:**
1. Obtener IP actual: Ver en logs o usar `curl https://api.ipify.org`
2. Actualizar `allowed_ips` en tabla api_keys:
```sql
UPDATE api_keys 
SET allowed_ips = '192.168.1.100,203.0.113.50' 
WHERE api_key = 'sk_...';
```

### Problema: "Has excedido el límite de peticiones"

**Causa:** Más de 3 peticiones por minuto

**Solución:**
1. Esperar 24 horas
2. O desbloquear IP manualmente:
```bash
POST /admin/rate-limits/unblock
Authorization: Bearer JWT_TOKEN
{
  "ip": "192.168.1.100"
}
```

### Problema: "No tienes acceso a este endpoint"

**Causa:** API Key no tiene permisos para este endpoint

**Solución:**
1. Verificar `allowed_endpoints` en tabla api_keys
2. Actualizar si es necesario:
```sql
UPDATE api_keys 
SET allowed_endpoints = '/contacts,/admissions,/registration' 
WHERE api_key = 'sk_...';
```

### Problema: "Método HTTP no permitido"

**Causa:** API Key no permite este método HTTP

**Solución:**
1. Verificar `allowed_methods`:
```sql
SELECT allowed_methods FROM api_keys WHERE api_key = 'sk_...';
```
2. Actualizar si es necesario:
```sql
UPDATE api_keys 
SET allowed_methods = 'GET,POST,PUT,DELETE' 
WHERE api_key = 'sk_...';
```

---

## 📞 Soporte y Contacto

Para reportar vulnerabilidades de seguridad o problemas:

1. **Email:** security@wut.edu.co
2. **GitHub Issues:** [Reportar en GitHub](https://github.com/wut/api-admission)
3. **Documentación:** Consultar `SECURITY.md` y `STRUCTURE.md`

---

## 📄 Licencia y Políticas

- **Licencia:** Propietaria
- **Versión:** 1.0.0
- **Última Actualización:** Marzo 18, 2026
- **Responsable:** Equipo de Seguridad WUT

---

**Protegiendo tu API con seguridad empresarial.** 🚀🛡️🔐
