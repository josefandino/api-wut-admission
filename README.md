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
