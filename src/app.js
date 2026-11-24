// src/app.js - Consumo de la API REST MVC (/api/productos)

document.addEventListener("DOMContentLoaded", () => {
    cargarProductos();
});

async function cargarProductos() {
    const contenedor = document.getElementById("contenedor-productos");
    if (!contenedor) return;

    contenedor.innerHTML = `
        <div class="col-span-full text-center py-8">
            <p class="text-indigo-600 font-semibold text-lg animate-pulse">Cargando productos desde la base de datos...</p>
        </div>
    `;

    try {
        const respuesta = await fetch("../api/productos");

        if (!respuesta.ok) {
            throw new Error(`Error HTTP: ${respuesta.status}`);
        }

        const resultado = await respuesta.json();

        if (!resultado.ok) {
            throw new Error(resultado.mensaje || "Error en la respuesta de la API.");
        }

        const productos = Array.isArray(resultado.datos) ? resultado.datos : [];
        const productosActivos = productos.filter(p => Number(p.activo) === 1);

        if (productosActivos.length === 0) {
            contenedor.innerHTML = `
                <div class="col-span-full text-center py-8">
                    <p class="text-gray-500 font-medium">No se encontraron productos activos en el catálogo.</p>
                </div>
            `;
            return;
        }

        renderizarTarjetas(productosActivos);

    } catch (error) {
        console.error("Error al cargar productos:", error);
        contenedor.innerHTML = `
            <div class="col-span-full text-center py-8 bg-red-50 rounded-lg border border-red-200">
                <p class="text-red-600 font-semibold">No se pudo cargar el catálogo de productos.</p>
                <p class="text-xs text-red-500 mt-1">${error.message}</p>
            </div>
        `;
    }
}

function renderizarTarjetas(lista) {
    const contenedor = document.getElementById("contenedor-productos");
    contenedor.innerHTML = "";

    lista.forEach(prod => {
        const precio = parseFloat(prod.precio_unitario).toFixed(2);
        const claseStock = Number(prod.stock) > 5 ? "text-emerald-600" : "text-amber-600";

        const card = document.createElement("div");
        card.className = "bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition flex flex-col justify-between";
        card.innerHTML = `
            <div>
                <span class="text-xs font-semibold uppercase tracking-wider text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-full">
                    ${prod.categoria}
                </span>
                <h3 class="text-lg font-bold text-gray-900 mt-3 mb-1">${prod.nombre}</h3>
                <p class="text-xs text-gray-400 font-mono mb-4">SKU: ${prod.codigo_sku}</p>
            </div>
            <div class="pt-4 border-t border-gray-100 flex items-center justify-between">
                <div>
                    <span class="text-xs text-gray-400 block">Precio</span>
                    <span class="text-xl font-black text-gray-900">S/ ${precio}</span>
                </div>
                <div class="text-right">
                    <span class="text-xs text-gray-400 block">Stock</span>
                    <span class="text-sm font-semibold ${claseStock}">
                        ${prod.stock} unid.
                    </span>
                </div>
            </div>
        `;
        contenedor.appendChild(card);
    });
}
