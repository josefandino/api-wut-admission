# 🎓 WUT Admission API - Sistema Administrativo

![PHP](https://img.shields.io/badge/PHP-8.4-blue)
![Slim](https://img.shields.io/badge/Slim-4-green)
![MySQL](https://img.shields.io/badge/MySQL-8.0-orange)
![Security](https://img.shields.io/badge/Security-Enterprise-red)

Sistema API profesional con **5 capas de seguridad** para gestión de admisiones y contactos.

---

## 📚 Documentación Completa

- **[SECURITY.md](./SECURITY.md)** - 🔐 Sistema de seguridad avanzado (LEER PRIMERO)
- **[STRUCTURE.md](./STRUCTURE.md)** - 📐 Estructura del proyecto
- **[database.sql](./database.sql)** - 💾 Schema de base de datos

---

## 🚀 Inicio Rápido

### 1. Requisitos

```
- PHP 8.4+
- MySQL 8.0+
- Composer
- XAMPP/Apache con mod_rewrite activado
```

### 2. Instalación

```bash
# Clonar repositorio
git clone https://github.com/wut/api-admission.git
cd api-admission

# Instalar dependencias
composer install

# Copiar archivo de configuración
cp .env.example .env

# Editar .env con tus credenciales
nano .env

# Crear base de datos
mysql -u root < database.sql
```

### 3. Configuración (.env)

```env
DB_HOST=localhost
DB_PORT=3306
DB_NAME=name_db
DB_USER=root
DB_PASSWORD=

JWT_SECRET=tu_secreto_jwt_aqui_debe_ser_largo_y_seguro
API_ENV=production
APP_DEBUG=false
```

### 4. Verificar Instalación

```bash
# Verificar sintaxis PHP
php -l public/index.php

# Probar endpoint de salud
curl http://localhost:8000/
```

---

## 📋 Endpoints Disponibles

### Public Endpoints (Requieren X-API-Key)

#### Contact (Contactos)

```bash
# Crear contacto
POST /contacts
X-API-Key: sk_...
{
  "name": "Carlos",
  "lastname": "García",
  "phone": "3101234567",
  "email": "carlos@example.com",
  "affair": "Consulta",
  "message": "Mensaje de prueba"
}

# Listar contactos
GET /contacts
X-API-Key: sk_...

# Ver contacto específico
GET /contacts/{id}
X-API-Key: sk_...
```

#### Admission (Admisiones)

```bash
# Crear admisión
POST /admissions
X-API-Key: sk_...
{
  "name": "Juan",
  "lastname": "Pérez",
  "type_document": "DNI",
  "document": "1234567890",
  "email": "juan@example.com",
  "phone": "3101234567",
  "country": "Colombia",
  "city": "Bogotá",
  "address": "Calle Principal 123",
  "program": "Ingeniería de Sistemas"
}

# Listar admisiones
GET /admissions
X-API-Key: sk_...

# Ver admisión específica
GET /admissions/{id}
X-API-Key: sk_...
```

---

### Admin Endpoints (Requieren JWT Token)

#### Rate Limiting

```bash
# Ver IPs bloqueadas
GET /admin/rate-limits
Authorization: Bearer JWT_TOKEN

# Desbloquear IP
POST /admin/rate-limits/unblock
Authorization: Bearer JWT_TOKEN
{
  "ip": "192.168.1.100",
  "endpoint": "/contacts"
}
```

#### API Keys

```bash
# Listar API Keys
GET /admin/api-keys
Authorization: Bearer JWT_TOKEN

# Crear nueva API Key
POST /admin/api-keys
Authorization: Bearer JWT_TOKEN
{
  "key_name": "Mobile App",
  "client_name": "Mi Aplicación",
  "client_email": "app@example.com",
  "rate_limit": 1000,
  "allowed_endpoints": "/contacts,/admissions",
  "allowed_methods": "GET,POST",
  "expires_in_days": 90
}
```

#### Fraud Analytics

```bash
# Ver análisis de fraude
GET /admin/fraud/analytics
Authorization: Bearer JWT_TOKEN
```

---

## 🔐 Cómo Obtener API Key

### Paso 1: Obtener JWT Token (Solo Admin)

```bash
# Con credenciales admin (ver configuración)
curl -X POST http://localhost:8000/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@wut.edu.co",
    "password": "tu_contraseña"
  }'

# Respuesta:
{
  "exito": true,
  "datos": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
  }
}
```

### Paso 2: Crear API Key

```bash
curl -X POST http://localhost:8000/admin/api-keys \
  -H "Authorization: Bearer JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "key_name": "Mobile App",
    "client_name": "Aplicación Móvil",
    "client_email": "app@example.com",
    "rate_limit": 1000,
    "allowed_endpoints": "/contacts,/admissions",
    "allowed_methods": "GET,POST",
    "expires_in_days": 90
  }'

# Respuesta:
{
  "exito": true,
  "datos": {
    "api_key": "sk_a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6...",
    "key_name": "Mobile App",
    "mensaje": "API Key creada exitosamente. Guarda la clave en un lugar seguro.",
    "expires_at": "2026-06-18 10:30:00"
  }
}
```

### Paso 3: Usar API Key

```bash
curl -X POST http://localhost:8000/contacts \
  -H "X-API-Key: sk_a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6..." \
  -H "Content-Type: application/json" \
  -d '{...}'
```

---

## 🛡️ Capas de Seguridad

### 1. Validación de Solicitud (RequestValidationMiddleware)

✅ Valida método HTTP  
✅ Valida Content-Type  
✅ Limita tamaño (máx 10MB)  
✅ Detecta URI maliciosa  
✅ Valida headers sospechosos  

### 2. Autenticación de API Key (ApiKeyMiddleware)

✅ Valida X-API-Key header  
✅ Verifica IP permitidas/bloqueadas  
✅ Valida endpoints permitidos  
✅ Valida métodos HTTP permitidos  
✅ Verifica expiración de clave  

### 3. Rate Limiting (RateLimitMiddleware)

✅ Máx 3 peticiones por minuto  
✅ Bloquea por 24 horas  
✅ Rastreo por IP  
✅ Bloqueo automático  

### 4. Sanitización y Validación (Action Layer)

✅ Sanitiza entrada de usuario  
✅ Detecta patrones maliciosos  
✅ Valida reglas de negocio  
✅ Logging de auditoría  

### 5. Headers de Seguridad (SecurityHeadersMiddleware)

✅ Content Security Policy (CSP)  
✅ X-Frame-Options (Clickjacking)  
✅ X-Content-Type-Options (MIME sniffing)  
✅ HSTS (HTTPS enforcement)  
✅ Permissions-Policy  

---

## 📊 Protecciones Contra Ataques

| Ataque | Protección | Estado |
|--------|-----------|--------|
| **XSS** | Sanitización + CSP | ✅ |
| **SQL Injection** | Prepared Statements | ✅ |
| **CSRF** | CORS + Headers | ✅ |
| **DDoS** | Rate Limiting | ✅ |
| **Fuerza Bruta** | Rate Limiting | ✅ |
| **Clickjacking** | X-Frame-Options | ✅ |
| **MIME Sniffing** | X-Content-Type-Options | ✅ |
| **Fraude** | IP Intelligence | ✅ |

---

## 📁 Estructura del Proyecto

```
api-wut-admission/
├── app/                          # Configuración
│   ├── middleware.php            # Middlewares
│   ├── routes.php                # Rutas
│   ├── settings.php              # Configuración
│   └── dependencies.php          # Inyección
│
├── src/
│   ├── Application/Actions/      # Acciones/Controladores
│   │   ├── Contact/              # Acciones de contacto
│   │   ├── Admission/            # Acciones de admisión
│   │   ├── ApiKey/               # Gestión de API Keys
│   │   ├── RateLimit/            # Gestión de rate limit
│   │   └── Fraud/                # Analytics de fraude
│   │
│   ├── Domain/                   # Entidades
│   │   ├── Contact/
│   │   └── Admission/
│   │
│   ├── Infrastructure/           # Persistencia
│   │   └── Persistence/
│   │       ├── Contact/
│   │       └── Admission/
│   │
│   ├── Middleware/               # Middlewares personalizados
│   │   ├── ApiKeyMiddleware.php
│   │   ├── RateLimitMiddleware.php
│   │   ├── RequestValidationMiddleware.php
│   │   └── SecurityHeadersMiddleware.php
│   │
│   ├── Shared/                   # Utilidades compartidas
│   │   ├── Database.php
│   │   ├── Sanitizer.php
│   │   ├── RateLimiter.php
│   │   └── IpIntelligence.php
│   │
│   ├── Presentation/Routes/      # Definición de rutas
│   │   ├── ContactRoutes.php
│   │   ├── AdmissionRoutes.php
│   │   ├── ApiKeyRoutes.php
│   │   └── RateLimitRoutes.php
│   │
│   └── Routes/Routes.php         # Registro de rutas
│
├── public/
│   ├── index.php                 # Punto de entrada
│   └── .htaccess
│
├── database.sql                  # Schema de BD
├── .env.example                  # Variables de entorno
├── .gitignore
├── composer.json
├── SECURITY.md                   # Documentación de seguridad
├── STRUCTURE.md                  # Estructura del proyecto
└── README.md                     # Este archivo
```

---

## 📊 Tablas de Base de Datos

### contact (Contactos)

```sql
CREATE TABLE contact (
    id CHAR(36) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    lastname VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(150) NOT NULL,
    affair VARCHAR(100) NOT NULL,
    message VARCHAR(200) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### admission (Admisiones)

```sql
CREATE TABLE admission (
    id CHAR(36) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    lastname VARCHAR(100) NOT NULL,
    type_document VARCHAR(50) NOT NULL,
    document VARCHAR(25) NOT NULL UNIQUE,
    phone VARCHAR(20),
    email VARCHAR(150) NOT NULL,
    country VARCHAR(100),
    city VARCHAR(100),
    address TEXT,
    program VARCHAR(150),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### rate_limiting (Control de Rate Limit)

```sql
CREATE TABLE rate_limiting (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    ip_address VARCHAR(45) NOT NULL UNIQUE,
    endpoint VARCHAR(255),
    request_count INT,
    blocked_until TIMESTAMP,
    is_blocked BOOLEAN,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### ip_logs (Detección de Fraude)

```sql
CREATE TABLE ip_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### api_keys (API Keys Management)

```sql
CREATE TABLE api_keys (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    api_key VARCHAR(255) NOT NULL UNIQUE,
    key_name VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    is_admin BOOLEAN DEFAULT FALSE,
    rate_limit INT DEFAULT 100,
    allowed_endpoints TEXT,
    allowed_methods VARCHAR(100),
    allowed_ips TEXT,
    blocked_ips TEXT,
    expires_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

## 🧪 Testing

```bash
# Crear contacto con curl
curl -X POST http://localhost:8000/contacts \
  -H "X-API-Key: sk_test123..." \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test",
    "lastname": "User",
    "phone": "1234567890",
    "email": "test@example.com",
    "affair": "Testing",
    "message": "This is a test message"
  }'

# Ver todos los contactos
curl -X GET http://localhost:8000/contacts \
  -H "X-API-Key: sk_test123..."

# Ver contacto específico
curl -X GET http://localhost:8000/contacts/123e4567-e89b-12d3-a456-426614174000 \
  -H "X-API-Key: sk_test123..."
```

---

## 🔧 Configuración Avanzada

### Cambiar Límite de Rate Limiting

**Archivo:** `src/Shared/RateLimiter.php`

```php
const MAX_REQUESTS_PER_MINUTE = 3;      // Cambiar aquí
const BLOCK_DURATION_HOURS = 24;        // O aquí
```

### Modificar Endpoints Excluidos

**Archivo:** `src/Middleware/ApiKeyMiddleware.php`

```php
const PUBLIC_ENDPOINTS = [
    '/',
    '/health',
    '/status',
    '/ping',
];
```

### Configurar Endpoints Estrictos

**Archivo:** `src/Shared/RateLimiter.php`

```php
const STRICT_ENDPOINTS = [
    '/contacts',        // Máx 2 peticiones
    '/admissions',
    '/registration',
];
```

---

## 📝 Validaciones de Campos

### Contact

| Campo | Tipo | Requerido | Validación |
|-------|------|-----------|-----------|
| name | String | ✅ | 3-100 caracteres |
| lastname | String | ✅ | 3-100 caracteres |
| phone | String | ✅ | 7-20 caracteres |
| email | String | ✅ | Email válido |
| affair | String | ✅ | 3-100 caracteres |
| message | String | ✅ | 5-200 caracteres |

### Admission

| Campo | Tipo | Requerido | Validación |
|-------|------|-----------|-----------|
| name | String | ✅ | 3-100 caracteres |
| lastname | String | ✅ | 3-100 caracteres |
| type_document | String | ✅ | 1-50 caracteres |
| document | String | ✅ | 5-25 caracteres, UNIQUE |
| phone | String | ❌ | 7-20 caracteres |
| email | String | ✅ | Email válido |
| country | String | ❌ | 2-100 caracteres |
| city | String | ❌ | 2-100 caracteres |
| address | String | ❌ | 5-500 caracteres |
| program | String | ❌ | 3-150 caracteres |

---

## 🐛 Debugging

### Ver Logs

```bash
# Logs de error (Apache)
tail -f /var/log/apache2/error.log

# Logs de aplicación
tail -f storage/logs/app.log
```

### Modo Debug

En `.env`:
```env
APP_DEBUG=true
```

### Ver Headers de Respuesta

```bash
curl -i -X GET http://localhost:8000/contacts \
  -H "X-API-Key: sk_..."
```

---

## 📚 Recursos Adicionales

- **[PHP Documentation](https://www.php.net/docs.php)**
- **[Slim Framework](https://www.slimframework.com/)**
- **[OWASP Security](https://owasp.org/)**
- **[MySQL Documentation](https://dev.mysql.com/doc/)**

---

## 🤝 Contribuciones

Las contribuciones son bienvenidas. Por favor:

1. Fork el repositorio
2. Crear rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit cambios (`git commit -m 'Add AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abrir Pull Request

---

## 📄 Licencia

Este proyecto es propiedad de **WUT - World University of Technology**

---

## 📧 Soporte

- **Email:** support@wut.edu.co
- **Teléfono:** +57 1 234 5678
- **Website:** https://www.wut.edu.co

---

## ⚠️ Seguridad

**IMPORTANTE:** Nunca compartas tus API Keys públicamente. Si sospechas que una clave ha sido comprometida:

1. Desactívala inmediatamente en el panel admin
2. Reporta el incidente a security@wut.edu.co
3. Genera una nueva clave

Lee [SECURITY.md](./SECURITY.md) para más información sobre seguridad.

---

**Última actualización:** Marzo 18, 2026  
**Versión:** 1.0.0  
**Autor:** Equipo de Desarrollo WUT

🚀 **Protegiendo tu API con seguridad empresarial.**
