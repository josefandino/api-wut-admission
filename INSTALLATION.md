# 🔧 Guía de Instalación y Configuración

Guía paso a paso para instalar y configurar el sistema.

---

## 📋 Requisitos Previos

### Sistema Operativo
- Linux (Ubuntu, CentOS, Debian) - RECOMENDADO
- macOS
- Windows (con XAMPP/WAMP)

### Software Requerido
- **PHP 8.4+** (con extensiones: json, pdo, pdo_mysql, curl, mbstring)
- **MySQL 8.0+** (o MariaDB 10.5+)
- **Composer** (gestor de dependencias PHP)
- **Git** (opcional pero recomendado)

### Verificar Requisitos

```bash
# Verificar PHP
php -v
# Salida esperada: PHP 8.4.0 o superior

# Verificar MySQL
mysql --version
# Salida esperada: mysql Ver 8.0.x o MariaDB 10.5.x

# Verificar Composer
composer --version
# Salida esperada: Composer version 2.x
```

---

## 📦 Instalación Paso a Paso

### Paso 1: Clonar o Descargar Repositorio

```bash
# Opción A: Con Git
git clone https://github.com/wut/api-admission.git
cd api-admission

# Opción B: Descargar ZIP manualmente
unzip api-admission.zip
cd api-admission
```

### Paso 2: Instalar Dependencias PHP

```bash
composer install

# Si necesitas actualizar dependencias
composer update
```

**Tiempo esperado:** 2-5 minutos

### Paso 3: Copiar Variables de Entorno

```bash
cp .env.example .env
```

### Paso 4: Configurar Archivo .env

```bash
# Editar con tu editor preferido
nano .env
# o
vim .env
```

**Contenido de .env:**
```env
# Base de Datos
DB_HOST=localhost          # Host MySQL
DB_PORT=3306              # Puerto MySQL
DB_NAME=db_wut_admissions # Nombre BD
DB_USER=root              # Usuario MySQL
DB_PASSWORD=              # Contraseña (dejar vacío si no hay)

# JWT (Solo para admin)
JWT_SECRET=tu_secreto_jwt_super_largo_y_seguro_123456789

# Aplicación
APP_ENV=production        # production o development
APP_DEBUG=false           # true o false
APP_NAME="WUT Admission API"

# Logging
LOG_LEVEL=info           # debug, info, warning, error
```

### Paso 5: Crear Base de Datos

```bash
# Opción A: Importar archivo SQL
mysql -u root -p < database.sql

# Opción B: Ejecutar manualmente
mysql -u root -p
> CREATE DATABASE db_wut_admissions;
> USE db_wut_admissions;
> [Copiar y pegar contenido de database.sql]
```

### Paso 6: Verificar Permisos

```bash
# Dar permisos de lectura/escritura
chmod -R 755 src/
chmod -R 755 public/
chmod -R 755 storage/
chmod -R 755 logs/

# En XAMPP/Windows, asegurar que Apache tenga acceso
```

### Paso 7: Configurar Apache (.htaccess)

El archivo `.htaccess` ya está configurado. Verificar que Apache tenga habilitado `mod_rewrite`:

```bash
# En Linux
sudo a2enmod rewrite
sudo systemctl restart apache2

# En XAMPP (Windows)
# Editar httpd.conf y descomentar:
# LoadModule rewrite_module modules/mod_rewrite.so
```

### Paso 8: Probar Instalación

```bash
# Verificar sintaxis PHP
php -l public/index.php

# Probar endpoint
curl http://localhost:8000/

# Respuesta esperada:
# Hello world!
```

---

## ✅ Verificación de Instalación

### 1. Verificar Conexión a BD

```bash
mysql -u root -p db_wut_admissions
mysql> SHOW TABLES;
```

**Tablas esperadas:**
- admission
- contact
- rate_limiting
- ip_logs
- api_keys
- api_key_logs

### 2. Verificar Directorios

```bash
# Verificar estructura
ls -la

# Debe contener:
# - src/
# - public/
# - app/
# - vendor/
# - database.sql
# - .env
```

### 3. Verificar Composer

```bash
composer validate
# Salida esperada: valid
```

---

## 🚀 Ejecución del Servidor

### Opción 1: PHP Built-in Server (Desarrollo)

```bash
# Iniciar servidor
php -S localhost:8000 -t public/

# Verificar
curl http://localhost:8000/
```

### Opción 2: Apache (Producción)

#### En Linux

```bash
# Crear virtual host
sudo nano /etc/apache2/sites-available/admission.conf
```

**Contenido:**
```apache
<VirtualHost *:80>
    ServerName api.wut.local
    DocumentRoot /var/www/html/api-admission/public

    <Directory /var/www/html/api-admission/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/admission-error.log
    CustomLog ${APACHE_LOG_DIR}/admission-access.log combined
</VirtualHost>
```

```bash
# Habilitar sitio
sudo a2ensite admission.conf
sudo a2enmod rewrite

# Reiniciar Apache
sudo systemctl restart apache2

# Editar hosts
sudo nano /etc/hosts
# Agregar: 127.0.0.1 api.wut.local
```

#### En XAMPP (Windows)

```bash
# Copiar proyecto a C:\xampp\htdocs\api-admission\

# Editar C:\xampp\apache\conf\extra\httpd-vhosts.conf
<VirtualHost *:80>
    ServerName api.wut.local
    DocumentRoot "C:/xampp/htdocs/api-admission/public"
    
    <Directory "C:/xampp/htdocs/api-admission/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>

# Editar C:\Windows\System32\drivers\etc\hosts
# Agregar: 127.0.0.1 api.wut.local

# Reiniciar Apache desde XAMPP Control Panel
```

### Opción 3: Docker (Recomendado)

```bash
# Construir imagen
docker build -t wut-admission:1.0 .

# Ejecutar contenedor
docker run -d \
  -p 8000:8000 \
  -e DB_HOST=mysql \
  -e DB_USER=root \
  -e DB_PASSWORD=root \
  -e JWT_SECRET=tu_secreto \
  --name admission-api \
  wut-admission:1.0

# Verificar
curl http://localhost:8000/
```

---

## 🗄️ Administración de Base de Datos

### Crear Usuario MySQL

```bash
mysql -u root -p

> CREATE USER 'wut_user'@'localhost' IDENTIFIED BY 'secure_password_123';
> GRANT ALL PRIVILEGES ON db_wut_admissions.* TO 'wut_user'@'localhost';
> FLUSH PRIVILEGES;
```

### Backup de Base de Datos

```bash
# Crear backup
mysqldump -u root -p db_wut_admissions > backup_2026_03_18.sql

# Restaurar backup
mysql -u root -p db_wut_admissions < backup_2026_03_18.sql
```

### Ver Tamaño de BD

```bash
mysql -u root -p

> SELECT 
    table_name, 
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
  FROM information_schema.TABLES 
  WHERE table_schema = 'db_wut_admissions';
```

---

## 🔐 Configuración de Seguridad

### 1. Cambiar Contraseña MySQL

```bash
# Usuario root
mysqladmin -u root -p password nueva_contraseña

# O crear usuario sin privilegios root
mysql -u root -p
> CREATE USER 'api_user'@'localhost' IDENTIFIED BY 'strong_password_123';
> GRANT SELECT, INSERT, UPDATE, DELETE ON db_wut_admissions.* TO 'api_user'@'localhost';
> FLUSH PRIVILEGES;
```

### 2. Generar JWT Secret

```bash
# Opción 1: Con openssl
openssl rand -base64 32

# Opción 2: Con PHP
php -r "echo bin2hex(random_bytes(32));"

# Copiar resultado a .env
JWT_SECRET=...
```

### 3. Configurar HTTPS

```bash
# Generar certificado autofirmado
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout /etc/ssl/private/server.key \
  -out /etc/ssl/certs/server.crt

# En Apache
sudo a2enmod ssl
```

### 4. Habilitar CORS (si es necesario)

Editar `src/Presentation/Routes/ContactRoutes.php`:

```php
$app->options('/{routes:.*}', function ($request, $response) {
    return $response
        ->withHeader('Access-Control-Allow-Origin', 'https://tudominio.com')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, X-API-Key, Authorization');
});
```

---

## 🧪 Pruebas Post-Instalación

### Test 1: Crear API Key

```bash
# 1. Obtener JWT (requiere usuario admin)
curl -X POST http://localhost:8000/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@wut.edu.co",
    "password": "admin123"
  }'

# 2. Copiar token de respuesta

# 3. Crear API Key
curl -X POST http://localhost:8000/admin/api-keys \
  -H "Authorization: Bearer TOKEN_AQUI" \
  -H "Content-Type: application/json" \
  -d '{
    "key_name": "Test Key"
  }'

# 4. Copiar api_key de respuesta
```

### Test 2: Crear Contacto

```bash
curl -X POST http://localhost:8000/contacts \
  -H "X-API-Key: sk_..." \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test",
    "lastname": "User",
    "phone": "1234567890",
    "email": "test@example.com",
    "affair": "Testing",
    "message": "This is a test message for the system"
  }'

# Respuesta esperada:
# HTTP 201 Created
# {
#   "exito": true,
#   "datos": {
#     "id": "...",
#     "mensaje": "Contacto creado exitosamente"
#   }
# }
```

### Test 3: Rate Limiting

```bash
# Hacer 4 peticiones rápidas (la 4ta debe ser bloqueada)
for i in {1..4}; do
  echo "Petición $i:"
  curl -X POST http://localhost:8000/contacts \
    -H "X-API-Key: sk_..." \
    -H "Content-Type: application/json" \
    -d '{...}'
  echo ""
  sleep 0.1
done

# Petición 4 debe retornar HTTP 429
```

---

## 📊 Monitoreo

### Logs

```bash
# Ver últimas líneas de log
tail -f /var/log/apache2/error.log

# Ver logs de PHP-FPM
tail -f /var/log/php-fpm.log
```

### Estadísticas de BD

```bash
# Conexiones activas
mysql -u root -p -e "SHOW PROCESSLIST;"

# Variables del servidor
mysql -u root -p -e "SHOW VARIABLES LIKE 'max_connections';"

# Status del servidor
mysql -u root -p -e "SHOW STATUS;"
```

---

## 🆘 Troubleshooting

### Error: "Class not found"

```
Error: Class "App\Application\Actions\Contact\CreateContactAction" not found
```

**Solución:**
```bash
# Regenerar autoloader
composer dump-autoload

# O instalar nuevamente
rm -rf vendor/
composer install
```

### Error: "Connection refused"

```
Error: SQLSTATE[HY000] [2002] Connection refused
```

**Solución:**
```bash
# Verificar que MySQL está corriendo
sudo systemctl status mysql

# O iniciar MySQL
sudo systemctl start mysql

# Verificar credenciales en .env
```

### Error: "Permission denied"

```
Error: Permission denied in /var/www/html/api-admission
```

**Solución:**
```bash
# Cambiar propietario
sudo chown -R www-data:www-data /var/www/html/api-admission

# Cambiar permisos
sudo chmod -R 755 /var/www/html/api-admission
sudo chmod -R 775 /var/www/html/api-admission/storage
sudo chmod -R 775 /var/www/html/api-admission/logs
```

### Error: "mod_rewrite not enabled"

```
Error: .htaccess: Invalid command 'RewriteEngine'
```

**Solución:**
```bash
# Habilitar mod_rewrite
sudo a2enmod rewrite
sudo systemctl restart apache2
```

---

## 📚 Próximos Pasos

1. Leer [README.md](./README.md) - Información general
2. Leer [SECURITY.md](./SECURITY.md) - Sistema de seguridad
3. Leer [API_KEYS.md](./API_KEYS.md) - Gestión de claves
4. Crear primera API Key
5. Hacer pruebas con los endpoints

---

## 📞 Soporte

- **Documentación:** https://wut.edu.co/docs
- **Email:** support@wut.edu.co
- **GitHub Issues:** https://github.com/wut/api-admission/issues

---

**Última actualización:** Marzo 18, 2026  
**Versión:** 1.0.0

✅ **¡Instalación completada!**
