let carrito = [];
let total = 0;

function agregarAlCarrito(nombreItem, precio) {
    const itemExistente = carrito.find(item => item.nombre === nombreItem);
    
    if (itemExistente) {
        itemExistente.cantidad += 1;
    } else {
        carrito.push({ nombre: nombreItem, precio: precio, cantidad: 1 });
    }
    
    actualizarTotal();
    renderizarCarrito();
}

function eliminarDelCarrito(nombreItem) {
    const itemIndex = carrito.findIndex(item => item.nombre === nombreItem);
    if (itemIndex !== -1) {
        carrito.splice(itemIndex, 1);
    }
    actualizarTotal();
    renderizarCarrito();
}

function vaciarCarrito() {
    carrito = [];
    total = 0;
    renderizarCarrito();
}

function actualizarTotal() {
    total = carrito.reduce((sum, item) => sum + (item.precio * item.cantidad), 0);
}

function renderizarCarrito() {
    const contenedorItems = document.getElementById('cart-items');
    const contenedorTotal = document.getElementById('cart-total');
    const botonVaciar = document.getElementById('btn-clear-all');
    
    if (carrito.length === 0) {
        contenedorItems.innerHTML = '<p class="empty-cart">El carrito está vacío</p>';
        contenedorTotal.innerText = '$0.00';
        botonVaciar.style.display = 'none';
        return;
    }
    
    botonVaciar.style.display = 'block';
    contenedorItems.innerHTML = '';
    
    carrito.forEach((item) => {
        const itemRow = document.createElement('div');
        itemRow.classList.add('cart-item-row');
        
        itemRow.innerHTML = `
            <div class="cart-item-details">
                <span class="cart-item-name">${item.nombre}</span>
                <span class="cart-item-qty">Cant: ${item.cantidad} x $${item.precio.toFixed(2)}</span>
            </div>
            <div class="cart-item-right">
                <strong>$${(item.precio * item.cantidad).toFixed(2)}</strong>
                <button class="btn-remove-item" onclick="eliminarDelCarrito('${item.nombre}')" title="Eliminar artículo">Eliminar</button>
            </div>
        `;
        contenedorItems.appendChild(itemRow);
    });
    
    contenedorTotal.innerText = `$${total.toFixed(2)}`;
}

document.addEventListener("DOMContentLoaded", () => {
    const categoryItems = document.querySelectorAll('.category-item');
    const productCards = document.querySelectorAll('.product-card');

    function filtrarProductos(categoria) {
        productCards.forEach(card => {
            const tarjetaCategoria = card.getAttribute('data-category');

            if (tarjetaCategoria === categoria) {
                card.style.display = 'flex'; 
            } else {
                card.style.display = 'none'; 
            }
        });
    }

    filtrarProductos('combos');
    document.getElementById('btn-clear-all').style.display = 'none';

    categoryItems.forEach(item => {
        item.addEventListener('click', () => {
            categoryItems.forEach(i => i.classList.remove('active'));
            item.classList.add('active');
            
            const categoriaSeleccionada = item.getAttribute('data-category');
            filtrarProductos(categoriaSeleccionada);
        });
    });
});