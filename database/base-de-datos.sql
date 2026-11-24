
USE master;
GO

IF EXISTS (SELECT name FROM sys.databases WHERE name = N'NEXUS_ADMIN_DB')
BEGIN
    ALTER DATABASE NEXUS_ADMIN_DB SET SINGLE_USER WITH ROLLBACK IMMEDIATE;
    DROP DATABASE NEXUS_ADMIN_DB;
END
GO

CREATE DATABASE NEXUS_ADMIN_DB;
GO

USE NEXUS_ADMIN_DB;
GO


CREATE TABLE Roles (
    id_rol INT IDENTITY(1,1) NOT NULL,
    nombre VARCHAR(50) NOT NULL,
    descripcion VARCHAR(150) NULL,
    estado BIT NOT NULL DEFAULT 1,
    fecha_creacion DATETIME NOT NULL DEFAULT GETDATE(),
    CONSTRAINT PK_Roles PRIMARY KEY (id_rol),
    CONSTRAINT UQ_Roles_Nombre UNIQUE (nombre)
);
GO

-- ========================================================
-- 2. TABLA: Usuarios
-- ========================================================
CREATE TABLE Usuarios (
    id_usuario INT IDENTITY(1,1) NOT NULL,
    id_rol INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    clave_hash VARCHAR(255) NOT NULL,
    telefono VARCHAR(20) NULL,
    estado BIT NOT NULL DEFAULT 1,
    fecha_registro DATETIME NOT NULL DEFAULT GETDATE(),
    CONSTRAINT PK_Usuarios PRIMARY KEY (id_usuario),
    CONSTRAINT FK_Usuarios_Roles FOREIGN KEY (id_rol) REFERENCES Roles(id_rol),
    CONSTRAINT UQ_Usuarios_Email UNIQUE (email)
);
GO

-- ========================================================
-- 3. TABLA: Categorias
-- ========================================================
CREATE TABLE Categorias (
    id_categoria INT IDENTITY(1,1) NOT NULL,
    nombre VARCHAR(80) NOT NULL,
    descripcion VARCHAR(200) NULL,
    activo BIT NOT NULL DEFAULT 1,
    CONSTRAINT PK_Categorias PRIMARY KEY (id_categoria),
    CONSTRAINT UQ_Categorias_Nombre UNIQUE (nombre)
);
GO

-- ========================================================
-- 4. TABLA: Clientes
-- ========================================================
CREATE TABLE Clientes (
    id_cliente INT IDENTITY(1,1) NOT NULL,
    tipo_documento VARCHAR(10) NOT NULL DEFAULT 'DNI',
    numero_documento VARCHAR(20) NOT NULL,
    nombres_razon_social VARCHAR(150) NOT NULL,
    correo VARCHAR(100) NOT NULL,
    telefono VARCHAR(25) NULL,
    direccion VARCHAR(200) NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT GETDATE(),
    CONSTRAINT PK_Clientes PRIMARY KEY (id_cliente),
    CONSTRAINT UQ_Clientes_Documento UNIQUE (numero_documento),
    CONSTRAINT CK_Clientes_TipoDoc CHECK (tipo_documento IN ('DNI', 'RUC', 'CE', 'PASAPORTE'))
);
GO

-- ========================================================
-- 5. TABLA: Productos
-- ========================================================
CREATE TABLE Productos (
    id_producto INT IDENTITY(1,1) NOT NULL,
    id_categoria INT NOT NULL,
    codigo_sku VARCHAR(30) NOT NULL,
    nombre VARCHAR(120) NOT NULL,
    descripcion VARCHAR(300) NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    activo BIT NOT NULL DEFAULT 1,
    CONSTRAINT PK_Productos PRIMARY KEY (id_producto),
    CONSTRAINT FK_Productos_Categorias FOREIGN KEY (id_categoria) REFERENCES Categorias(id_categoria),
    CONSTRAINT UQ_Productos_SKU UNIQUE (codigo_sku),
    CONSTRAINT CK_Productos_Precio CHECK (precio_unitario > 0),
    CONSTRAINT CK_Productos_Stock CHECK (stock >= 0)
);
GO

-- ========================================================
-- 6. TABLA: Pedidos
-- ========================================================
CREATE TABLE Pedidos (
    id_pedido INT IDENTITY(1,1) NOT NULL,
    id_cliente INT NOT NULL,
    id_usuario INT NOT NULL,
    fecha_pedido DATETIME NOT NULL DEFAULT GETDATE(),
    estado VARCHAR(20) NOT NULL DEFAULT 'PENDIENTE',
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    igv DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    CONSTRAINT PK_Pedidos PRIMARY KEY (id_pedido),
    CONSTRAINT FK_Pedidos_Clientes FOREIGN KEY (id_cliente) REFERENCES Clientes(id_cliente),
    CONSTRAINT FK_Pedidos_Usuarios FOREIGN KEY (id_usuario) REFERENCES Usuarios(id_usuario),
    CONSTRAINT CK_Pedidos_Estado CHECK (estado IN ('PENDIENTE', 'PAGADO', 'ANULADO', 'ENVIADO')),
    CONSTRAINT CK_Pedidos_Total CHECK (total >= 0)
);
GO

-- ========================================================
-- 7. TABLA: Detalles_Pedido
-- ========================================================
CREATE TABLE Detalles_Pedido (
    id_detalle INT IDENTITY(1,1) NOT NULL,
    id_pedido INT NOT NULL,
    id_producto INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    importe_subtotal AS (cantidad * precio_unitario) PERSISTED,
    CONSTRAINT PK_Detalles_Pedido PRIMARY KEY (id_detalle),
    CONSTRAINT FK_Detalles_Pedidos FOREIGN KEY (id_pedido) REFERENCES Pedidos(id_pedido) ON DELETE CASCADE,
    CONSTRAINT FK_Detalles_Productos FOREIGN KEY (id_producto) REFERENCES Productos(id_producto),
    CONSTRAINT CK_Detalles_Cantidad CHECK (cantidad > 0)
);
GO

-- ========================================================
-- ÍNDICES (Optimización de búsquedas y reportes)
-- ========================================================
CREATE NONCLUSTERED INDEX IX_Usuarios_Rol ON Usuarios(id_rol);
CREATE NONCLUSTERED INDEX IX_Productos_Categoria ON Productos(id_categoria);
CREATE NONCLUSTERED INDEX IX_Pedidos_Cliente ON Pedidos(id_cliente);
CREATE NONCLUSTERED INDEX IX_Pedidos_Fecha ON Pedidos(fecha_pedido DESC);
CREATE NONCLUSTERED INDEX IX_Detalles_Pedido_Producto ON Detalles_Pedido(id_producto);
GO

-- ========================================================
-- Registro de Pedido
-- ========================================================
CREATE OR ALTER PROCEDURE sp_RegistrarPedido
    @id_cliente INT,
    @id_usuario INT,
    @id_producto INT,
    @cantidad INT,
    @nuevo_id_pedido INT OUTPUT
AS
BEGIN
    SET NOCOUNT ON;
    BEGIN TRY
        BEGIN TRANSACTION;

        DECLARE @stock_actual INT;
        DECLARE @precio_actual DECIMAL(10,2);

        SELECT @stock_actual = stock, @precio_actual = precio_unitario
        FROM Productos
        WHERE id_producto = @id_producto AND activo = 1;

        IF @stock_actual IS NULL
        BEGIN
            RAISERROR('El producto especificado no existe o se encuentra inactivo.', 16, 1);
            ROLLBACK TRANSACTION;
            RETURN;
        END

        IF @stock_actual < @cantidad
        BEGIN
            RAISERROR('Stock insuficiente para procesar la orden administrativa.', 16, 1);
            ROLLBACK TRANSACTION;
            RETURN;
        END

        -- Cálculo automático de importes (IGV 18%)
        DECLARE @subtotal DECIMAL(10,2) = @precio_actual * @cantidad;
        DECLARE @igv DECIMAL(10,2) = @subtotal * 0.18;
        DECLARE @total DECIMAL(10,2) = @subtotal + @igv;

        -- Registro de cabecera
        INSERT INTO Pedidos (id_cliente, id_usuario, fecha_pedido, estado, subtotal, igv, total)
        VALUES (@id_cliente, @id_usuario, GETDATE(), 'PAGADO', @subtotal, @igv, @total);

        SET @nuevo_id_pedido = SCOPE_IDENTITY();

        -- Registro de detalle
        INSERT INTO Detalles_Pedido (id_pedido, id_producto, cantidad, precio_unitario)
        VALUES (@nuevo_id_pedido, @id_producto, @cantidad, @precio_actual);

        -- Actualización de inventario
        UPDATE Productos
        SET stock = stock - @cantidad
        WHERE id_producto = @id_producto;

        COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0
            ROLLBACK TRANSACTION;

        DECLARE @ErrorMessage NVARCHAR(4000) = ERROR_MESSAGE();
        RAISERROR(@ErrorMessage, 16, 1);
    END CATCH
END;
GO

-- ========================================================
-- 10 registros por tabla
-- ========================================================

-- Roles
INSERT INTO Roles (nombre, descripcion) VALUES
('Super Administrador', 'Acceso total a todas las funciones del sistema'),
('Administrador', 'Gestión general de ventas, productos y clientes'),
('Vendedor', 'Registro y seguimiento de pedidos y cotizaciones'),
('Supervisor de Almacén', 'Control de existencias y despacho');
GO

-- Usuarios
INSERT INTO Usuarios (id_rol, nombre, email, clave_hash, telefono) VALUES
(1, 'Carlos Mendoza', 'carlos.mendoza@nexus.com', 'hash_pass_admin1', '987654321'),
(2, 'Ana Beltrán', 'ana.beltran@nexus.com', 'hash_pass_admin2', '981122334'),
(3, 'Luis Rojas', 'luis.rojas@nexus.com', 'hash_pass_vendedor1', '972233445'),
(3, 'Sofía Paredes', 'sofia.paredes@nexus.com', 'hash_pass_vendedor2', '963344556'),
(4, 'Jorge Dávila', 'jorge.davila@nexus.com', 'hash_pass_almacen1', '954455667');
GO

-- Categorías
INSERT INTO Categorias (nombre, descripcion) VALUES
('Tecnología', 'Equipos de cómputo, laptops y componentes'),
('Accesorios', 'Periféricos, mouse, teclados y adaptadores'),
('Servicios Cloud', 'Licenciamiento y hosting empresarial'),
('Mobiliario de Oficina', 'Sillas ergonómicas y escritorios modulares'),
('Redes y Conectividad', 'Routers, switches y cableado estructurado');
GO

-- Clientes
INSERT INTO Clientes (tipo_documento, numero_documento, nombres_razon_social, correo, telefono, direccion) VALUES
('RUC', '20601234567', 'Innovatech Solutions S.A.C.', 'contacto@innovatech.pe', '01-4456789', 'Av. Javier Prado Este 2450, Lima'),
('RUC', '20509876543', 'Corporación Digital Nexus S.R.L.', 'ventas@digitalnexus.pe', '01-2213456', 'Calle Las Begonias 441, San Isidro'),
('DNI', '45879632', 'Martín Quispe Huamán', 'martin.quispe@gmail.com', '991234567', 'Jr. Junín 320, Lima Cercado'),
('DNI', '72345678', 'Valeria Romero Díaz', 'valeria.romero@hotmail.com', '984561230', 'Av. Arequipa 1280, Lince'),
('CE',  '001234567', 'Jean Dupont', 'jdupont@globalexp.com', '977889900', 'Calle Schell 310, Miraflores'),
('RUC', '20100055443', 'Sistemas del Pacífico E.I.R.L.', 'info@sistempacifico.com', '01-6109988', 'Av. Canaval y Moreyra 580, San Isidro');
GO

-- Productos
INSERT INTO Productos (id_categoria, codigo_sku, nombre, descripcion, precio_unitario, stock) VALUES
(1, 'PRD-LPT-01', 'Laptop Nexus Pro 15', 'Intel i7, 16GB RAM, 512GB SSD', 3850.00, 15),
(1, 'PRD-LPT-02', 'Laptop Nexus Ultrabook 13', 'Intel i5, 8GB RAM, 256GB SSD', 2600.00, 20),
(2, 'PRD-ACC-01', 'Mouse Inalámbrico Ergo Pro', 'Sensor óptico 1600 DPI silencioso', 65.00, 50),
(2, 'PRD-ACC-02', 'Teclado Mecánico RGB Nexus', 'Switches mecánicos táctiles compactos', 180.00, 35),
(3, 'PRD-SRV-01', 'Plan Cloud Nexus Business', 'Suscripción anual servidor dedicado', 1200.00, 100),
(4, 'PRD-MOB-01', 'Silla Ergonómica Nexus Comfort', 'Soporte lumbar ajustable y cabecera', 540.00, 18),
(5, 'PRD-NET-01', 'Switch Administrable 24 Puertos', 'Gigabit Ethernet L2/L3 empresarial', 890.00, 12);
GO

-- Pedidos
INSERT INTO Pedidos (id_cliente, id_usuario, fecha_pedido, estado, subtotal, igv, total) VALUES
(1, 3, '2026-10-15 09:30:00', 'PAGADO', 7700.00, 1386.00, 9086.00),
(2, 4, '2026-10-18 14:15:00', 'PAGADO', 2400.00, 432.00, 2832.00),
(3, 3, '2026-10-22 11:00:00', 'PAGADO', 245.00, 44.10, 289.10),
(4, 4, '2026-10-25 16:45:00', 'ENVIADO', 540.00, 97.20, 637.20),
(5, 3, '2026-10-28 10:20:00', 'PENDIENTE', 3850.00, 693.00, 4543.00);
GO

-- Detalles_Pedido
INSERT INTO Detalles_Pedido (id_pedido, id_producto, cantidad, precio_unitario) VALUES
(1, 1, 2, 3850.00),
(2, 5, 2, 1200.00),
(3, 3, 1, 65.00),
(3, 4, 1, 180.00),
(4, 6, 1, 540.00),
(5, 1, 1, 3850.00);
GO

-- ========================================================
-- prueba de funcionamiento
-- ========================================================
DECLARE @id_nuevo_pedido INT;
EXEC sp_RegistrarPedido 
    @id_cliente = 3, 
    @id_usuario = 3, 
    @id_producto = 3, 
    @cantidad = 2, 
    @nuevo_id_pedido = @id_nuevo_pedido OUTPUT;

SELECT @id_nuevo_pedido AS IdPedidoGenerado;
SELECT * FROM Pedidos WHERE id_pedido = @id_nuevo_pedido;
SELECT * FROM Detalles_Pedido WHERE id_pedido = @id_nuevo_pedido;
GO