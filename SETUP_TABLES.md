# 📋 Crear Tablas Faltantes

Las tablas de **Rate Limiting, IP Logs, API Keys** no están siendo creadas automáticamente. Ejecuta estos comandos para crearlas.

---

## ✅ Opción 1: Ejecutar Script SQL (RECOMENDADO)

```bash
# Desde la raíz del proyecto
mysql -u root -p db_wut_admissions < CREATE_MISSING_TABLES.sql
```

Si te pide contraseña, escribe tu contraseña MySQL (vacía si no tiene).

---

## ✅ Opción 2: Ejecutar Manualmente en MySQL

```bash
# 1. Conectar a MySQL
mysql -u root -p

# 2. Copiar y pegar todo el contenido de CREATE_MISSING_TABLES.sql
# (Ver el archivo para copiar el SQL)
```

---

## ✅ Opción 3: Con PhpMyAdmin

1. Abre **PhpMyAdmin** (http://localhost/phpmyadmin)
2. Selecciona base de datos `db_wut_admissions`
3. Ve a la pestaña **SQL**
4. Copia y pega el contenido de `CREATE_MISSING_TABLES.sql`
5. Haz clic en **Ejecutar**

---

## ✅ Opción 4: Con MySQL Workbench

1. Abre **MySQL Workbench**
2. Selecciona tu conexión
3. Abre una nueva pestaña SQL
4. Copia y pega el contenido de `CREATE_MISSING_TABLES.sql`
5. Ejecuta (Ctrl+Enter)

---

## 🔍 Verificar que las Tablas Fueron Creadas

```bash
mysql -u root -p db_wut_admissions << EOF
SHOW TABLES;
EOF
```

Deberías ver:
```
admission
api_key_logs
api_keys
contact
ip_logs
rate_limiting
```

---

## 🧪 Verificar Después de Crear

Después de crear las tablas, haz una solicitud a la API:

```bash
curl -X POST http://localhost:8000/contacts \
  -H "X-API-Key: sk_test123" \
  -H "Content-Type: application/json" \
  -d '{...}'
```

Luego verifica que los datos se almacenaron:

```bash
mysql -u root -p db_wut_admissions << EOF
SELECT * FROM rate_limiting;
SELECT * FROM ip_logs;
SELECT * FROM api_keys;
SELECT * FROM api_key_logs;
EOF
```

---

## ❌ Si Tienes Error: "Table already exists"

Es normal si ya creaste las tablas. Simplemente ignora el error.

---

## 🚀 Listo

Una vez ejecutado el script, todas las tablas estarán creadas y los datos se almacenarán automáticamente.

