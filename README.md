# CMDB — Sistema de Gestión de Configuración
**TFG · Grado en Ingeniería Informática · UNIR 2025/2026**
Autor: Javier Moyano Vizcaíno

---

## Estructura del proyecto

```
cmdb/
├── index.php                  ← Punto de entrada (Front Controller)
├── .htaccess
├── README.md
├── datos_prueba.sql           ← Datos de ejemplo
├── crear_hash.php             ← Script aux para generar hashes
│
├── config/
│   ├── database.php           ← *** EDITAR credenciales aquí ***
│   ├── app.php
│   └── .htaccess              ← Bloquea acceso web a esta carpeta
│
├── controllers/
│   ├── AuthController.php
│   └── CiController.php
│
├── models/
│   ├── Database.php
│   ├── UsuarioModel.php
│   └── CiModel.php
│
├── views/
│   ├── login.php
│   ├── search.php
│   ├── detail.php
│   └── partials/
│       ├── header.php
│       └── footer.php
│
└── public/
    ├── css/
    │   ├── estilos_login.css
    │   └── app.css
    └── js/
        ├── login.js
        └── search.js
```

---

## Instalación

### 1. Base de datos
```sql
CREATE DATABASE cmdb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE erp  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- Ejecutar en orden:
source DDL_ERP.sql
source DDL_CMDB.sql
source datos_prueba.sql
```

### 2. Configuración
Editar `config/database.php` con tus credenciales MySQL.

### 3. Despliegue
Copiar la carpeta entera en el DocumentRoot de Apache:
- XAMPP Windows: `C:\xampp\htdocs\cmdb\`
- Linux:         `/var/www/html/cmdb/`

### 4. URL de acceso
```
http://localhost/cmdb/index.php
```

---

## Usuarios de prueba
| Login   | Contraseña  | Perfil        |
|---------|-------------|---------------|
| admin   | admin123    | Administrador |
| tecnico | tecnico123  | Técnico       |

> Si los hashes no funcionan, genera los tuyos ejecutando:
> `php crear_hash.php` y actualiza `datos_prueba.sql`

---

## Arquitectura MVC

| Capa       | Ficheros                 | Responsabilidad                     |
|------------|--------------------------|-------------------------------------|
| Model      | models/*.php             | Acceso BD, lógica de negocio        |
| View       | views/*.php              | Presentación HTML                   |
| Controller | controllers/*.php        | Coordinación M↔V, validación        |
| Router     | index.php                | Despacho por ?action=               |
