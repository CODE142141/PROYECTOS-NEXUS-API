# Evidencias de Pruebas CRUD — API REST MVC (`NEXUS_ADMIN_DB`)

URL Base en entorno local XAMPP: `http://localhost/PROYECTOS-NEXUS/api`

## 1. Módulo Roles (`/api/roles`)
- **POST `/api/roles` (`201 Created`)**
  - Solicitud: `{"nombre": "Auditor Interno", "descripcion": "Revisión de operaciones", "estado": 1}`
  - Respuesta: `{"ok": true, "mensaje": "Rol creado correctamente.", "datos": {"id_rol": 5, "nombre": "Auditor Interno", "descripcion": "Revisión de operaciones", "estado": 1}}`
- **GET `/api/roles` (`200 OK`)**: Devuelve el arreglo con los roles activos e inactivos.
- **GET `/api/roles/1` (`200 OK`)**: Devuelve `Super Administrador`.
- **PUT `/api/roles/5` (`200 OK`)**: Actualiza descripción/estado del rol creado.
- **DELETE `/api/roles/5` (`200 OK`)**: Elimina el rol de prueba si no tiene usuarios asociados.

## 2. Módulo Usuarios (`/api/usuarios`)
- **POST `/api/usuarios` (`201 Created`)**
  - Solicitud: `{"id_rol": 3, "nombre": "Valeria Torres", "email": "valeria.torres@nexus.com", "clave": "ClaveSegura2026", "telefono": "988776655"}`
  - Respuesta: Devuelve el usuario registrado con su `rol_nombre` y sin exponer `clave_hash`.
- **GET `/api/usuarios` (`200 OK`)** y **GET `/api/usuarios/1` (`200 OK`)**
- **PUT `/api/usuarios/6` (`200 OK`)** y **DELETE `/api/usuarios/6` (`200 OK`)**

## 3. Módulo Categorías (`/api/categorias`)
- **POST `/api/categorias` (`201 Created`)**
  - Solicitud: `{"nombre": "Seguridad Electrónica", "descripcion": "Cámaras IP y control biométrico", "activo": 1}`
- **GET `/api/categorias` (`200 OK`)**, **GET `/api/categorias/1` (`200 OK`)**, **PUT `/api/categorias/6` (`200 OK`)**, **DELETE `/api/categorias/6` (`200 OK`)**

## 4. Módulo Clientes (`/api/clientes`)
- **POST `/api/clientes` (`201 Created`)**
  - Solicitud: `{"tipo_documento": "DNI", "numero_documento": "46781234", "nombres_razon_social": "Diego Salazar Méndez", "correo": "diego.salazar@correo.pe", "telefono": "987112233", "direccion": "Av. La Molina 1450, Lima"}`
- **GET `/api/clientes` (`200 OK`)**, **GET `/api/clientes/1` (`200 OK`)**, **PUT `/api/clientes/7` (`200 OK`)**, **DELETE `/api/clientes/7` (`200 OK`)**

## 5. Módulo Productos (`/api/productos`)
- **POST `/api/productos` (`201 Created`)**
  - Solicitud: `{"id_categoria": 2, "codigo_sku": "PRD-ACC-03", "nombre": "Hub USB-C 7 en 1 Nexus", "descripcion": "HDMI 4K, PD 100W", "precio_unitario": 145.00, "stock": 30, "activo": 1}`
- **GET `/api/productos` (`200 OK`)**, **GET `/api/productos/1` (`200 OK`)**, **PUT `/api/productos/8` (`200 OK`)**, **DELETE `/api/productos/8` (`200 OK`)**

## 6. Módulo Pedidos (`/api/pedidos`)
- **POST `/api/pedidos` (`201 Created`)**
  - Solicitud: `{"id_cliente": 1, "id_usuario": 3, "estado": "PENDIENTE", "subtotal": 180.00}`
- **GET `/api/pedidos` (`200 OK`)**, **GET `/api/pedidos/1` (`200 OK` - incluye arreglo `detalles`)**, **PUT `/api/pedidos/7` (`200 OK`)**, **DELETE `/api/pedidos/7` (`200 OK`)**

## 7. Módulo Detalles_Pedido (`/api/detalles-pedido`)
- **POST `/api/detalles-pedido` (`201 Created`)**
  - Solicitud: `{"id_pedido": 5, "id_producto": 3, "cantidad": 2}` (toma automáticamente `precio_unitario = 65.00` y recalcula el pedido padre).
- **GET `/api/detalles-pedido` (`200 OK`)**, **GET `/api/detalles-pedido/1` (`200 OK`)**, **PUT `/api/detalles-pedido/8` (`200 OK`)**, **DELETE `/api/detalles-pedido/8` (`200 OK`)**

## 8. Validaciones y Manejo de Errores HTTP
- **400 Bad Request**: Campos obligatorios vacíos, tipos inválidos, `precio_unitario <= 0`, DNI distinto de 8 dígitos, clave foránea inexistente o intento de eliminar un registro padre con hijos asociados.
- **404 Not Found**: Consulta, edición o eliminación de un ID que no existe en la tabla, o ruta inexistente.
- **405 Method Not Allowed**: Uso de verbos HTTP no soportados.
