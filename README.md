# Proyecto API REST MVC — Nexus Solutions (`NEXUS_ADMIN_DB`)

API RESTful desarrollada en **PHP (POO + PDO)** conectada a **Microsoft SQL Server**, organizada bajo la arquitectura **Modelo–Vista–Controlador (MVC)** con enrutamiento limpio, variables de entorno y validación integral de datos.

## 1. Estructura del Proyecto

```text
PROYECTOS-NEXUS/
├── .env                                    # Variables de entorno locales (excluido de Git)
├── .env.example                            # Plantilla segura de configuración
├── .gitignore                              # Exclusión de .env y node_modules/
├── api/
│   ├── .htaccess                           # Reescritura de rutas REST hacia index.php
│   ├── index.php                           # Punto de entrada (Front Controller)
│   ├── config/
│   │   ├── EnvLoader.php                   # Cargador de variables de entorno
│   │   └── Conexion.php                    # Conexión PDO/ODBC a SQL Server
│   ├── helpers/
│   │   └── ResponseHelper.php              # Respuestas JSON, códigos HTTP y UTF-8
│   ├── models/
│   │   ├── BaseModel.php                   # Clase base de acceso a datos
│   │   ├── RolModel.php                    # Modelo tabla Roles
│   │   ├── UsuarioModel.php                # Modelo tabla Usuarios
│   │   ├── CategoriaModel.php              # Modelo tabla Categorias
│   │   ├── ClienteModel.php                # Modelo tabla Clientes
│   │   ├── ProductoModel.php               # Modelo tabla Productos
│   │   ├── PedidoModel.php                 # Modelo tabla Pedidos
│   │   └── DetallePedidoModel.php          # Modelo tabla Detalles_Pedido
│   ├── controllers/
│   │   ├── RolController.php
│   │   ├── UsuarioController.php
│   │   ├── CategoriaController.php
│   │   ├── ClienteController.php
│   │   ├── ProductoController.php
│   │   ├── PedidoController.php
│   │   └── DetallePedidoController.php
│   └── routes/
│       └── api.php                         # Enrutador REST (GET, POST, PUT, PATCH, DELETE)
├── database/
│   └── base-de-datos.sql                   # Script DDL/DML de NEXUS_ADMIN_DB
├── docs/
│   ├── EVIDENCIAS_CRUD_API.md              # Evidencias de pruebas CRUD
│   └── Nexus_API_Postman_Collection.json   # Colección lista para Postman / Thunder Client
├── img/
│   └── img-1.jpeg
├── src/
│   ├── app.js
│   ├── index.html
│   ├── output.css
│   └── styles.css
├── package.json
├── package-lock.json
└── tailwind.config.js
```

## 2. Instalación y Puesta en Marcha

1. Ejecutar `database/base-de-datos.sql` en **SQL Server Management Studio (SSMS)** para crear `NEXUS_ADMIN_DB` y sus datos iniciales.
2. Copiar `.env.example` como `.env` y configurar `DB_HOST`, `DB_USER` y `DB_PASS`.
3. Colocar la carpeta `PROYECTOS-NEXUS` dentro de `C:\xampp\htdocs\` e iniciar **Apache** en XAMPP.
4. Importar `docs/Nexus_API_Postman_Collection.json` en **Postman** o **Thunder Client** y probar los endpoints.

## 3. Endpoints CRUD Disponibles

| Módulo / Tabla | Listar (`GET`) | Consultar ID (`GET`) | Crear (`POST`) | Actualizar (`PUT`/`PATCH`) | Eliminar (`DELETE`) |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Roles** | `/api/roles` | `/api/roles/{id}` | `/api/roles` | `/api/roles/{id}` | `/api/roles/{id}` |
| **Usuarios** | `/api/usuarios` | `/api/usuarios/{id}` | `/api/usuarios` | `/api/usuarios/{id}` | `/api/usuarios/{id}` |
| **Categorías** | `/api/categorias` | `/api/categorias/{id}` | `/api/categorias` | `/api/categorias/{id}` | `/api/categorias/{id}` |
| **Clientes** | `/api/clientes` | `/api/clientes/{id}` | `/api/clientes` | `/api/clientes/{id}` | `/api/clientes/{id}` |
| **Productos** | `/api/productos` | `/api/productos/{id}` | `/api/productos` | `/api/productos/{id}` | `/api/productos/{id}` |
| **Pedidos** | `/api/pedidos` | `/api/pedidos/{id}` | `/api/pedidos` | `/api/pedidos/{id}` | `/api/pedidos/{id}` |
| **Detalles_Pedido** | `/api/detalles-pedido` | `/api/detalles-pedido/{id}` | `/api/detalles-pedido` | `/api/detalles-pedido/{id}` | `/api/detalles-pedido/{id}` |
